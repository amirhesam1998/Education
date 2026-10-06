"""Build the 1405 catalogue data from the parsed booklet.

Usage: python3 build_1405.py <parsed.json> <booklet.pdf> <out_dir> [review_dir]
                             [--and <parsed.json> <booklet.pdf> <out_dir> [review_dir]] ...

<parsed.json> is the output of parse_1405.py. Every row gets its place
(province, city, institution, campus), its field, course and admission type
and its texts. Anything that cannot be resolved stops the build.

<out_dir> is database/seeders/data/booklets/<year>/<group> and holds academic_fields.json
(the group's field list, see field_list_1405.py);
programs.jsonl.gz and manifest.json are written next to it and are what
`php artisan education:import-booklets` loads.

The groups of a year share cities, institutions, campuses and fields in the catalogue, so every
build is checked against the other groups' committed booklets of the year (one spelling, one place,
one home city per institution). A change to something they share (a CITY_OF entry, an alias, the
place rules) has to be built for every group at once: name each group with --and.
"""
from __future__ import annotations

import collections
import gzip
import hashlib
import json
import os
import re
import sys
from dataclasses import replace

from places_1405 import CITIES, PROVINCES, Place, Places, UnknownPlace, key, province, split_institution, split_study_place, tidy

TITLE_PARTS = {'health', 'science', 'nonprofit', 'payame_noor'}
INSTITUTION_CELL_PARTS = {'health_commitment_council', 'health_commitment_justice', 'health_commitment_native', 'quota'}
# Parts whose sections are reserved for (or give priority to) the natives of one province,
# who may study in another one: the service-commitment parts, the native quotas and Farhangian.
NATIVE_PARTS = INSTITUTION_CELL_PARTS | {'teacher'}
# Quota sections that name a county instead of its province.
NATIVE_COUNTIES = {'بشاگرد': 'هرمزگان'}


class BuildError(Exception):
    pass


def title_place(places: Places, title: str) -> Place:
    m = re.match(r'^دانشگاه پیام نور استان (.+?) - (.+)$', title)
    if m:
        return places.locate(province(m.group(1)), 'دانشگاه پیام نور', tidy(m.group(2)), None)
    m = re.match(r'^استان (.+?) - (.+)$', title)
    if not m:
        raise BuildError(f'section title without a place: {title!r}')
    rest, study_place = split_study_place(m.group(2))
    institution, campus, suffix = split_institution(rest)
    if study_place and campus:
        raise BuildError(f'two campuses in {title!r}')
    return places.locate(province(m.group(1)), institution, tidy(study_place) if study_place else campus, suffix)


_LETTER = '[آ-ی]'
_GAP = '[\\s\u200c]*'
_PROVINCE_RE = re.compile(
    rf'(?<!{_LETTER})استان[\s\u200c]+(?:محروم[\s\u200c]+)?('
    + '|'.join(_GAP.join(map(re.escape, p.replace(' ', ''))) for p in sorted(PROVINCES, key=len, reverse=True))
    + rf')(?!{_LETTER})')
_COUNTY_RE = re.compile(rf'(?<!{_LETTER})شهرستان[\s\u200c]+({"|".join(NATIVE_COUNTIES)})(?!{_LETTER})')


def native_province(title: str) -> str:
    """The one province whose natives a section is reserved for or gives priority to, read from its title."""
    found = {province(m.group(1)) for m in _PROVINCE_RE.finditer(title)}
    found |= {NATIVE_COUNTIES[m.group(1)] for m in _COUNTY_RE.finditer(title)}
    if len(found) != 1:
        raise BuildError(f'section title names {sorted(found) or "no"} province(s): {title!r}')
    return found.pop()


