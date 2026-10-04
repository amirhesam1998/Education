"""Where each 1405 booklet program is studied: province, institution, campus and city.

The booklet prints the place in several shapes (section titles, a column
holding the institution and its notes, the Farhangian campus column). Every
shape is reduced here to one canonical institution, campus and city, so the
catalogue never holds two spellings of the same place.

Cities are not printed in a column of their own. Each one is read from the
printed text (" - کرج", "(محل تحصیل دانشکده پرستاری میانه)", "مرکز بناب",
"واقع در پاکدشت"), or, where the text names no city, taken from CITY_OF,
and must be listed in CITIES for its province, otherwise the build stops.
"""
from __future__ import annotations

import re
from dataclasses import dataclass

PROVINCES = [
    'آذربایجان شرقی', 'آذربایجان غربی', 'اردبیل', 'اصفهان', 'البرز', 'ایلام', 'بوشهر', 'تهران',
    'چهارمحال و بختیاری', 'خراسان جنوبی', 'خراسان رضوی', 'خراسان شمالی', 'خوزستان', 'زنجان',
    'سمنان', 'سیستان و بلوچستان', 'فارس', 'قزوین', 'قم', 'کردستان', 'کرمان', 'کرمانشاه',
    'کهگیلویه و بویراحمد', 'گلستان', 'گیلان', 'لرستان', 'مازندران', 'مرکزی', 'هرمزگان', 'همدان', 'یزد',
]


def key(text: str) -> str:
    """Spelling-insensitive key: ignores spaces, ZWNJ, dashes, harakat, madda and hamza forms."""
    text = re.sub(r'[\s\u200c\-–—ـ\u064B-\u0652\u0670\u0654]', '', text)
    return text.translate(str.maketrans({
        'آ': 'ا', 'أ': 'ا', 'إ': 'ا', 'ؤ': 'و', 'ئ': 'ی', 'ي': 'ی', 'ك': 'ک', 'ۀ': 'ه', 'ة': 'ه'}))


PROVINCE_BY_KEY = {key(p): p for p in PROVINCES}


def province(text: str) -> str:
    canonical = PROVINCE_BY_KEY.get(key(text))
    if canonical is None:
        raise ValueError(f'unknown province: {text!r}')
    return canonical


