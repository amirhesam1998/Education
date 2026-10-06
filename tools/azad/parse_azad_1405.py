"""Read every programme row of the five Azad 1405 booklets.

Usage: python3 parse_azad_1405.py <booklet_dir> <out.json>

Each booklet has its own table layout:
- «سراسری_ازاد» lists programmes under a «استان … | واحد … - کد» heading,
  with the exam group and the capacity of each half-year;
- the records-based (سوابق تحصیلی) booklets list them either under a
  province heading (place code, place name, field code, field name, gender)
  or under a «کد و نام رشته تحصیلی» heading (province, place code, place
  name, gender).
The field lists printed after the tables (fields by education group) are
read too. Anything the parser does not recognise stops it.
"""
from __future__ import annotations

import json
import os
import re
import sys
from dataclasses import dataclass, field

import pymupdf

from azad_pdf import Band, FontForms, Loose, Phantoms, TO_ASCII_DIGITS, Zwnj, read_page

YEAR = 1405


@dataclass(frozen=True)
class Booklet:
    key: str
    file: str
    level: str | None        # مقطع as printed on the cover, None for the exam booklet
    basis: str               # 'exam' (با آزمون سراسری) or 'records' (بر اساس سوابق تحصیلی)
    layout: str              # 'unit', 'province' or 'field'
    pages: tuple[int, int]   # first and last page of the programme table
    title: str               # running title over the programme table
    lists: tuple[tuple[int, int, str, str], ...] = ()  # (first, last, list kind, running title)


BOOKLETS = [
    Booklet('sarasari', 'سراسری_ازاد.pdf', None, 'exam', 'unit', (10, 64),
            'جدول شماره 2- رشته / محل های آزمون سراسری سال 1405 دانشگاه آزاد اسلامی'),
    Booklet('kardani_napeyvaste', 'کاردانی_ناپیوسته_سوابق_تحصیلی.pdf', 'کاردانی ناپیوسته', 'records', 'province', (14, 172),
            'جدول شماره 2- رشته / محل های پذیرش بر اساس سوابق تحصیلی مقطع کاردانی ناپیوسته نیمسال اول سال 1405 دانشگاه آزاد اسلامی',
            ((173, 175, 'group',
              'جدول شماره 3- فهرست رشته های مقطع کاردانی ناپیوسته به تفکیک گروه های آموزشی(نیمسال اول 1405)'),
             (176, 183, 'technical',
              'جدول شماره 4- فهرست رشته های دوره کاردانی فنی و دوره کاردانی حرفه ای(ناپیوسته) به تفکیک گروه های آموزشی(نیمسال اول 1405)'))),
    Booklet('kardani_peyvaste', 'کاردانی_پیوسته_سوابق_تحصیلی.pdf', 'کاردانی پیوسته', 'records', 'field', (19, 155),
            'جدول شماره 3- رشته / محل های پذیرش بر اساس سوابق تحصیلی مقطع کاردانی پیوسته نیمسال اول سال 1405 دانشگاه آزاد اسلامی'),
    Booklet('karshenasi_napeyvaste', 'کارشناسی_ناپیوسته_سوابق_تحصیلی.pdf', 'کارشناسی ناپیوسته', 'records', 'field', (13, 156),
            'جدول شماره 2- رشته / محل های پذیرش بر اساس سوابق تحصیلی مقطع کارشناسی ناپیوسته نیمسال اول سال 1405 دانشگاه آزاد اسلامی'),
    Booklet('karshenasi_peyvaste', 'کارشناسی_پیوسته_سوابق_تحصیلی.pdf', 'کارشناسی پیوسته', 'records', 'province', (14, 205),
            'جدول شماره 2- رشته / محل های پذیرش بر اساس سوابق تحصیلی مقطع کارشناسی پیوسته نیمسال اول سال 1405 دانشگاه آزاد اسلامی',
            ((206, 211, 'group',
              'جدول شماره 3- فهرست رشته های مقطع کارشناسی پیوسته به تفکیک گروه های آموزشی(نیمسال اول 1405)'),)),
]