class InstitutionCells:
    """Reads "INSTITUTION (محل تحصیل CAMPUS) - notes" cells against the institutions the titles name."""

    START = 'شروع تحصیل مهرماه 1406'
    UNITS = {'دانشگاه ملی مهارت', 'دانشگاه آزاد اسلامی'}   # "INSTITUTION - UNIT - notes"

    def __init__(self, places: Places, known: dict[tuple[str, str | None], Place],
                 siblings: dict[tuple[str, str | None], Place] | None = None) -> None:
        self.places = places
        self.known = known
        self.siblings = siblings or {}
        names = {inst for inst, _ in known} | {inst for inst, _ in self.siblings}
        self.names = sorted(names, key=lambda n: -len(key(n)))

    def read(self, cell: str) -> tuple[Place, str | None]:
        text = tidy(cell)
        prefix = None
        if text.startswith(self.START):
            prefix, text = self.START, text[len(self.START):].lstrip(' -')
        institution, rest = self._institution(text)
        campus = city_text = None
        m = re.match(r'^- ([^-()]+?) (\(محل تحصیل.*)$', rest)
        if m:
            # "INSTITUTION - CITY (محل تحصیل CAMPUS) - notes": the city of the study place, checked below
            city_text, rest = m.group(1), m.group(2)
        if rest.startswith('(محل تحصیل'):
            _, campus = split_study_place(rest)
            rest = rest[rest.index(campus) + len(campus):].lstrip(') ')
        elif institution in self.UNITS and rest.startswith('- '):
            campus, _, rest = rest[2:].partition(' - ')
            rest = '- ' + rest if rest else ''
        if rest and not rest.startswith('- '):
            raise BuildError(f'unexpected text after the institution in {cell!r}: {rest!r}')
        notes = printed_tail(cell, rest[2:].strip()) if rest else None
        notes = ' - '.join(n for n in (prefix, notes) if n) or None
        campus = tidy(campus) if campus else None
        place = self.known.get((institution, campus))
        if place is None or place.how == 'ambiguous':
            try:
                place = self.places.locate(None, institution, campus, None)
            except UnknownPlace:
                # a faculty named without its city ("دانشکده بهداشت") is in the institution's own city;
                # failing that, an institution only another group's titles name is where they put it
                home = self.known.get((institution, None))
                sibling = self.siblings.get((institution, campus))
                if campus is not None and home is not None and home.how != 'ambiguous':
                    place = Place(home.province, home.city, institution, campus, 'institution')
                elif sibling is not None and sibling.how != 'ambiguous':
                    place = sibling
                else:
                    raise
        if city_text and self.places.city(place.province, city_text) != place.city:
            raise BuildError(f'{city_text!r} before the study place is not its city {place.city} in {cell!r}')
        return place, notes

    def _institution(self, text: str) -> tuple[str, str]:
        k = key(text)
        for name in self.names:
            kn = key(name)
            if not k.startswith(kn):
                continue
            # cut the printed text where the institution name ends
            seen, i = 0, 0
            while seen < len(kn):
                if key(text[i]):
                    seen += 1
                i += 1
            rest = text[i:].strip()
            if rest == '' or rest.startswith(('(', '- ')):
                return name, rest
        raise BuildError(f'no known institution at the start of {text!r}')


def build_places(raw: dict, siblings: list[dict] = ()) -> tuple[dict[str, Place], dict[str, dict]]:
    """Place of every program, and the texts that came out of its place cell (notes, admission scope).

    A place cell is read against the institutions and campuses this booklet's own titles name. The
    other groups' booklets of the year (siblings) are the last resort, for an institution that only
    their titles name.
    """
    places = Places()
    sections = {s['id']: s for s in raw['sections']}
    by_code: dict[str, Place] = {}
    section_place: dict[int, Place] = {}
    errors: list[str] = []
    for s in raw['sections']:
        if s['part'] in TITLE_PARTS:
            try:
                section_place[s['id']] = title_place(places, s['title'])
            except (UnknownPlace, BuildError) as e:
                errors.append(f'{s["first_page"]}: {e}')
    if errors:
        raise BuildError('\n'.join(errors))

    known: dict[tuple[str, str | None], Place] = {}
    for p in section_place.values():
        k = (p.institution, p.campus)
        if k in known and (known[k].province, known[k].city) != (p.province, p.city):
            known[k] = Place(p.province, p.city, p.institution, p.campus, 'ambiguous')
        else:
            known.setdefault(k, p)
    sibling_cities: dict[tuple[str, str | None], set] = collections.defaultdict(set)
    for p in siblings:
        sibling_cities[(p['institution'], p['campus'])].add((p['province'], p['city']))
    sibling_places = {
        (inst, campus): Place(*min(where), inst, campus, 'sibling' if len(where) == 1 else 'ambiguous')
        for (inst, campus), where in sibling_cities.items()
    }
    cells = InstitutionCells(places, known, sibling_places)

    extras: dict[str, dict] = {}
    for r in raw['rows']:
        part = sections[r['section_id']]['part']
        try:
            if part in TITLE_PARTS:
                by_code[r['code']] = section_place[r['section_id']]
                extras[r['code']] = {'notes': r['cells']['notes'] or None}
            elif part in INSTITUTION_CELL_PARTS:
                place, notes = cells.read(r['cells']['institution_notes'])
                by_code[r['code']] = place
                extras[r['code']] = {'notes': drop_leading_city(places, place, notes)}
            elif part == 'teacher':
                place, scope = teacher_place(places, known, sections[r['section_id']], r['cells']['campus_scope'])
                by_code[r['code']] = place
                extras[r['code']] = {'notes': None, 'scope': scope or None}
            else:
                raise BuildError(f'unknown part {part}')
        except (UnknownPlace, BuildError) as e:
            errors.append(f'{r["code"]} p{r["page"]}: {e}')
    if errors:
        raise BuildError('\n'.join(errors))
    return by_code, extras