# Every city a 1405 tajrobi program is studied in, in one spelling each.
# Spelling: the official name of the city, with the ZWNJ the booklet itself
# uses in most places (خرم‌آباد, not خرم آباد). Printed variants that differ
# only in spaces, ZWNJ, madda or hamza seat (خرم آباد، میاندواب، نایین) are
# matched through key(); other printed names of a place are in PLACE_NAMES.
CITIES: dict[str, list[str]] = {
    'آذربایجان شرقی': [
        'تبریز', 'آذرشهر', 'اسکو', 'اهر', 'بستان‌آباد', 'بناب', 'جلفا', 'سراب', 'سهند', 'شبستر',
        'عجب‌شیر', 'قره‌آغاج', 'کلیبر', 'مراغه', 'مرند', 'ملکان', 'میانه', 'ورزقان', 'هریس', 'هشترود',
    ],
    'آذربایجان غربی': [
        'ارومیه', 'بوکان', 'پیرانشهر', 'تکاب', 'خوی', 'سردشت', 'سلماس', 'شاهین‌دژ', 'شوط', 'قوشچی',
        'ماکو', 'مهاباد', 'میاندوآب', 'نقده',
    ],
    'اردبیل': ['اردبیل', 'پارس‌آباد', 'خلخال', 'گرمی', 'مشگین‌شهر', 'نمین', 'نیر'],
    'اصفهان': [
        'اصفهان', 'آران و بیدگل', 'اردستان', 'بهارستان', 'تیران', 'خمینی‌شهر', 'خوانسار', 'خوراسگان',
        'دولت‌آباد', 'زرین‌شهر', 'سمیرم', 'شاهین‌شهر', 'شهرضا', 'فریدون‌شهر', 'فولادشهر', 'قهدریجان',
        'کاشان', 'کوهپایه', 'گلپایگان', 'مبارکه', 'میمه', 'نائین', 'نجف‌آباد', 'نطنز', 'ورزنه', 'وزوان',
    ],
    'البرز': ['کرج', 'اشتهارد', 'ماهدشت', 'نظرآباد', 'هشتگرد'],
    'ایلام': ['ایلام', 'ایوان', 'دره‌شهر', 'دهلران', 'سرابله', 'هلیلان'],
    'بوشهر': ['بوشهر', 'برازجان', 'جم', 'خورموج', 'عسلویه', 'کنگان', 'گناوه'],
    'تهران': [
        'تهران', 'اسلامشهر', 'بهارستان', 'پاکدشت', 'پردیس', 'پرند', 'پیشوا', 'حسن‌آباد', 'دماوند',
        'ری', 'شهریار', 'فیروزکوه', 'لواسان', 'ملارد', 'ورامین',
    ],
    'چهارمحال و بختیاری': ['شهرکرد', 'باباحیدر', 'بروجن', 'فارسان', 'فرخ‌شهر', 'گندمان', 'لردگان'],
    'خراسان جنوبی': ['بیرجند', 'اسدیه', 'سرایان', 'طبس', 'فردوس', 'قائن', 'نهبندان'],
    'خراسان رضوی': [
        'مشهد', 'بجستان', 'تایباد', 'تربت‌جام', 'تربت حیدریه', 'جوین', 'چناران', 'خواف', 'درگز',
        'دولت‌آباد', 'سبزوار', 'سرخس', 'فریمان', 'قوچان', 'کاشمر', 'کلات', 'گلبهار', 'گناباد', 'نیشابور',
    ],
    'خراسان شمالی': ['بجنورد', 'آشخانه', 'اسفراین', 'شیروان', 'غلامان'],
    'خوزستان': [
        'اهواز', 'آبادان', 'اندیمشک', 'ایذه', 'بستان', 'بندر امام خمینی', 'بندر ماهشهر', 'بهبهان',
        'خرمشهر', 'دزفول', 'رامشیر', 'رامهرمز', 'سوسنگرد', 'شادگان', 'شوش', 'شوشتر', 'مسجدسلیمان',
        'ملاثانی', 'هفتگل', 'هندیجان', 'هویزه',
    ],
    'زنجان': ['زنجان', 'ابهر', 'خرمدره', 'سجاس', 'قیدار', 'ماه‌نشان'],
    'سمنان': ['سمنان', 'آرادان', 'ایوانکی', 'دامغان', 'سرخه', 'شاهرود', 'شهمیرزاد', 'گرمسار', 'مهدی‌شهر'],
    'سیستان و بلوچستان': ['زاهدان', 'ایرانشهر', 'چابهار', 'خاش', 'راسک', 'زابل', 'زهک', 'سراوان', 'نیک‌شهر'],
    'فارس': [
        'شیراز', 'آباده', 'ارسنجان', 'استهبان', 'اقلید', 'اوز', 'بوانات', 'جویم', 'جهرم', 'خرامه',
        'خشت', 'داراب', 'زرقان', 'سپیدان', 'سیمکان', 'صفاشهر', 'فسا', 'فیروزآباد', 'قادرآباد', 'کازرون',
        'گراش', 'لار', 'لامرد', 'مرودشت', 'مهر', 'نورآباد', 'نی‌ریز',
    ],
    'قزوین': ['قزوین', 'آبیک', 'بوئین‌زهرا', 'تاکستان'],
    'قم': ['قم', 'جعفریه'],
    'کردستان': ['سنندج', 'بانه', 'بیجار', 'دیواندره', 'سقز', 'قروه', 'کامیاران', 'مریوان'],
    'کرمان': [
        'کرمان', 'انار', 'بافت', 'بردسیر', 'بم', 'جیرفت', 'راین', 'رفسنجان', 'زرند', 'سیرجان',
        'شهربابک', 'کهنوج',
    ],
    'کرمانشاه': [
        'کرمانشاه', 'اسلام‌آباد غرب', 'پاوه', 'جوانرود', 'روانسر', 'سرپل ذهاب', 'سنقر', 'صحنه',
        'قصرشیرین', 'کنگاور', 'گیلانغرب', 'هرسین',
    ],
    'کهگیلویه و بویراحمد': ['یاسوج', 'دوگنبدان', 'دهدشت'],
    'گلستان': [
        'گرگان', 'آزادشهر', 'آق‌قلا', 'بندر ترکمن', 'علی‌آباد کتول', 'کردکوی', 'گنبدکاووس', 'مراوه‌تپه',
        'مینودشت',
    ],
    'گیلان': [
        'رشت', 'آستارا', 'آستانه اشرفیه', 'بندر انزلی', 'تالش', 'چابکسر', 'خمام', 'رستم‌آباد', 'رودسر',
        'سنگر', 'صومعه‌سرا', 'فومن', 'لاهیجان', 'لنگرود', 'منجیل',
    ],
    'لرستان': ['خرم‌آباد', 'ازنا', 'الشتر', 'الیگودرز', 'بروجرد', 'پلدختر', 'دورود', 'کوهدشت', 'نورآباد'],
    'مازندران': [
        'ساری', 'آمل', 'امیرکلا', 'بابل', 'بابلسر', 'بهشهر', 'بهنمیر', 'پل‌سفید', 'تنکابن', 'جویبار',
        'چالوس', 'رامسر', 'رویان', 'زیراب', 'قائم‌شهر', 'محمودآباد', 'نکا', 'نور', 'نوشهر',
    ],
    'مرکزی': ['اراک', 'تفرش', 'خمین', 'دلیجان', 'ساوه', 'شازند', 'فراهان', 'محلات'],
    'هرمزگان': ['بندرعباس', 'ابوموسی', 'بستک', 'بندر خمیر', 'بندرلنگه', 'دهبارز', 'قشم', 'کیش', 'میناب'],
    'همدان': ['همدان', 'اسدآباد', 'بهار', 'تویسرکان', 'رزن', 'کبودرآهنگ', 'ملایر', 'نهاوند'],
    'یزد': ['یزد', 'ابرکوه', 'اردکان', 'بافق', 'تفت', 'رضوانشهر', 'مهریز', 'میبد'],
}

