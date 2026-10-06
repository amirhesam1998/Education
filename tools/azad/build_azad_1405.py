"""Build the Azad 1405 catalogue data from the parsed booklets.

Usage: python3 build_azad_1405.py <parsed.json> <booklet_dir> <out_dir> [review_dir]

<parsed.json> is the output of parse_azad_1405.py. Every row gets its
canonical province and city (azad_places_1405), one spelling per unit and
field, its education group (from the booklet's own field lists) and Persian
digits in its texts. Anything that cannot be resolved stops the build.

<out_dir> (database/seeders/data/azad/1405) receives programs.jsonl.gz and
manifest.json, which `php artisan education:import-azad-programs` loads.
With [review_dir] a review workbook is written there too.
"""
from __future__ import annotations

import collections
import gzip
import hashlib
import json
import os
import re
import sys

from azad_places_1405 import CITY_OF_UNIT, INFERRED, UNIT_NAMES, city, is_self_funded, key, known_cities, province
from parse_azad_1405 import BOOKLETS, YEAR

TO_PERSIAN_DIGITS = str.maketrans('0123456789', '۰۱۲۳۴۵۶۷۸۹')

GROUPS = {
    'علوم تجربی': 'tajrobi',
    'علوم ریاضی وفنی': 'riazi',
    'علوم انسانی': 'ensani',
    'هنر': 'honar',
    'زبان خارجی': 'zaban',
}

BOOKLET_LABELS = {
    'sarasari': 'با آزمون سراسری',
    'kardani_napeyvaste': 'کاردانی ناپیوسته - سوابق تحصیلی',
    'kardani_peyvaste': 'کاردانی پیوسته - سوابق تحصیلی',
    'karshenasi_napeyvaste': 'کارشناسی ناپیوسته - سوابق تحصیلی',
    'karshenasi_peyvaste': 'کارشناسی پیوسته - سوابق تحصیلی',
}

# Field names the programme table prints cut off at the cell edge; the
# booklet's own field list prints them whole. (booklet, field code)
CUT_OFF_NAMES = {
    ('kardani_napeyvaste', '21636'),  # «... مربیگری پایه کوه پیمایی و» / «... کوه پیمایی و کوهنوردی»
    ('kardani_napeyvaste', '41157'),  # «... تجهیزات کنترل عفونی و» / «... کنترل عفونی و استریلیزاسیون»
}

PART_TIME = '(پاره وقت)'


class BuildError(Exception):
    pass


def persian_digits(text: str) -> str:
    return text.translate(TO_PERSIAN_DIGITS)


def capacity(value: str) -> int | None:
    return None if value == '-' else int(value)