def printed_tail(cell: str, tail: str) -> str:
    """The end of the printed cell that tidy() made into tail, with its dashes and spaces as printed (zaban p. 310 "ایرانشهر- ممنوعیت")."""
    want, i = len(re.sub(r'\s', '', tail)), len(cell)
    while want:
        i -= 1
        want -= not cell[i].isspace()
    printed = cell[i:].strip()
    if tidy(printed) != tail:
        raise BuildError(f'cannot find {tail!r} at the end of {cell!r}')
    return printed


def drop_leading_city(places: Places, place: Place, notes: str | None) -> str | None:
    """"کرج - فاقد خوابگاه" after the institution: the city is the program's city, not a note."""
    if not notes:
        return notes
    first, rest = (notes, '') if (m := re.match(r'(.*?)\s*[–—-]\s*(.*)$', notes, re.S)) is None else m.groups()
    if len(first) <= 40 and places.city(place.province, first) is not None:
        if places.city(place.province, first) != place.city:
            raise BuildError(f'notes start with {first!r} but the program is in {place.city}')
        return rest.strip() or None
    return notes


def teacher_place(places: Places, known: dict, section: dict, cell: str) -> tuple[Place, str]:
    campus_text, _, scope = cell.partition('/')
    campus_text, scope = tidy(campus_text), scope.strip()
    if campus_text.startswith('پردیس '):
        institution, campus, suffix = 'دانشگاه فرهنگیان', campus_text, None
    else:
        institution, campus, suffix = split_institution(campus_text)
    place = known.get((institution, campus))
    if place is None or place.how == 'ambiguous':
        home = province(section['title'].replace('مخصوص متقاضیان بومی استان ', ''))
        try:
            place = places.locate(home, institution, campus, suffix)
        except UnknownPlace:
            place = places.locate(None, institution, campus, suffix)
    return place, scope




# ---------------------------------------------------------------------------
# Programs

# Printed course (دوره) -> course type of the catalogue. The printed text is kept as well.
COURSE_TYPES = {
    'روزانه': 'day',
    'نوبت دوم': 'evening',
    'شهریه پرداز': 'tuition',
    'پردیس خودگردان': 'tuition',
    'مجازی': 'virtual',
    'روزانه - غیردولتی': 'day',       # free of charge ("تحصیل رایگان") at non-state universities
    'آزاد تمام وقت': 'azad',
    'خودگردان آزاد': 'azad',
}
# Parts whose tables have no course column.
PART_COURSE_TYPES = {
    'nonprofit': 'nonprofit',
    'payame_noor': 'payame_noor',
    'health_commitment_council': 'commitment',
    'health_commitment_justice': 'commitment',
    'health_commitment_native': 'commitment',
    'quota': 'day',
    'teacher': 'teacher',
}
ADMISSION_TYPES = {'با آزمون': 'with_exam', 'صرفا با سوابق تحصیلی': 'academic_records'}

# One institution printed under two names; the name the section titles use wins.
INSTITUTION_ALIASES = {
    'دانشگاه علوم پزشکی و خدمات بهداشتی درمانی خراسان شمالی': 'دانشگاه علوم پزشکی و خدمات بهداشتی درمانی بجنورد',
    'دانشگاه علوم پزشکی وخدمات بهداشتی درمانی جندی‌شاپور اهواز': 'دانشگاه علوم پزشکی و خدمات بهداشتی درمانی جندی شاپور اهواز',
}

CITY_HOW = {
    'title': 'از عنوان جدول دفترچه',
    'campus': 'از نام محل تحصیل',
    'name': 'از نام دانشگاه',
    'hand': 'تعیین دستی (بررسی‌شده)',
    'institution': 'شهر مرکز دانشگاه',
    'sibling': 'از دفترچه گروه دیگر',
}

# A field the booklet prints in two spellings -> the spelling most of its tables use. The printed
# title stays in the program's cells.
FIELD_SPELLINGS = {
    'مهندسی نقشه برداری': 'مهندسی نقشه‌برداری',       # riazi: the Farhangian/Rajaee tables only
    # ensani: one Bashagard quota row (p. 146)
    'علوم و مهندسی صنایع غذایی (این رشته متعلق به گروه کشاورزی است)': 'علوم و مهندسی صنایع غذایی(این رشته متعلق به گروه کشاورزی است)',
}