# Places the booklet names by another name than the city: a county, a region,
# a district of the city, or a longer form. (province, printed) -> city.
PLACE_NAMES: dict[tuple[str, str], str] = {
    ('آذربایجان شرقی', 'شهر جدید سهند'): 'سهند',
    ('اردبیل', 'مشکین‌شهر'): 'مشگین‌شهر',
    ('اردبیل', 'مغان'): 'گرمی',
    ('تهران', 'تهران جنوب'): 'تهران',
    ('تهران', 'تهران شرق'): 'تهران',
    ('تهران', 'تهران شمال'): 'تهران',
    ('تهران', 'تهران غرب'): 'تهران',
    ('تهران', 'شهر ری'): 'ری',
    ('تهران', 'بهارستان رباط‌کریم'): 'بهارستان',
    ('تهران', 'شهر جدید پردیس'): 'پردیس',
    ('تهران', 'لواسانات'): 'لواسان',
    ('خراسان جنوبی', 'قائنات'): 'قائن',
    ('خراسان رضوی', 'دولت آباد زاوه'): 'دولت‌آباد',
    ('خراسان شمالی', 'اشخانه'): 'آشخانه',
    ('خراسان شمالی', 'مانه و سملقان'): 'آشخانه',
    ('خوزستان', 'ماهشهر'): 'بندر ماهشهر',
    ('خوزستان', 'شوش دانیال'): 'شوش',
    ('خوزستان', 'خوزستان'): 'اهواز',             # "موسسه غیرانتفاعی جهاد دانشگاهی - خوزستان" 
    ('زنجان', 'سجاس رود'): 'سجاس',
    ('فارس', 'لارستان'): 'لار',
    ('فارس', 'ممسنی'): 'نورآباد',
    ('فارس', 'نورآباد ممسنی'): 'نورآباد',
    ('کهگیلویه و بویراحمد', 'گچساران'): 'دوگنبدان',
    ('گلستان', 'گنبد'): 'گنبدکاووس',
    ('گیلان', 'رستم‌آباد رودبار'): 'رستم‌آباد',
    ('لرستان', 'نور آباد دلفان'): 'نورآباد',
    ('مازندران', 'سوادکوه مازندران'): 'زیراب',
    ('هرمزگان', 'بندر عباس'): 'بندرعباس',
    ('یزد', 'رضوانشهر صدوق'): 'رضوانشهر',
}