def build(parsed: dict) -> tuple[list[dict], dict]:
    notes: dict[str, list] = collections.defaultdict(list)
    lists: dict[tuple[str, str], dict] = {}
    for e in parsed['lists']:
        k = (e['booklet'], e['field_code'])
        if k in lists:
            raise BuildError(f'field {k} listed twice')
        if e['group'] not in GROUPS:
            raise BuildError(f'unknown education group {e["group"]!r}')
        lists[k] = e
    has_list = {b.key for b in BOOKLETS if b.lists}

    unit_spellings: dict[str, collections.Counter] = collections.defaultdict(collections.Counter)
    for r in parsed['rows']:
        unit_spellings[r['unit_code']][r['unit_name']] += 1
    for code, names in unit_spellings.items():
        if len(names) > 1 and code not in UNIT_NAMES:
            raise BuildError(f'unit {code} printed as {dict(names)}')
        if code in UNIT_NAMES and UNIT_NAMES[code] not in names:
            raise BuildError(f'unit {code}: {UNIT_NAMES[code]!r} is not one of {dict(names)}')

    booklets = {b.key: b for b in BOOKLETS}
    programs: list[dict] = []
    for r in parsed['rows']:
        b = booklets[r['booklet']]
        prov = province(r['province'])
        unit_name = UNIT_NAMES.get(r['unit_code'], r['unit_name'])
        if unit_name != r['unit_name']:
            notes['unit_spelling'].append((r['booklet'], r['page'], r['unit_code'], r['unit_name'], unit_name))
        field_name = r['field_name']
        entry = lists.get((r['booklet'], r['field_code']))
        if (r['booklet'], r['field_code']) in CUT_OFF_NAMES:
            if not entry or not entry['field_name'].startswith(field_name) or entry['field_name'] == field_name:
                raise BuildError(f'{r["booklet"]} {r["field_code"]}: {field_name!r} is not cut off from the list name')
            notes['cut_off_name'].append((r['booklet'], r['page'], r['field_code'], field_name, entry['field_name']))
            field_name = entry['field_name']
        part_time = field_name.endswith(PART_TIME)
        if PART_TIME in field_name and not part_time:
            raise BuildError(f'{PART_TIME} inside {field_name!r}')
        education_group = None
        if b.key in has_list:
            if entry is None:
                raise BuildError(f'{b.key} {r["field_code"]} is not in the booklet field list')
            base = field_name[:-len(PART_TIME)] if part_time else field_name
            if entry['field_name'] != base:
                notes['list_name'].append((b.key, r['page'], r['field_code'], base, entry['field_name']))
            education_group = GROUPS[entry['group']]
        exam_group = None
        if r.get('exam_group') is not None:
            if r['exam_group'] not in GROUPS:
                raise BuildError(f'unknown exam group {r["exam_group"]!r}')
            exam_group = GROUPS[r['exam_group']]
        programs.append({
            'booklet': b.key,
            'admission': b.basis,
            'level': b.level,
            'province': prov,
            'city': city(r['unit_code'], unit_name, prov),
            'unit_code': r['unit_code'],
            'unit_name': persian_digits(unit_name),
            'self_funded': is_self_funded(unit_name),
            'field_code': r['field_code'],
            'field_name': persian_digits(field_name),
            'part_time': part_time,
            'gender': r['gender'],
            'exam_group': exam_group,
            'education_group': education_group,
            'capacity_first': capacity(r['capacity_first']) if 'capacity_first' in r else None,
            'capacity_second': capacity(r['capacity_second']) if 'capacity_second' in r else None,
            'page': r['page'],
        })
    check(programs, notes)
    return programs, notes


def check(programs: list[dict], notes: dict) -> None:
    seen: dict[tuple, dict] = {}
    for p in programs:
        k = (p['booklet'], p['unit_code'], p['field_code'], p['part_time'])
        if k in seen:
            raise BuildError(f'duplicate programme {k}: pages {seen[k]["page"]} and {p["page"]}')
        seen[k] = p
        for name in ('unit_name', 'field_name', 'city'):
            text = p[name]
            if re.search(r'[0-9٠-٩]', text) or re.search(r'\s{2,}', text) or text != text.strip():
                raise BuildError(f'bad {name} {text!r}')
            if re.search(r'[يكة]', text):
                raise BuildError(f'Arabic letter in {name} {text!r}')
        if p['admission'] == 'exam':
            if p['exam_group'] is None or (p['capacity_first'] is None and p['capacity_second'] is None):
                raise BuildError(f'exam programme without group or capacity: {p}')
        elif p['capacity_first'] is not None or p['exam_group'] is not None:
            raise BuildError(f'records programme with exam columns: {p}')
    # one unit: one name, one province, one city
    by_unit: dict[str, set] = collections.defaultdict(set)
    for p in programs:
        by_unit[p['unit_code']].add((p['unit_name'], p['province'], p['city']))
    for code, places in by_unit.items():
        if len({x[0] for x in places}) > 1 or len({x[2] for x in places}) > 1:
            raise BuildError(f'unit {code} has {places}')
        if len({x[1] for x in places}) > 1:
            notes['unit_two_provinces'].append((code, sorted(places)))
    # one spelling per field (code + part time) across booklets
    by_field: dict[tuple, set] = collections.defaultdict(set)
    for p in programs:
        by_field[(p['field_code'], p['part_time'])].add(p['field_name'])
    for k, names in by_field.items():
        if len(names) > 1:
            notes['field_spellings'].append((k, sorted(names)))
    # one spelling per city per province
    cities = known_cities()
    for p in programs:
        canonical = cities[p['province']].get(key(p['city']))
        if canonical is not None and canonical != p['city']:
            raise BuildError(f'city {p["city"]!r} is spelled {canonical!r} elsewhere')


