"""Turn the 1405 field-selection booklet (one exam group) into structured rows.

Usage: python3 parse_1405.py <booklet.pdf> <first_table_page> <last_table_page> <out.json>

Every table row of the booklet becomes one record holding the printed cells
under named columns plus the context printed around the table: the booklet
part, the admission period, the section title and the notes under it.
Nothing is guessed here. Anything the parser does not recognise stops it,
so a new layout cannot slip through as wrong data.
"""
from __future__ import annotations

import json
import re
import sys
from dataclasses import dataclass, field

import pymupdf

from booklet_pages import MERGED_CELLS, Band, Loose, read_page
from pdf_cells import GlyphForms, normalize

DIGITS = str.maketrans('۰۱۲۳۴۵۶۷۸۹٠١٢٣٤٥٦٧٨٩', '01234567890123456789')

# Big headings that open a booklet part, and the smaller ones printed with them.
PART_HEADINGS = {
    'رشته‌محل‌های دانشگاه‌های وزارت بهداشت، درمان و آموزش پزشکی': 'health',
    'رشته‌محل‌های پزشکی و دندان‌پزشکی دارای تعهد خدمت موضوع مصوبات شورای عالی انقلاب': 'health_commitment_council',
    'رشته‌محل‌های پزشکی، داروسازی و دندان‌پزشکی دارای تعهد خدمت موضوع قانون': 'health_commitment_justice',
    'رشته محل‌های سهمیه بومی در رشته‌های کاردانی و کارشناسی دارای تعهد خدمت': 'health_commitment_native',
    'رشته‌محل‌های دانشگاه‌ها و مؤسسات آموزش عالی غیردولتی- غیرانتفاعی': 'nonprofit',
    'رشته‌محل‌های دانشگاه‌های وزارت علوم، تحقیقات و فناوری': 'science',
    'رشته‌محل‌های سهمیه مناطق محروم و رشته‌محل‌های مخصوص متقاضیان مناطق درگیر بلایای طبیعی': 'quota',
    'رشته‌محل‌های دانشگاه‌های فرهنگیان، تربیت دبیر شهید رجایی و تعدادی از دانشگاه‌های علوم': 'teacher',
    'رشته‌محل‌های دانشگاه پیام‌نور': 'payame_noor',
    # riazi
    'رشته‌محل‌های سهمیه بومی در رشته‌های کاردانی و کارشناسی دارای تعهد خدمت': 'health_commitment_native',
    'رشته‌محل‌های مؤسسات آموزش عالی غیردولتی- غیرانتفاعی': 'nonprofit',
    'رشته‌محل‌های دانشگاه‌های فرهنگیان و تربیت دبیر شهید رجایی': 'teacher',
}
# Second lines of the part headings above, and other headings that only describe the part.
HEADING_CONTINUATIONS = {
    'دوره‌های روزانه، شهریه‌پرداز و دانشگاه آزاد اسلامی به تفکیک رشته‌محل‌های پذیرش',
    'نیم‌سال اول و دوم سال 1405 و نیم‌سال اول (مهرماه) سال 1406',
    'فرهنگی به تفکیک رشته‌محل‌های پذیرش',
    'برقراری عدالت آموزشی',
    'مورد نیاز وزارت بهداشت، درمان و آموزش پزشکی',
    'دوره‌های روزانه، نوبت دوم، روزانه- غیردولتی، مجازی، و پردیس‌خودگردان',
    'دانشگاه‌ها و مؤسسات آموزش عالی',
    '(سیل، زلزله،...) و متقاضیان بومی جنوب استان کرمان (شهرستان‌های جیرفت، رودبار جنوب،',
    'عنبرآباد، قلعه گنج، کهنوج و منوجان) و همچنین مخصوص متقاضیان بومی شهرستان بشاگرد از استان',
    'هرمزگان',
    'پزشکی وزارت بهداشت (رشته بهداشت مدارس)',
    'رشته‌های تحصیلی دانشگاه‌های فرهنگیان و تربیت دبیر شهید رجایی',
    '(شهرستان‌های جیرفت، رودبار جنوب، عنبرآباد، قلعه گنج، کهنوج و منوجان)',
    # riazi
    'دوره‌های روزانه، نوبت دوم، روزانه- غیردولتی، مجازی و پردیس‌خودگردان',
    'دوره‌های روزانه و شهریه‌پرداز',
    'رودبار جنوب، عنبرآباد، قلعه گنج، کهنوج و منوجان)',
    # ensani
    'جنوب، عنبرآباد، قلعه گنج، کهنوج و منوجان)',
    'رشته‌محل‌های دوره‌های روزانه و شهریه‌پرداز',
    # zaban
    'دوره‌های روزانه، نوبت دوم، مجازی و پردیس‌خودگردان دانشگاه‌ها و مؤسسات آموزش عالی',
}
PERIOD_HEADINGS = {
    'رشته‌محل‌های پذیرش نیم‌سال اول و دوم سال 1405 :': 'نیم‌سال اول و دوم سال 1405',
    'رشته‌محل‌های پذیرش نیم‌سال اول (مهرماه) سال 1406 :': 'نیم‌سال اول (مهرماه) سال 1406',
}
SUBPART_HEADINGS = {
    'رشته‌های تحصیلی سهمیه مناطق محروم': 'deprived',
    'سهمیه مخصوص متقاضیان بومی مناطق درگیر بلایای طبیعی (سیل، زلزله و ...)': 'disaster',
    'سهمیه مخصوص متقاضیان بومی شهرستان های جنوب استان کرمان': 'south_kerman',
    'سهمیه مخصوص متقاضیان بومی شهرستان بشاگرد': 'bashagard',
    # riazi
    'رشته‌محل‌های سهمیه مناطق محروم': 'deprived',
    'سهمیه مخصوص متقاضیان بومی شهرستان‌های جنوب استان کرمان (شهرستان‌های جیرفت،': 'south_kerman',
    # ensani
    'سهمیه مخصوص متقاضیان بومی مناطق درگیر بلایای طبیعی (سیل، زلزله و...)': 'disaster',
    'سهمیه مخصوص متقاضیان بومی شهرستان‌های جنوب استان کرمان (شهرستانهای جیرفت، رودبار': 'south_kerman',
    # zaban
    'سهمیه مخصوص متقاضیان بومی شهرستان‌های جنوب استان کرمان (شهرستان‌های جیرفت، رودبار': 'south_kerman',
}
# Second lines of the part-level "نکته:" remarks printed in title size.
REMARK_ENDINGS = {'همین دفترچه راهنما مراجعه کنند.', 'مراجعه کنید.', 'همین دفترچه مراجعه کنند.'}
IGNORED_LINES = {
    'متقاضیان به نکات ذیل توجه نمایند:',
    'مهمترین شرایط و ضوابط پذیرش این رشته‌ها به شرح ذیل است:',
}
SECTION_PATTERNS = [
    re.compile(r'^استان .+ - .+'),
    re.compile(r'^دانشگاه پیام نور استان .+ - .+'),
    re.compile(r'^پذیرش از تمام متقاضیان سراسر کشور، با (ترتیب )?اولویت متقاضیان .+'),
    re.compile(r'^مخصوص متقاضیان بومی استان .+'),
    re.compile(r'^سهمیه مخصوص متقاضیان بومی .+'),
]

