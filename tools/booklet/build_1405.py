"""Build the 1405 catalogue data from the parsed booklet.

Usage: python3 build_1405.py <parsed.json> <booklet.pdf> <out_dir> [review_dir]

<parsed.json> is the output of parse_1405.py. Every row gets its place
(province, city, institution, campus), its field, course and admission type
and its texts. Anything that cannot be resolved stops the build.

<out_dir> holds academic_fields.json (the 149-field list of the group);
programs.jsonl.gz and manifest.json are written next to it and are what
`php artisan education:import-booklets` loads.
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


class InstitutionCells:
    """Reads "INSTITUTION (محل تحصیل CAMPUS) - notes" cells against the institutions the titles name."""

    START = 'شروع تحصیل مهرماه 1406'
    UNITS = {'دانشگاه ملی مهارت', 'دانشگاه آزاد اسلامی'}   # "INSTITUTION - UNIT - notes"

    def __init__(self, places: Places, known: dict[tuple[str, str | None], Place]) -> None:
        self.places = places
        self.known = known
        names = {inst for inst, _ in known}
        self.names = sorted(names, key=lambda n: -len(key(n)))

    def read(self, cell: str) -> tuple[Place, str | None]:
        text = tidy(cell)
        prefix = None
        if text.startswith(self.START):
            prefix, text = self.START, text[len(self.START):].lstrip(' -')
        institution, rest = self._institution(text)
        campus = None
        if rest.startswith('(محل تحصیل'):
            _, campus = split_study_place(rest)
            rest = rest[rest.index(campus) + len(campus):].lstrip(') ')
        elif institution in self.UNITS and rest.startswith('- '):
            campus, _, rest = rest[2:].partition(' - ')
            rest = '- ' + rest if rest else ''
        if rest and not rest.startswith('- '):
            raise BuildError(f'unexpected text after the institution in {cell!r}: {rest!r}')
        notes = rest[2:].strip() if rest else None
        notes = ' - '.join(n for n in (prefix, notes) if n) or None
        campus = tidy(campus) if campus else None
        place = self.known.get((institution, campus))
        if place is None or place.how == 'ambiguous':
            try:
                place = self.places.locate(None, institution, campus, None)
            except UnknownPlace:
                # a faculty named without its city ("دانشکده بهداشت") is in the institution's own city
                home = self.known.get((institution, None))
                if campus is None or home is None or home.how == 'ambiguous':
                    raise
                place = Place(home.province, home.city, institution, campus, 'institution')
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


def build_places(raw: dict) -> tuple[dict[str, Place], dict[str, dict]]:
    """Place of every program, and the texts that came out of its place cell (notes, admission scope)."""
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
    cells = InstitutionCells(places, known)

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


def drop_leading_city(places: Places, place: Place, notes: str | None) -> str | None:
    """"کرج - فاقد خوابگاه" after the institution: the city is the program's city, not a note."""
    if not notes:
        return notes
    first, sep, rest = notes.partition(' - ')
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
}

# Printed table titles that name a field of the 149-field list differently.
FIELD_LIST_ALIASES = {
    'کارشناسی ارشد پیوسته علوم قضایی': 7,
    'علوم و مهندسی صنایع غذایی(این رشته متعلق به گروه کشاورزی است)': 62,
    'مددکاری اجتماعی (ویژه وزارت بهداشت)': 85,
    'کاردانی بهداشت عمومی گرایش بهداشت خانواده': 125,
    'کاردانی بهداشت عمومی گرایش مبارزه با بیماری‌ها': 125,
    'کاردانی فنی باغبانی - تولید و فرآوری خرما': 136,
}

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
    """The 149 fields of the tajrobi group (academic_fields.json), matched to the printed table titles."""

    def __init__(self, path: str) -> None:
        self.fields = json.load(open(path, encoding='utf-8'))['fields']
        self.by_key: dict[str, dict] = {}
        for f in self.fields:
            for name in (f['name'], f'{f["degree_level"]} {f["name"]}'):
                self.by_key.setdefault(self._key(name), f)
        self.by_order = {f['order']: f for f in self.fields}

    @staticmethod
    def _key(name: str) -> str:
        return key(re.sub(r'[()]', '', name))

    def find(self, title: str) -> dict | None:
        if title in FIELD_LIST_ALIASES:
            return self.by_order[FIELD_LIST_ALIASES[title]]
        return self.by_key.get(self._key(title))


def build_programs(raw: dict, field_list: FieldList) -> list[dict]:
    from parse_1405 import PART_HEADINGS
    part_heading = {v: k for k, v in PART_HEADINGS.items()}
    sections = {s['id']: s for s in raw['sections']}
    by_code, extras = build_places(raw)
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
            'part_heading': part_heading[s['part']],
            'subpart': s['subpart'],
            'section': fa(tidy(s['title'])),
            'province': place.province,
            'city': place.city,
            'city_how': CITY_HOW[place.how],
            'institution': place.institution,
            'campus': place.campus,
            'field': c['title'],
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
    """An institution's own city: where its programs without a campus are, when that is one city."""
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


def main() -> None:
    parsed, pdf, out_dir = sys.argv[1:4]
    review = sys.argv[4] if len(sys.argv) > 4 else None
    raw = json.load(open(parsed, encoding='utf-8'))
    field_list = FieldList(os.path.join(out_dir, 'academic_fields.json'))
    try:
        programs = build_programs(raw, field_list)
        findings = check(programs)
    except BuildError as e:
        print(e)
        sys.exit(1)
    homes = institution_homes(programs)
    for p in programs:
        home = homes[p['institution']]
        p['institution_province'], p['institution_city'] = home if home else (None, None)

    data = os.path.join(out_dir, 'programs.jsonl.gz')
    write_gzip_jsonl(data, programs)
    manifest = {
        'format': 'booklet-programs-v1',
        'year': 1405,
        'group': 'tajrobi',
        'group_name': 'تجربی',
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
    if review:
        write_review(review, programs, findings, field_list)
    print(json.dumps(manifest, ensure_ascii=False, indent=1))
    print('capacity mismatches:', findings['capacity_mismatch'])


def write_review(review: str, programs: list[dict], findings: dict, field_list: FieldList) -> None:
    import csv
    places = collections.Counter((p['province'], p['city'], p['institution'], p['campus'] or '', p['city_how']) for p in programs)
    with open(os.path.join(review, 'places.tsv'), 'w', encoding='utf-8') as fh:
        fh.write('province\tcity\tinstitution\tcampus\thow\tprograms\n')
        for (prov, city, inst, campus, how), n in sorted(places.items()):
            fh.write(f'{prov}\t{city}\t{inst}\t{campus}\t{how}\t{n}\n')
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