def write_gzip_jsonl(path: str, rows: list[dict]) -> None:
    with open(path, 'wb') as fh:
        with gzip.GzipFile(filename='', mode='wb', fileobj=fh, mtime=0) as gz:
            for row in rows:
                gz.write((json.dumps(row, ensure_ascii=False, sort_keys=True) + '\n').encode('utf-8'))


def sha256(path: str) -> str:
    h = hashlib.sha256()
    with open(path, 'rb') as fh:
        for chunk in iter(lambda: fh.read(1 << 20), b''):
            h.update(chunk)
    return h.hexdigest()


def write_review(review_dir: str, programs: list[dict], notes: dict, parsed: dict) -> str:
    from openpyxl import Workbook
    from openpyxl.styles import Font

    os.makedirs(review_dir, exist_ok=True)
    wb = Workbook()
    ws = wb.active
    ws.title = 'خلاصه'
    ws.sheet_view.rightToLeft = True
    ws.append(['دفترچه', 'فایل', 'صفحات جدول', 'تعداد رشته‌محل'])
    counts = collections.Counter(p['booklet'] for p in programs)
    for b in BOOKLETS:
        ws.append([BOOKLET_LABELS[b.key], b.file, f'{b.pages[0]}–{b.pages[1]}', counts[b.key]])
    ws.append(['جمع', '', '', len(programs)])

    ws = wb.create_sheet('رشته‌محل‌ها')
    ws.sheet_view.rightToLeft = True
    cols = ['booklet', 'page', 'province', 'city', 'unit_code', 'unit_name', 'self_funded', 'field_code', 'field_name',
            'part_time', 'gender', 'exam_group', 'education_group', 'capacity_first', 'capacity_second']
    ws.append(['دفترچه', 'صفحه', 'استان', 'شهر', 'کد محل', 'نام محل', 'خودگردان', 'کد رشته', 'نام رشته',
               'پاره‌وقت', 'جنس', 'گروه آزمایشی', 'گروه آموزشی', 'ظرفیت نیمسال اول', 'ظرفیت نیمسال دوم'])
    for p in programs:
        ws.append([BOOKLET_LABELS[p['booklet']] if c == 'booklet' else
                   ('بله' if p[c] else '') if c in ('self_funded', 'part_time') else p[c] for c in cols])
    ws.freeze_panes = 'A2'
    ws.auto_filter.ref = ws.dimensions

    ws = wb.create_sheet('واحدها')
    ws.sheet_view.rightToLeft = True
    ws.append(['کد محل', 'نام محل', 'استان', 'شهر', 'شهر چاپ نشده (استنباطی)', 'تعداد رشته‌محل', 'دفترچه‌ها'])
    units: dict[str, list[dict]] = collections.defaultdict(list)
    for p in programs:
        units[p['unit_code']].append(p)
    for code in sorted(units, key=int):
        ps = units[code]
        ws.append([code, ps[0]['unit_name'], '، '.join(sorted({p['province'] for p in ps})), ps[0]['city'],
                   'بله' if code in INFERRED else '', len(ps),
                   '، '.join(BOOKLET_LABELS[b] for b in sorted({p['booklet'] for p in ps}))])

    ws = wb.create_sheet('نکات دفترچه')
    ws.sheet_view.rightToLeft = True
    ws.append(['موضوع', 'جزئیات'])
    for b, page, code, printed, used in notes.get('unit_spelling', [])[:0]:
        pass
    spelled = collections.defaultdict(set)
    for b, page, code, printed, used in notes.get('unit_spelling', []):
        spelled[(code, printed, used)].add(f'{BOOKLET_LABELS[b]} ص{page}')
    for (code, printed, used), where in sorted(spelled.items()):
        ws.append(['نام واحد به دو شکل چاپ شده', f'کد {code}: «{printed}» در {"، ".join(sorted(where))}؛ در بقیه «{used}» (همین ثبت شد)'])
    for b, page, code, printed, used in notes.get('cut_off_name', []):
        ws.append(['نام رشته در جدول بریده چاپ شده', f'{BOOKLET_LABELS[b]} ص{page} کد {code}: «{printed}»؛ نام کامل از فهرست رشته‌های همان دفترچه: «{used}»'])
    listed = collections.defaultdict(set)
    for b, page, code, base, lname in notes.get('list_name', []):
        listed[(b, code, base, lname)].add(page)
    for (b, code, base, lname), pages in sorted(listed.items()):
        ws.append(['نام رشته در جدول و فهرست رشته‌ها فرق دارد', f'{BOOKLET_LABELS[b]} کد {code}: جدول «{base}» (ص{"، ".join(map(str, sorted(pages)))})، فهرست «{lname}»؛ نام جدول ثبت شد'])
    for code, places in notes.get('unit_two_provinces', []):
        ws.append(['واحد زیر دو استان چاپ شده', f'کد {code}: ' + ' / '.join(f'{n} ({p})' for n, p, _ in places)])
    for k, names in notes.get('field_spellings', []):
        ws.append(['یک کد رشته با چند نام در دفترچه‌های مختلف', f'کد {k[0]}{" پاره‌وقت" if k[1] else ""}: ' + ' / '.join(f'«{n}»' for n in names)])
    for code in sorted(INFERRED, key=int):
        name = units[code][0]['unit_name'] if code in units else '?'
        ws.append(['شهر واحد در نامش چاپ نشده (استنباطی)', f'کد {code} «{name}» ← {CITY_OF_UNIT[code]}'])
    for b, words in parsed.get('phantom_spaces', {}).items():
        for w, n in sorted(words.items()):
            ws.append(['فاصلهٔ زیر حرف (نیم‌فاصله شد)', f'{BOOKLET_LABELS[b]}: {w} ×{n}'])
    for row in wb['نکات دفترچه'].iter_rows(min_row=1, max_row=1):
        for c in row:
            c.font = Font(bold=True)
    path = os.path.join(review_dir, 'azad-1405-review.xlsx')
    wb.save(path)
    return path