HEADERS = {
    'province': [['کد محل دانشگاهی', 'نام محل دانشگاهی', 'کد رشته تحصیلی', 'نام رشته تحصیلی', 'جنس پذیرش']],
    'field': [['استان', 'کد محل دانشگاهی', 'نام محل دانشگاهی', 'جنس پذیرش']],
    'unit': [['کد رشته', 'نام رشته تحصیلی', 'گروه', 'جنس', 'ظرفیت پذیرش نیمسال'],
             ['تحصیلی', '', 'آزمایشی', 'پذیرش', 'اول', 'دوم']],
    'list': [['کد رشته تحصیلی', 'نام رشته تحصیلی']],
}
COLUMNS = {
    'province': ('unit_code', 'unit_name', 'field_code', 'field_name', 'gender'),
    'field': ('province', 'unit_code', 'unit_name', 'gender'),
    'unit': ('field_code', 'field_name', 'exam_group', 'gender', 'capacity_first', 'capacity_second'),
    'list': ('field_code', 'field_name'),
}
GENDERS = {'زن و مرد', 'زن', 'مرد'}
CODE = re.compile(r'^\d+$')


class BookletError(Exception):
    pass


@dataclass
class State:
    heading: dict = field(default_factory=dict)
    heading_page: int | None = None
    heading_box: tuple[float, float] | None = None
    header_seen: int = 0


def printed_page(items: list) -> int | None:
    for it in items:
        if isinstance(it, Loose) and it.y0 > 760 and len(it.segments) == 1 and CODE.match(it.segments[0].translate(TO_ASCII_DIGITS)):
            return int(it.segments[0].translate(TO_ASCII_DIGITS))
    return None


def heading_of(layout: str, it: Loose, page: int) -> dict:
    seg = it.segments
    if layout == 'province':
        if len(seg) == 1 and (seg[0].startswith('استان ') or seg[0] == 'برون مرزی'):
            return {'province': seg[0]}
    elif layout == 'field':
        if len(seg) >= 3 and seg[0] == 'کد و نام رشته تحصیلی:' and CODE.match(seg[1].translate(TO_ASCII_DIGITS)):
            return {'field_code': seg[1].translate(TO_ASCII_DIGITS), 'field_name': ' '.join(seg[2:])}
    elif layout == 'unit':
        if len(seg) >= 2 and (seg[0].startswith('استان ') or seg[0] == 'برون مرزی'):
            # the unit title is sometimes set in two pieces with a wider gap: «واحد ایلام» «- ظرفیت خودگردان - 900»
            m = re.fullmatch(r'(.+?) - (\d+)', ' '.join(seg[1:]).translate(TO_ASCII_DIGITS))
            if m:
                return {'province': seg[0], 'unit_name': m.group(1), 'unit_code': m.group(2)}
    elif layout == 'list':
        if len(seg) == 2 and seg[0] == 'گروه':
            return {'group': seg[1]}
        if len(seg) == 1 and seg[0].startswith('گروه '):
            return {'group': seg[0][len('گروه '):]}
    raise BookletError(f'page {page}: unrecognised heading {seg!r}')


def check_row(layout: str, cells: dict, page: int) -> None:
    def need(ok: bool, what: str) -> None:
        if not ok:
            raise BookletError(f'page {page}: bad {what} in row {cells}')

    for name in ('unit_code', 'field_code'):
        if name in cells:
            need(bool(CODE.match(cells[name])), name)
    if 'gender' in cells:
        need(cells['gender'] in GENDERS, 'gender')
    for name in ('unit_name', 'field_name', 'province', 'exam_group'):
        if name in cells:
            need(bool(cells[name]) and not re.search(r'\d{3,}', cells[name].translate(TO_ASCII_DIGITS)), name)
    for name in ('capacity_first', 'capacity_second'):
        if name in cells:
            need(cells[name] == '-' or bool(CODE.match(cells[name])), name)