# Institutions and campuses whose printed name names no city.
# (institution, campus or None) -> (province, city). Each one was checked by hand.
CITY_OF: dict[tuple[str, str | None], tuple[str, str]] = {
    ('دانشگاه علوم پزشکی ارتش جمهوری اسلامی ایران', None): ('تهران', 'تهران'),
    ('دانشگاه علوم پزشکی و خدمات بهداشتی درمانی ایران', None): ('تهران', 'تهران'),
    ('مؤسسه آموزش عالی علمی - کاربردی هلال ایران', None): ('تهران', 'تهران'),
    ('دانشگاه اطلاعات و امنیت ملی امام باقر(ع)', None): ('تهران', 'تهران'),
    ('آموزشکده فنی نقشه‌برداری - سازمان جغرافیایی نیروهای مسلح', None): ('تهران', 'تهران'),
    ('دانشگاه غیرانتفاعی عدالت', None): ('تهران', 'تهران'),
    ('دانشگاه آزاد اسلامی', 'واحد علوم و تحقیقات'): ('تهران', 'تهران'),
    ('دانشگاه آزاد اسلامی', 'واحد آیت ا... آملی'): ('مازندران', 'آمل'),
    ('دانشگاه فرهنگیان', 'پردیس زینبیه پیشوا (ورامین)'): ('تهران', 'پیشوا'),
    # every row of this section notes "محل تحصیل واحد دماوند"
    ('مؤسسه غیرانتفاعی ارشاد', None): ('تهران', 'دماوند'),
    # PNU's Arvand centre is in the Arvand free zone; the city is not printed
    ('دانشگاه پیام نور', 'مرکز اروند'): ('خوزستان', 'خرمشهر'),
}

# Cities in CITY_OF that are inferred rather than printed; listed for review.
INFERRED = {
    ('دانشگاه پیام نور', 'مرکز اروند'),
    ('دانشگاه محقق اردبیلی', 'دانشکده کشاورزی و منابع طبیعی مغان'),
    ('دانشگاه علوم پزشکی و خدمات بهداشتی درمانی بجنورد', 'مرکز آموزشی فوریت‌های پزشکی مانه و سملقان'),
    ('دانشگاه پیام نور', 'واحد بهارستان رباط‌کریم'),
}

# Printed institution names that hold " - " as part of the name.
NAMES_WITH_DASH = {
    'مؤسسه آموزش عالی علمی - کاربردی هلال ایران',
    'آموزشکده فنی نقشه‌برداری - سازمان جغرافیایی نیروهای مسلح',
}

HONORIFIC = re.compile(r'\((?:ع|س|ص|ره|عج)\)')
QUALIFIER = re.compile(r'\((?:ویژه|شامل|استان)[^()]*\)')


def tidy(text: str) -> str:
    """One spelling of the punctuation inside a printed name: " - " for every dash, no space inside parentheses."""
    text = re.sub(r'\s*[–—-]\s*', ' - ', text)
    text = re.sub(r'\(\s+', '(', text)
    text = re.sub(r'\s+\)', ')', text)
    return re.sub(r'\s+', ' ', text).strip()


def split_study_place(text: str) -> tuple[str, str | None]:
    """'X (محل تحصیل Y) Z' -> ('X Z', 'Y'); Y may hold parentheses of its own."""
    start = text.find('(محل تحصیل')
    if start < 0:
        return text, None
    depth, end = 0, len(text)
    for i in range(start, len(text)):
        depth += {'(': 1, ')': -1}.get(text[i], 0)
        if depth == 0:
            end = i
            break
    inner = text[start + 1:end][len('محل تحصیل'):].strip()
    outside = (text[:start] + ' ' + text[end + 1:]).strip()
    return re.sub(r'\s+', ' ', outside), inner


@dataclass(frozen=True)
class Place:
    province: str
    city: str
    institution: str
    campus: str | None
    how: str                     # where the city was read from