# Header layouts, cells listed right to left as printed.
LAYOUTS = {
    ('نحوه پذیرش', 'دوره تحصیلی', 'کدرشته محل', 'عنوان رشته', 'ظرفیت پذیرش نیم‌سال اول دوم', 'جنس پذیرش زن مرد', 'توضیحات'):
        ('method', 'course', 'code', 'title', 'capacity_first', 'capacity_second', 'female', 'male', 'notes'),
    ('نحوه پذیرش', 'کدرشته محل', 'عنوان رشته', 'ظرفیت پذیرش نیم‌سال اول دوم', 'جنس پذیرش زن مرد', 'توضیحات'):
        ('method', 'code', 'title', 'capacity_first', 'capacity_second', 'female', 'male', 'notes'),
    ('نحوه پذیرش', 'کدرشته محل', 'عنوان رشته', 'ظرفیت پذیرش نیم‌سال اول دوم', 'جنس پذیرش زن مرد', 'دانشگاه علوم پزشکی محل تحصیل / توضیحات'):
        ('method', 'code', 'title', 'capacity_first', 'capacity_second', 'female', 'male', 'institution_notes'),
    ('نحوه پذیرش', 'کدرشته محل', 'عنوان رشته', 'ظرفیت پذیرش نیم‌سال اول دوم', 'جنس پذیرش زن مرد', 'دانشگاه محل تحصیل / توضیحات'):
        ('method', 'code', 'title', 'capacity_first', 'capacity_second', 'female', 'male', 'institution_notes'),
    ('کدرشته محل', 'عنوان رشته', 'ظرفیت', 'جنس', 'دانشگاه یا پردیس محل تحصیل / دامنه پذیرش', 'محل خدمت'):
        ('code', 'title', 'capacity', 'gender', 'campus_scope', 'service_location'),
}
# Sub-column labels under a merged header cell, right to left.
SUB_LABELS = {'capacity_first': 'اول', 'capacity_second': 'دوم', 'female': 'زن', 'male': 'مرد'}


class BookletError(Exception):
    pass