def read_table(doc: pymupdf.Document, fonts: FontForms, zwnj: Zwnj, phantoms: Phantoms, first: int, last: int,
               layout: str, title: str, booklet: str, out: list[dict], stats: dict) -> None:
    st = State()
    headers = HEADERS[layout]
    columns = COLUMNS[layout]
    for pno in range(first - 1, last):
        page = pno + 1
        items = read_page(doc, pno, fonts, zwnj, phantoms)
        printed = printed_page(items)
        if printed != page:
            raise BookletError(f'page {page}: printed page number is {printed}')
        loose = [it for it in items if isinstance(it, Loose)]
        if not loose or loose[0].text != title:
            raise BookletError(f'page {page}: running title {loose[0].text if loose else None!r}')
        st.header_seen = 0
        headings_on_page = 0
        for it in items:
            if isinstance(it, Loose):
                if it is loose[0] or (it.y0 > 760 and it.text == str(page)):
                    continue
                st.heading = heading_of(layout, it, page)
                st.heading_page = page
                st.heading_box = (round(it.y0, 2), round(it.y1, 2))
                st.header_seen = 0
                headings_on_page += 1
                continue
            cells = it.cells
            if st.header_seen < len(headers):
                if cells != headers[st.header_seen]:
                    raise BookletError(f'page {page}: expected header {headers[st.header_seen]} got {cells}')
                st.header_seen += 1
                continue
            if len(cells) != len(columns):
                raise BookletError(f'page {page}: {len(cells)} cells under {layout}: {cells}')
            if not st.heading:
                raise BookletError(f'page {page}: row before any heading: {cells}')
            row = dict(zip(columns, cells))
            row['unit_code'] = row.get('unit_code', '').translate(TO_ASCII_DIGITS) or None
            row['field_code'] = row.get('field_code', '').translate(TO_ASCII_DIGITS) or None
            for name in ('capacity_first', 'capacity_second'):
                if name in row:
                    row[name] = row[name].translate(TO_ASCII_DIGITS)
            check_row(layout, {k: v for k, v in row.items() if v is not None}, page)
            for key, value in st.heading.items():
                if key in row and row[key] is not None:
                    raise BookletError(f'page {page}: heading and row both carry {key}')
                row[key] = value
            row['booklet'] = booklet
            row['page'] = page
            row['heading_page'] = st.heading_page
            row['heading_box'] = st.heading_box
            row['box'] = (round(it.y0, 2), round(it.y1, 2))
            out.append(row)
            stats[page] = stats.get(page, 0) + 1
        if headings_on_page == 0 and layout != 'unit':
            # province and field tables repeat their heading at the top of every page
            raise BookletError(f'page {page}: no heading on this page')


def parse(booklet_dir: str) -> dict:
    result: dict = {'year': YEAR, 'booklets': [], 'rows': [], 'lists': [], 'zwnj': {}, 'rows_per_page': {}}
    for b in BOOKLETS:
        path = os.path.join(booklet_dir, b.file)
        doc = pymupdf.open(path)
        fonts = FontForms(doc)
        zwnj = Zwnj()
        phantoms = Phantoms()
        rows: list[dict] = []
        stats: dict[int, int] = {}
        read_table(doc, fonts, zwnj, phantoms, b.pages[0], b.pages[1], b.layout, b.title, b.key, rows, stats)
        lists: list[dict] = []
        for first, last, kind, title in b.lists:
            entries: list[dict] = []
            read_table(doc, fonts, zwnj, phantoms, first, last, 'list', title, b.key, entries, {})
            for e in entries:
                e['list'] = kind
            lists += entries
        result['booklets'].append({**b.__dict__, 'page_count': doc.page_count, 'rows': len(rows)})
        result['rows'] += rows
        result['lists'] += lists
        result['zwnj'][b.key] = zwnj.inserted
        result.setdefault('phantom_spaces', {})[b.key] = phantoms.dropped
        result['rows_per_page'][b.key] = stats
        print(f'{b.key}: {len(rows)} rows, {len(lists)} list entries, {len(zwnj.inserted)} ZWNJ words, {len(phantoms.dropped)} phantom spaces', file=sys.stderr)
    return result


def main() -> None:
    booklet_dir, out = sys.argv[1], sys.argv[2]
    result = parse(booklet_dir)
    with open(out, 'w', encoding='utf-8') as fh:
        json.dump(result, fh, ensure_ascii=False, indent=0)


if __name__ == '__main__':
    main()