def split_institution(name: str) -> tuple[str, str | None, str | None]:
    """A printed institution name -> (institution, campus, place suffix).

    "دانشگاه محقق اردبیلی - اردبیل" and "دانشگاه محقق اردبیلی (محل تحصیل ...)"
    are one institution: the city after the dash only says where its main
    campus is. Units of the multi-campus universities become campuses.
    """
    name = tidy(name)
    m = re.match(r'^دانشگاه آزاد اسلامی(?: استان .+? -)? (واحد .+)$', name)
    if m:
        return 'دانشگاه آزاد اسلامی', m.group(1), None
    m = re.match(r'^دانشگاه ملی مهارت - (.+)$', name)
    if m:
        return 'دانشگاه ملی مهارت', m.group(1), None
    if name in NAMES_WITH_DASH:
        return name, None, None
    institution, *rest = name.split(' - ')
    places = [p for p in rest if not p.startswith('وابسته به')]
    if len(places) > 1:
        raise UnknownPlace(f'more than one place after the institution name: {name!r}')
    return institution, None, places[0] if places else None


class UnknownPlace(Exception):
    pass


class Places:
    def __init__(self) -> None:
        self.index = {p: {key(c): c for c in cities} for p, cities in CITIES.items()}

    def city(self, province_name: str, phrase: str) -> str | None:
        """The city a printed place phrase names, or None."""
        phrase = phrase.strip(' ()')
        if (province_name, phrase) in PLACE_NAMES:
            return PLACE_NAMES[(province_name, phrase)]
        found = self.index[province_name].get(key(phrase))
        if found:
            return found
        m = re.match(r'^(?:شهرستان|شهر) (.+)$', phrase)
        if m:
            return self.city(province_name, m.group(1))
        words = phrase.split()
        # "X استان Y" / "X Y" where Y only says where X is (شاهین‌شهر اصفهان, بهنمیر بابلسر, مهر فارس)
        for n in range(len(words) - 1, 0, -1):
            head, rest = ' '.join(words[:n]), ' '.join(words[n:])
            rest = re.sub(r'^استان ', '', rest)
            if key(rest) in PROVINCE_BY_KEY or key(rest) in self.index[province_name]:
                found = self.city(province_name, head)
                if found:
                    return found
        return None

    def city_at_end(self, province_name: str, text: str) -> str | None:
        """The city named by the last words of a printed name (دانشکده پرستاری میانه -> میانه)."""
        text = HONORIFIC.sub('', QUALIFIER.sub('', text)).strip()
        m = re.search(r'واقع در (?:شهرستان |شهر )?(.+)$', text)
        if m:
            return self.city(province_name, m.group(1))
        m = re.search(r'\(([^()]+)\)$', text)
        if m:
            found = self.city(province_name, m.group(1)) or self.city_at_end(province_name, m.group(1))
            if found:
                return found
            text = text[:m.start()].strip()
        words = text.split()
        for n in range(min(4, len(words)), 0, -1):
            found = self.city(province_name, ' '.join(words[-n:]))
            if found:
                return found
        return None

    def locate(self, province_name: str | None, institution: str, campus: str | None, suffix: str | None) -> Place:
        """Where a program is studied. Without a province every province is tried and exactly one must fit."""
        hits = []
        for p in [province_name] if province_name else PROVINCES:
            found = self._find(p, institution, campus, suffix)
            if found:
                hits.append(Place(p, found[0], institution, campus, found[1]))
        if len(hits) != 1:
            where = province_name or 'any province'
            raise UnknownPlace(f'{len(hits)} cities in {where} for {institution!r} / {campus!r} / {suffix!r}: {hits}')
        return hits[0]

    def _find(self, province_name: str, institution: str, campus: str | None, suffix: str | None) -> tuple[str, str] | None:
        if (institution, campus) in CITY_OF:
            p, city = CITY_OF[(institution, campus)]
            return (city, 'hand') if p == province_name else None
        if campus:
            city = self.city_at_end(province_name, campus)
            return (city, 'campus') if city else None
        if suffix:
            city = self.city(province_name, suffix)
            return (city, 'title') if city else None
        city = self.city_at_end(province_name, institution)
        if city:
            return city, 'name'
        if (institution, None) in CITY_OF:
            p, city = CITY_OF[(institution, None)]
            return (city, 'hand') if p == province_name else None
        return None