HEADERLESS: list[int] = []


@dataclass
class Section:
    id: int
    title: str
    part: str | None
    part_heading: str | None
    period: str | None
    subpart: str | None
    first_page: int
    notes: list[str] = field(default_factory=list)
    rows: int = 0


@dataclass
class Context:
    part: str | None = None
    part_heading: list[str] = field(default_factory=list)
    period: str | None = None
    subpart: str | None = None
    section: Section | None = None
    title_lines: list[str] = field(default_factory=list)
    small_print: list[str] = field(default_factory=list)
    layout: tuple[str, ...] | None = None
    edges: list[float] | None = None


def page_number(items: list) -> int | None:
    for it in items:
        if isinstance(it, Loose) and it.y0 > 740 and re.fullmatch(r'\d+', it.text.translate(DIGITS)):
            return int(it.text.translate(DIGITS))
    return None


def check_sub_columns(header: Band, names: tuple[str, ...], edges: list[float], page: int) -> None:
    """The labels اول/دوم and زن/مرد must sit above the data columns they are mapped to."""
    glyphs = [g for cell in header.glyphs for g in cell]
    for ci, name in enumerate(names):
        label = SUB_LABELS.get(name)
        if not label:
            continue
        lo, hi = edges[ci + 1], edges[ci]
        inside = [g for g in glyphs if lo - 0.5 <= g.cx <= hi + 0.5 and g.c.strip()]
        bottom = max((g.oy for g in inside), default=0)
        inside = ''.join(g.c for g in sorted((g for g in inside if g.oy > bottom - 2), key=lambda g: -g.x1))
        if normalize(inside) != label:
            raise BookletError(f'page {page}: column {name} expected label {label!r}, header shows {inside!r}')


HEADER_TAILS = {'محل', 'اول', 'دوم', 'زن', 'مرد'}
SPLIT_HEADERS: list[int] = []


def join_header(header: Band, tail: Band, page: int) -> Band:
    """A header whose second line is ruled off (zaban p. 105): append each lower cell to the header cell above it."""
    if abs(tail.y0 - header.y1) > 1 or any(c and c not in HEADER_TAILS for c in tail.cells):
        raise BookletError(f'page {page}: unknown header {header.cells}')
    cells = list(header.cells)
    for ci, text in enumerate(tail.cells):
        if not text:
            continue
        middle = (tail.edges[ci] + tail.edges[ci + 1]) / 2
        above = [hi for hi in range(len(cells)) if header.edges[hi + 1] < middle < header.edges[hi]]
        if len(above) != 1:
            raise BookletError(f'page {page}: header line {text!r} is under no single header cell')
        cells[above[0]] += ' ' + text
    SPLIT_HEADERS.append(page)
    return Band(page, header.y0, tail.y1, cells, header.edges, header.glyphs + tail.glyphs)


def parse(pdf: str, first: int, last: int) -> tuple[list[Section], list[dict]]:
    doc = pymupdf.open(pdf)
    forms = GlyphForms(doc)
    ctx = Context()
    sections: list[Section] = []
    rows: list[dict] = []
    for index in range(first - 1, last):
        items = read_page(doc, index, forms)
        page = index + 1
        printed = page_number(items)
        pending_header: Band | None = None
        split_header: Band | None = None
        ctx.edges = None  # every page draws its own grid
        for it in items:
            if isinstance(it, Loose):
                handle_line(it, ctx, page)
                continue
            if not any(c.strip() for c in it.cells):
                continue
            if split_header is not None:
                it, split_header = join_header(split_header, it, page), None
            if any('کدرشته' in c.replace(' ', '') for c in it.cells):
                key = tuple(c.replace('کد رشته', 'کدرشته').replace('نیمسال', 'نیم‌سال') for c in it.cells)  # header spelling varies
                if key not in LAYOUTS and it is not items[-1]:
                    split_header = it  # its second line may be ruled off as a band of its own
                    continue
                if key not in LAYOUTS:
                    raise BookletError(f'page {page}: unknown header {key}')
                ctx.layout = LAYOUTS[key]
                ctx.edges = None
                pending_header = it
                continue
            if ctx.layout is None or len(it.cells) != len(ctx.layout):
                raise BookletError(f'page {page}: row with {len(it.cells)} cells under layout {ctx.layout}: {it.cells}')
            if ctx.edges is None:
                if pending_header is None:
                    HEADERLESS.append(page)  # same layout continues on a new page without repeating the header
                else:
                    check_sub_columns(pending_header, ctx.layout, it.edges, page)
                ctx.edges = it.edges
            elif len(it.edges) != len(ctx.edges) or max(abs(a - b) for a, b in zip(ctx.edges, it.edges)) > 1.5:
                raise BookletError(f'page {page}: row edges {it.edges} differ from table edges {ctx.edges}')
            close_title(ctx, sections, page)
            if ctx.section is None:
                raise BookletError(f'page {page}: row before any section title: {it.cells}')
            cells = dict(zip(ctx.layout, it.cells))
            rows.append({
                'code': cells['code'].translate(DIGITS),
                'page': page,
                'printed_page': printed,
                'section_id': ctx.section.id,
                'cells': cells,
            })
            ctx.section.rows += 1
    close_title(ctx, sections, last)
    return sections, rows