# Exam groups: the folder name under database/seeders/data/booklets/<year>/ and the group's name.
GROUPS = {'tajrobi': 'تجربی', 'riazi': 'ریاضی', 'ensani': 'انسانی', 'zaban': 'زبان', 'honar': 'هنر'}

# Printed table titles that name a field of the group's field list differently (title -> order).
FIELD_LIST_ALIASES = {'tajrobi': {
    'کارشناسی ارشد پیوسته الهیات و معارف اسلامی و ارشاد گرایش فقه و مبانی حقوق اسلامی': 6,
    'کارشناسی ارشد پیوسته الهیات و معارف اسلامی و ارشاد گرایش قرآن و حدیث': 6,
    'کارشناسی ارشد پیوسته علوم قضایی': 7,
    'علوم و مهندسی صنایع غذایی(این رشته متعلق به گروه کشاورزی است)': 62,
    'مددکاری اجتماعی (ویژه وزارت بهداشت)': 85,
    'کاردانی بهداشت عمومی گرایش بهداشت خانواده': 125,
    'کاردانی بهداشت عمومی گرایش مبارزه با بیماری‌ها': 125,
    'کاردانی فنی باغبانی - تولید و فرآوری خرما': 136,
}, 'riazi': {
    'دکتری پیوسته بیوتکنولوژی': 1,
    'دکتری پیوسته فیزیک': 2,
    'کارشناسی ارشد پیوسته الهیات و معارف اسلامی و ارشاد گرایش فقه و مبانی حقوق اسلامی': 3,
    'کارشناسی ارشد پیوسته الهیات و معارف اسلامی و ارشاد گرایش قرآن و حدیث': 3,
    'کارشناسی ارشد پیوسته علوم قضایی': 4,
    'کارشناسی ارشد پیوسته مهندسی پزشکی گرایش بیوالکتریک': 5,
    'اقتصاد (برنامه درسی خاص دانشگاه)': 9,
    'زبان و ادبیات عربی (برنامه درسی خاص دانشگاه)': 23,
    'علوم قرآن و حدیث (برنامه درسی خاص دانشگاه)': 31,
    'فقه و حقوق اسلامی (برنامه درسی خاص دانشگاه شهید مطهری)': 38,
    'فقه و مبانی حقوق اسلامی (برنامه درسی خاص دانشگاه)': 41,
    'فلسفه و کلام اسلامی (برنامه درسی خاص دانشگاه)': 44,
    'مدیریت فرهنگی هنری (ویژه دانشکده آموزش عالی تربیت مربی عقیدتی سیاسی سپاه)': 57,
    'مهندسی ایمنی و بازرسی فنی در صنایع نفت و گاز': 75,
    'مهندسی مکانیک بیوسیستم (این رشته متعلق به گروه کشاورزی است)': 107,
    'کاردانی نقشه‌برداری - ژئودزی': 149,
}, 'ensani': {
    'کارشناسی ارشد پیوسته علوم قضایی': 1,
    'کارشناسی ارشد پیوسته الهیات و معارف اسلامی و ارشاد گرایش فقه و مبانی حقوق اسلامی': 2,
    'کارشناسی ارشد پیوسته الهیات و معارف اسلامی و ارشاد گرایش قرآن و حدیث': 2,
    'اقتصاد (برنامه درسی خاص این دانشگاه)': 6,
    'تاریخ و تمدن ملل اسلامی': 17,                         # the list prints "تاریخ تمدن ملل اسلامی"
    'جامعه‌شناسی (برنامه درسی خاص این دانشگاه)': 20,
    'حقوق (برنامه درسی خاص این دانشگاه)': 24,
    'زبان و ادبیات عربی (برنامه درسی خاص این دانشگاه)': 31,
    'علوم قرآن و حدیث (برنامه درسی خاص این دانشگاه)': 44,
    'فقه و حقوق اسلامی (برنامه درسی خاص این دانشگاه)': 49,
    'فقه و مبانی حقوق اسلامی (برنامه درسی خاص این دانشگاه)': 53,
    'فلسفه و کلام اسلامی (برنامه درسی خاص این دانشگاه)': 57,
    'مدیریت آموزشی(با رویکرد آموزش سازمانی)': 62,          # the list prints "مدیریت آموزشی/ رویکرد آموزش سازمانی/"
    'مدیریت فرهنگی هنری (ویژه دانشکده آموزش عالی تربیت مربی عقیدتی سیاسی سپاه)': 70,
}, 'zaban': {
}, 'honar': {
}}