def main() -> None:
    parsed_path, booklet_dir, out_dir = sys.argv[1:4]
    review_dir = sys.argv[4] if len(sys.argv) > 4 else None
    parsed = json.load(open(parsed_path, encoding='utf-8'))
    if parsed['year'] != YEAR:
        raise BuildError(f'parsed year {parsed["year"]}')
    programs, notes = build(parsed)
    os.makedirs(out_dir, exist_ok=True)
    data = os.path.join(out_dir, 'programs.jsonl.gz')
    write_gzip_jsonl(data, programs)
    counts = collections.Counter(p['booklet'] for p in programs)
    manifest = {
        'format': 'azad-programs-v1',
        'year': YEAR,
        'booklets': [{
            'key': b.key,
            'label': BOOKLET_LABELS[b.key],
            'level': b.level,
            'admission': b.basis,
            'source_file': b.file,
            'source_sha256': sha256(os.path.join(booklet_dir, b.file)),
            'pages': list(b.pages),
            'programs': counts[b.key],
        } for b in BOOKLETS],
        'programs': len(programs),
        'programs_file': 'programs.jsonl.gz',
        'programs_sha256': sha256(data),
        'counts': {
            'provinces': len({p['province'] for p in programs}),
            'cities': len({(p['province'], p['city']) for p in programs}),
            'units': len({p['unit_code'] for p in programs}),
            'fields': len({(p['field_code'], p['part_time']) for p in programs}),
        },
    }
    with open(os.path.join(out_dir, 'manifest.json'), 'w', encoding='utf-8') as fh:
        json.dump(manifest, fh, ensure_ascii=False, indent=2)
        fh.write('\n')
    print(json.dumps(manifest, ensure_ascii=False, indent=1))
    for k, v in notes.items():
        print(k, len(v), file=sys.stderr)
    if review_dir:
        print('review:', write_review(review_dir, programs, notes, parsed), file=sys.stderr)


if __name__ == '__main__':
    main()