def same_title(title: str) -> str:
    return title.replace('–', '-')  # a continued title may print the en dash of the first page as a hyphen (zaban p. 157)


def close_title(ctx: Context, sections: list[Section], page: int) -> None:
    """A section title (and the small print under it) ends where its table starts."""
    small, ctx.small_print = ctx.small_print, []
    if not ctx.title_lines:
        if small and ctx.section is not None:
            ctx.section.notes += [s for s in small if s not in ctx.section.notes]
        return
    title = normalize(' '.join(ctx.title_lines))
    ctx.title_lines = []
    continued = re.match(r'^ادامه\s*(.*)$', title)
    if continued:
        if ctx.section is None or same_title(continued.group(1)) != same_title(ctx.section.title):
            raise BookletError(f'page {page}: continuation {continued.group(1)!r} does not match open section {ctx.section.title if ctx.section else None!r}')
        for s in small:
            if s not in ctx.section.notes:
                raise BookletError(f'page {page}: note under continued title is new: {s!r}')
        return
    if not any(p.match(title) for p in SECTION_PATTERNS):
        raise BookletError(f'page {page}: unrecognised section title {title!r}')
    ctx.section = Section(len(sections) + 1, title, ctx.part, ctx.part_heading[0] if ctx.part_heading else None, ctx.period, ctx.subpart, page, notes=small)
    sections.append(ctx.section)


def handle_line(it: Loose, ctx: Context, page: int) -> None:
    text = it.text
    if it.font.startswith('BZar') and (it.y0 < 60 or it.y0 > 740):
        return  # running header and page number
    if 'Titr' not in it.font:
        return  # instructions printed before a part
    if text in IGNORED_LINES:
        return
    size = it.size
    if size >= 12.5 or (size > 10.5 and (text in SUBPART_HEADINGS or text in HEADING_CONTINUATIONS)):  # zaban p. 108 sets a quota heading in 12 pt
        if ctx.title_lines:
            raise BookletError(f'page {page}: heading {text!r} right after an unfinished title {ctx.title_lines}')
        if text in PART_HEADINGS:
            ctx.part = PART_HEADINGS[text]
            ctx.part_heading = [text]
            ctx.period = ctx.subpart = None
            ctx.section = None
            ctx.layout = None
        elif text in PERIOD_HEADINGS:
            ctx.period = PERIOD_HEADINGS[text]
            ctx.section = None
        elif text in SUBPART_HEADINGS:
            ctx.subpart = SUBPART_HEADINGS[text]
            ctx.section = None
        elif text in HEADING_CONTINUATIONS:
            ctx.part_heading.append(text)
        else:
            raise BookletError(f'page {page}: unknown heading {text!r} ({size})')
        return
    if 8.5 <= size <= 10.5 or (size < 8.5 and any(p.match(text) for p in SECTION_PATTERNS)):
        # a long section title is sometimes set in small type to fit one line (riazi p. 67)
        if text.startswith('نکته:') or text in REMARK_ENDINGS or text.startswith('متقاضیان پس از مطالعه کامل'):
            return  # part-level remarks printed in title size
        if ctx.small_print and not ctx.title_lines:
            close_title(ctx, [], page)  # small print after a table belongs to that table's section
        ctx.title_lines.append(text)
        ctx.edges = None  # a new title starts a new table, which may not repeat the header
        return
    if size < 8.5 or text.startswith('*'):
        ctx.small_print.append(text)
        return
    raise BookletError(f'page {page}: unexpected title-font line {text!r} ({size})')


def main() -> None:
    pdf, first, last, out = sys.argv[1], int(sys.argv[2]), int(sys.argv[3]), sys.argv[4]
    sections, rows = parse(pdf, first, last)
    with open(out, 'w', encoding='utf-8') as fh:
        json.dump({'sections': [s.__dict__ for s in sections], 'rows': rows}, fh, ensure_ascii=False, indent=0)
    print(len(sections), 'sections', len(rows), 'rows', 'pages without header', HEADERLESS, 'merged cells', MERGED_CELLS, 'split headers', SPLIT_HEADERS)


if __name__ == '__main__':
    main()