PERSIAN_DIGITS = str.maketrans('0123456789٠١٢٣٤٥٦٧٨٩', '۰۱۲۳۴۵۶۷۸۹۰۱۲۳۴۵۶۷۸۹')
ASCII_DIGITS = str.maketrans('۰۱۲۳۴۵۶۷۸۹٠١٢٣٤٥٦٧٨٩', '01234567890123456789')


def fa(text: str | None) -> str | None:
    """Free text as printed, with every digit in Persian (the text layer mixes 5 and ۵)."""
    return text.translate(PERSIAN_DIGITS) if text else text


def number(cell: str) -> int | None:
    cell = cell.translate(ASCII_DIGITS).strip()
    if cell in ('', '-'):
        return None
    if not cell.isdigit():
        raise BuildError(f'not a number: {cell!r}')
    return int(cell)


def gender(cell: str, word: str) -> tuple[bool, int | None]:
    """'زن' -> accepted; '-' -> not accepted; a number -> that many seats for this gender."""
    if cell == word:
        return True, None
    if cell in ('', '-'):
        return False, None
    n = number(cell)
    return n > 0, n


def php_lookup(text: str) -> str:
    """PersianTextNormalizer::lookup, so names that collide in the app's unique keys are caught here."""
    text = text.strip().translate(str.maketrans({'ي': 'ی', 'ى': 'ی', 'ك': 'ک', 'ة': 'ه', 'ۀ': 'ه', 'ـ': None,
                                                 ' ': ' ', '​': ' ', '‌': ' ', '‍': ' '}))
    text = re.sub('[‎‏‪-‮]', '', text)
    text = re.sub(r'\s*([،؛:])\s*', r'\1 ', text)
    return re.sub(r'\s+', ' ', text).strip().lower()


def section_note(notes: list[str]) -> str | None:
    """Printed lines of the note under a section title: wrapped lines joined, '*' starts a new paragraph."""
    paragraphs: list[str] = []
    for line in notes:
        if line.startswith('*') or not paragraphs:
            paragraphs.append(line)
        else:
            paragraphs[-1] += ' ' + line
    return fa('\n'.join(paragraphs)) if paragraphs else None


class FieldList:
    """The field list of the group (academic_fields.json), matched to the printed table titles."""

    def __init__(self, path: str, group: str) -> None:
        self.fields = json.load(open(path, encoding='utf-8'))['fields']
        self.aliases = FIELD_LIST_ALIASES[group]
        self.by_key: dict[str, dict] = {}
        for f in self.fields:
            for name in (f['name'], f'{f["degree_level"]} {f["name"]}'):
                self.by_key.setdefault(self._key(name), f)
        self.by_order = {f['order']: f for f in self.fields}

    @staticmethod
    def _key(name: str) -> str:
        return key(re.sub(r'[()]', '', name))

    def find(self, title: str) -> dict | None:
        if title in self.aliases:
            return self.by_order[self.aliases[title]]
        return self.by_key.get(self._key(title))


def build_programs(raw: dict, field_list: FieldList, siblings: list[dict] = ()) -> list[dict]:
    sections = {s['id']: s for s in raw['sections']}
    by_code, extras = build_places(raw, siblings)
    natives = {s['id']: native_province(tidy(s['title'])) for s in raw['sections'] if s['part'] in NATIVE_PARTS}
    programs: list[dict] = []
    errors: list[str] = []
    for n, r in enumerate(raw['rows'], 1):
        s = sections[r['section_id']]
        c = r['cells']
        place = by_code[r['code']]
        if place.institution in INSTITUTION_ALIASES:
            place = replace(place, institution=INSTITUTION_ALIASES[place.institution])
        try:
            if s['part'] == 'teacher':
                course = None
                course_type = PART_COURSE_TYPES['teacher']
                method, admission_type = None, 'with_exam'
                first, second = number(c['capacity']), None
                if c['gender'] not in ('زن', 'مرد'):
                    raise BuildError(f'gender {c["gender"]!r}')
                accepts_female, accepts_male = c['gender'] == 'زن', c['gender'] == 'مرد'
                female_capacity = male_capacity = None
                scope = f'{s["title"]} - {extras[r["code"]]["scope"]}' if extras[r['code']]['scope'] else s['title']
                service = c['service_location'] or None
            else:
                course = c.get('course')
                course_type = COURSE_TYPES[course] if course else PART_COURSE_TYPES[s['part']]
                method = c['method']
                admission_type = ADMISSION_TYPES[method]
                first, second = number(c['capacity_first']), number(c['capacity_second'])
                accepts_female, female_capacity = gender(c['female'], 'زن')
                accepts_male, male_capacity = gender(c['male'], 'مرد')
                scope = s['title'] if s['part'] not in TITLE_PARTS else None
                service = None
            if r['code'] != c['code'].translate(ASCII_DIGITS) or not re.fullmatch(r'\d{5}', r['code']):
                raise BuildError(f'code cell {c["code"]!r}')
            if not (accepts_female or accepts_male):
                raise BuildError('accepts neither gender')
            if first is None and second is None:
                raise BuildError('no capacity')
        except (BuildError, KeyError) as e:
            errors.append(f'{r["code"]} p{r["page"]}: {e!r}')
            continue
        listed = field_list.find(c['title'])
        programs.append({
            'code': r['code'],
            'source_row': n,
            'page': r['page'],
            'printed_page': r['printed_page'],
            'part': s['part'],
            'part_heading': s['part_heading'],
            'subpart': s['subpart'],
            'section': fa(tidy(s['title'])),
            'province': place.province,
            'native_province': natives.get(s['id']),
            'city': place.city,
            'city_how': CITY_HOW[place.how],
            'institution': place.institution,
            'campus': place.campus,
            'field': FIELD_SPELLINGS.get(c['title'], c['title']),
            'course': course,
            'course_type': course_type,
            'method': method,
            'admission_type': admission_type,
            'capacity_first': first,
            'capacity_second': second,
            'accepts_female': accepts_female,
            'accepts_male': accepts_male,
            'female_capacity': female_capacity,
            'male_capacity': male_capacity,
            'description': fa(extras[r['code']]['notes']),
            'admission_period': fa(s['period']),
            'admission_scope': fa(scope),
            'service_location': fa(service),
            'section_note': section_note(s['notes']),
            'field_list': {k: listed[k] for k in ('order', 'degree_level', 'selection_type', 'field_row_code', 'gender_min_30_percent_note')} if listed else None,
            'cells': c,
        })
    if errors:
        raise BuildError('\n'.join(errors))
    return programs


def check(programs: list[dict]) -> dict[str, list]:
    """Hard rules stop the build; the returned lists go to the review files."""
    errors: list[str] = []
    codes = collections.Counter(p['code'] for p in programs)
    errors += [f'code {c} printed {n} times' for c, n in codes.items() if n > 1]

    def one_spelling(label: str, keyed: dict[tuple, set]) -> None:
        for k, names in keyed.items():
            if len(names) > 1:
                errors.append(f'{label} {k}: {sorted(names)}')

    cities, institutions, campuses, fields = (collections.defaultdict(set) for _ in range(4))
    campus_place = collections.defaultdict(set)
    for p in programs:
        if p['province'] not in PROVINCES or p['city'] not in CITIES[p['province']]:
            errors.append(f'{p["code"]}: city {p["province"]}/{p["city"]} is not in CITIES')
        cities[(p['province'], php_lookup(p['city']))].add(p['city'])
        institutions[php_lookup(p['institution'])].add(p['institution'])
        fields[php_lookup(p['field'])].add(p['field'])
        if p['campus']:
            campuses[(p['institution'], php_lookup(p['campus']))].add(p['campus'])
            campus_place[(p['institution'], p['campus'])].add((p['province'], p['city']))
        for name in (p['field'], p['institution'], p['campus'] or '', p['city']):
            if re.search(r'[0-9۰-۹]', name) or name != tidy(name) and name not in (p['field'],):
                errors.append(f'{p["code"]}: name {name!r} has digits or loose spacing')
    one_spelling('city', cities)
    one_spelling('institution', institutions)
    one_spelling('campus', campuses)
    one_spelling('field', fields)
    for k, v in campus_place.items():
        if len(v) > 1:
            errors.append(f'campus {k} in {sorted(v)}')
    keys = collections.defaultdict(set)
    for p in programs:
        keys[key(p['institution'])].add(p['institution'])
    one_spelling('institution key', keys)
    if errors:
        raise BuildError('\n'.join(errors))

    sums = []
    for p in programs:
        if p['female_capacity'] is not None or p['male_capacity'] is not None:
            total = (p['capacity_first'] or 0) + (p['capacity_second'] or 0)
            by_gender = (p['female_capacity'] or 0) + (p['male_capacity'] or 0)
            if total != by_gender:
                sums.append((p['code'], p['page'], total, p['female_capacity'], p['male_capacity']))
    return {'capacity_mismatch': sums}


def institution_homes(programs: list[dict]) -> dict[str, tuple[str, str] | None]:
    """An institution's own city: where its programs without a campus are, when that is one city.

    Called with the programs of every group of the year: the catalogue holds one row per
    institution, which every booklet import rewrites, so all groups must give it the same city.
    """
    own, every = collections.defaultdict(set), collections.defaultdict(set)
    for p in programs:
        every[p['institution']].add((p['province'], p['city']))
        if not p['campus']:
            own[p['institution']].add((p['province'], p['city']))
    homes = {}
    for inst, places in every.items():
        candidates = own[inst] or places
        homes[inst] = next(iter(candidates)) if len(candidates) == 1 else None
    return homes


def read_committed(year_dir: str) -> dict[str, list[dict]]:
    """Programs of every group's committed booklet of a year, checked against its manifest."""
    groups: dict[str, list[dict]] = {}
    for group in sorted(GROUPS):
        folder = os.path.join(year_dir, group)
        if not os.path.isfile(os.path.join(folder, 'manifest.json')):
            continue
        manifest = json.load(open(os.path.join(folder, 'manifest.json'), encoding='utf-8'))
        path = os.path.join(folder, manifest['programs_file'])
        if sha256(path) != manifest['programs_sha256']:
            raise BuildError(f'{path} does not match its manifest')
        with gzip.open(path, 'rt', encoding='utf-8') as fh:
            groups[group] = [json.loads(line) for line in fh]
    return groups


def check_groups(groups: dict[str, list[dict]]) -> None:
    """The catalogue shares cities, institutions, campuses and fields between the groups of a year:
    each needs one spelling, a campus one city and an institution one home city in every booklet.
    A code printed in several booklets (the records-only codes are in all of them) is one study place."""
    errors: list[str] = []
    rows = [(group, p) for group, programs in sorted(groups.items()) for p in programs]

    def names(p: dict) -> list[tuple[str, tuple, str]]:
        found = [('city', (p['province'], p['city']), p['city']), ('institution', (p['institution'],), p['institution']),
                 ('field', (p['field'],), p['field'])]
        if p['campus']:
            found.append(('campus', (p['institution'], p['campus']), p['campus']))
        return found

    for normalize in (php_lookup, key):
        seen: dict[tuple, dict[str, str]] = collections.defaultdict(dict)
        for group, p in rows:
            for label, k, name in names(p):
                seen[(label, *map(normalize, k))].setdefault(name, group)
        errors += [f'{k[0]} spelled {spellings}' for k, spellings in seen.items() if len(spellings) > 1]
    where: dict[tuple, dict] = collections.defaultdict(dict)
    homes: dict[str, dict] = collections.defaultdict(dict)
    for group, p in rows:
        if p['campus']:
            where[(p['institution'], p['campus'])].setdefault((p['province'], p['city']), group)
        homes[p['institution']].setdefault((p['institution_province'], p['institution_city']), group)
    errors += [f'campus {k} in {v}' for k, v in where.items() if len(v) > 1]
    places: dict[str, dict[tuple, str]] = collections.defaultdict(dict)
    for group, p in rows:
        places[p['code']].setdefault((p['province'], p['city'], p['institution'], p['campus']), group)
    errors += [f'code {k} is at {v}' for k, v in places.items() if len(v) > 1]
    errors += [f'institution {k} has home cities {v}' for k, v in homes.items() if len(v) > 1]
    if errors:
        raise BuildError('\n'.join(errors) + '\n(build the groups that disagree together, with --and)')


def write_gzip_jsonl(path: str, rows: list[dict]) -> None:
    body = ''.join(json.dumps(r, ensure_ascii=False, sort_keys=True) + '\n' for r in rows).encode('utf-8')
    with open(path, 'wb') as fh:
        with gzip.GzipFile(filename='', mode='wb', fileobj=fh, mtime=0) as gz:
            gz.write(body)


def sha256(path: str) -> str:
    h = hashlib.sha256()
    with open(path, 'rb') as fh:
        for chunk in iter(lambda: fh.read(1 << 20), b''):
            h.update(chunk)
    return h.hexdigest()


def build(raw: dict, field_list: FieldList, siblings: list[dict]) -> tuple[list[dict], dict]:
    programs = build_programs(raw, field_list, siblings)
    return programs, check(programs)


def main() -> None:
    jobs, args = [], sys.argv[1:]
    while args:
        if jobs:
            if args[0] != '--and':
                sys.exit(__doc__)
            args = args[1:]
        n = 4 if len(args) > 3 and args[3] != '--and' else 3
        if len(args) < 3:
            sys.exit(__doc__)
        parsed, pdf, out_dir, review = (args[:n] + [None])[:4]
        args = args[n:]
        out_dir = os.path.normpath(out_dir)
        group = os.path.basename(out_dir)
        if group not in GROUPS:
            sys.exit(f'unknown group folder {group!r}; known: {sorted(GROUPS)}')
        jobs.append({'group': group, 'year_dir': os.path.dirname(out_dir), 'out_dir': out_dir, 'pdf': pdf, 'review': review,
                     'raw': json.load(open(parsed, encoding='utf-8')),
                     'field_list': FieldList(os.path.join(out_dir, 'academic_fields.json'), group)})
    if not jobs:
        sys.exit(__doc__)
    year_dir = jobs[0]['year_dir']
    if len({j['year_dir'] for j in jobs}) != 1 or len({j['group'] for j in jobs}) != len(jobs):
        sys.exit('build each group of one year once')
    year = int(os.path.basename(year_dir))
    try:
        groups = read_committed(year_dir)
        # Each group's place cells may use the other groups' places, so build until the places settle.
        built: dict[str, list[dict]] = {}
        for _ in range(3):
            fresh = {}
            for job in jobs:
                siblings = [p for g, programs in {**groups, **built}.items() if g != job['group'] for p in programs]
                fresh[job['group']] = build(job['raw'], job['field_list'], siblings)
            settled = built and all(fresh[g][0] == built[g] for g in built)
            built = {g: programs for g, (programs, _) in fresh.items()}
            findings = {g: f for g, (_, f) in fresh.items()}
            if settled or len(jobs) == 1:
                break
        else:
            raise BuildError('the groups\' places do not settle')
        groups.update(built)
        homes = institution_homes([p for programs in groups.values() for p in programs])
        for programs in built.values():
            for p in programs:
                home = homes[p['institution']]
                p['institution_province'], p['institution_city'] = home if home else (None, None)
        check_groups(groups)
    except BuildError as e:
        print(e)
        sys.exit(1)

    for job in jobs:
        programs = built[job['group']]
        write_booklet(job, year, programs)
        if job['review']:
            write_review(job['review'], programs, findings[job['group']], job['field_list'])
        print('capacity mismatches:', findings[job['group']]['capacity_mismatch'])


def write_booklet(job: dict, year: int, programs: list[dict]) -> None:
    raw, out_dir, pdf = job['raw'], job['out_dir'], job['pdf']
    data = os.path.join(out_dir, 'programs.jsonl.gz')
    write_gzip_jsonl(data, programs)
    manifest = {
        'format': 'booklet-programs-v1',
        'year': year,
        'group': job['group'],
        'group_name': GROUPS[job['group']],
        'source_file': os.path.basename(pdf),
        'source_sha256': sha256(pdf),
        'pages': [raw['rows'][0]['page'], raw['rows'][-1]['page']],
        'programs': len(programs),
        'programs_file': 'programs.jsonl.gz',
        'programs_sha256': sha256(data),
        'counts': {
            'provinces': len({p['province'] for p in programs}),
            'cities': len({(p['province'], p['city']) for p in programs}),
            'institutions': len({p['institution'] for p in programs}),
            'campuses': len({(p['institution'], p['campus']) for p in programs if p['campus']}),
            'fields': len({p['field'] for p in programs}),
        },
    }
    with open(os.path.join(out_dir, 'manifest.json'), 'w', encoding='utf-8') as fh:
        json.dump(manifest, fh, ensure_ascii=False, indent=2)
        fh.write('\n')
    print(json.dumps(manifest, ensure_ascii=False, indent=1))


def write_review(review: str, programs: list[dict], findings: dict, field_list: FieldList) -> None:
    import csv
    from places_1405 import INFERRED
    places = collections.Counter((p['province'], p['city'], p['institution'], p['campus'] or '', p['city_how']) for p in programs)
    with open(os.path.join(review, 'places.tsv'), 'w', encoding='utf-8') as fh:
        fh.write('province\tcity\tinstitution\tcampus\thow\tinferred\tprograms\n')
        for (prov, city, inst, campus, how), n in sorted(places.items()):
            inferred = 'inferred' if (inst, campus or None) in INFERRED else ''
            fh.write(f'{prov}\t{city}\t{inst}\t{campus}\t{how}\t{inferred}\t{n}\n')
    cols = ['code', 'page', 'province', 'city', 'institution', 'campus', 'field', 'course', 'course_type', 'method',
            'admission_type', 'capacity_first', 'capacity_second', 'accepts_female', 'female_capacity', 'accepts_male',
            'male_capacity', 'description', 'admission_period', 'admission_scope', 'service_location', 'section', 'city_how']
    with open(os.path.join(review, 'programs.csv'), 'w', encoding='utf-8-sig', newline='') as fh:
        w = csv.writer(fh)
        w.writerow(cols)
        for p in programs:
            w.writerow([p[c] for c in cols])
    json.dump(findings, open(os.path.join(review, 'findings.json'), 'w', encoding='utf-8'), ensure_ascii=False, indent=1)


if __name__ == '__main__':
    main()
