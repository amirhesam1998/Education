"""Province and city of every Azad 1405 unit (واحد / مرکز).

The Azad booklets print a unit as «واحد X» or «مرکز X» under a province
heading; X is the place the unit is in. The city of a unit is that place,
spelled as in the state booklets' city list (places_1405.CITIES and
PLACE_NAMES) when it is the same place, otherwise as printed (AZAD_CITIES).
Units whose name holds no city, or holds a qualifier around it, are in
CITY_OF_UNIT; the ones whose city is not printed at all are in INFERRED.
Anything not covered stops the build.
"""
from __future__ import annotations

import os
import re
import sys

sys.path.insert(0, os.path.join(os.path.dirname(os.path.abspath(__file__)), '..', 'booklet'))

from places_1405 import CITIES, PLACE_NAMES, key, province as state_province  # noqa: E402

ABROAD = 'برون مرزی'

# One spelling per unit code. The booklets print these two units two ways;
# the spelling of most rows is kept. (code -> canonical name)
UNIT_NAMES = {
    '536': 'مرکز کوارفارس',      # «مرکز کوار» in کاردانی ناپیوسته p109
    '656': 'مرکز قرچک ورامین',   # «مرکز قرچک» in کاردانی ناپیوسته p55–56
}

# Places printed as unit names that are not in the state booklets' city list,
# spelled as printed. A county or region printed as the unit's place is kept
# as printed (the unit is not moved to a city the booklet does not name).
AZAD_CITIES: dict[str, list[str]] = {
    'آذربایجان شرقی': [
        'ممقان', 'خاروانا', 'خامنه', 'صوفیان', 'ایلخچی', 'تسوج', 'بندرشرفخانه', 'زنوز', 'هوراند',
        'خداآفرین', 'گوگان', 'ترکمانچای', 'سیس', 'سردرود', 'مهربان', 'خسروشهر',
    ],
    'آذربایجان غربی': ['چالدران', 'قره ضیاءالدین', 'سولدوز'],
    'اردبیل': ['بیله سوار', 'اصلاندوز', 'انگوت', 'سرعین', 'هشتجین', 'لاهرود'],
    'اصفهان': ['فلاورجان', 'دهاقان', 'فریدن', 'هرند', 'لنجان', 'زواره', 'بادرود', 'جوشقان قالی', 'نیک آباد'],
    'ایلام': ['مهران', 'آبدانان', 'شیروان و چرداول'],
    'بوشهر': ['خارک', 'دشتستان', 'بندردیلم', 'دلوار', 'دیر', 'تنگستان'],
    'تهران': ['رودهن', 'رباط کریم', 'صفادشت', 'قرچک', 'صباشهر'],
    'خراسان رضوی': ['بردسکن'],
    'خراسان شمالی': ['جاجرم'],
    'خوزستان': ['امیدیه', 'باغملک', 'گتوند'],
    'زنجان': ['خدابنده', 'هیدج', 'طارم'],
    'فارس': [
        'خنج', 'سروستان', 'بیضا', 'زاهد شهر', 'فراشبند', 'قیروکارزین', 'آباده طشک', 'داریون',
        'زرین دشت', 'کوار', 'پاسارگاد', 'میمند', 'بیرم', 'ششده و قره بلاغ', 'سده', 'خفر',
    ],
    'قزوین': ['آوج', 'شال', 'اسفرورین', 'ضیاء آباد', 'خرمدشت', 'آبگرم'],
    'لرستان': ['سلسله', 'اشترینان', 'دلفان'],
    'مازندران': ['سوادکوه', 'گلوگاه'],
    'مرکزی': ['مهاجران', 'نراق', 'آشتیان', 'جاسب', 'کمیجان', 'آستانه', 'زرندیه', 'خنداب'],
    'هرمزگان': [
        'حاجی آباد', 'رودان', 'هرمز', 'پارسیان', 'بندر جاسک', 'جناح', 'سیریک', 'بندر چارک', 'بشاگرد',
    ],
    'همدان': ['سامن', 'قروه درجزین'],
    'چهارمحال و بختیاری': ['اردل'],
    'کرمان': ['بهرمان', 'کوهبنان', 'عنبرآباد'],
    'کرمانشاه': ['سنقروکلیائی'],
    'کهگیلویه و بویراحمد': ['بهمئی'],
    'گلستان': ['بندرگز', 'کلاله', 'گمیشان', 'گالیکش'],
    'گیلان': ['رودبار', 'فومن و شفت', 'سیاهکل', 'ماسال', 'لشت نشاء - زیبا کنار'],
    'یزد': ['اشکذر', 'خاتم', 'بهاباد'],
    ABROAD: ['امارات متحده عربی', 'آکسفورد'],
}

# Units whose name is not just «واحد/مرکز + place». (unit code -> city)
CITY_OF_UNIT: dict[str, str] = {
    '101': 'تهران',        # واحد تهران مرکزی
    '123': 'تهران',        # واحد علوم و تحقیقات (as in the state booklets)
    '726': 'تهران',        # واحد علوم و تحقیقات - ظرفیت خودگردان
    '139': 'ری',           # واحد یادگار امام خمینی(ره) شهرری
    '844': 'ری',           # واحد یادگار امام خمینی(ره) شهرری - مجتمع دانشگاهی مادران
    '252': 'قدس',          # واحد شهر قدس
    '942': 'قدس',          # واحد شهر قدس- ظرفیت خودگردان
    '503': 'تهران',        # واحد الکترونیکی
    '880': 'تهران',        # واحد هنرهای اسلامی- ایرانی استاد فرشچیان
    '886': 'تهران',        # واحد بین الملل فرشتگان
    '914': 'تهران',        # واحد علوم پزشکی تهران
    '915': 'تهران',
    '910': 'تبریز',        # واحد علوم پزشکی تبریز
    '913': 'تبریز',
    '908': 'مشهد',         # واحد علوم پزشکی مشهد
    '911': 'مشهد',
    '827': 'مشهد',         # واحد مشهد - پردیس مجتمع بین المللی دانشگاه آزاد اسلامی
    '909': 'قم',           # واحد علوم پزشکی قم
    '912': 'قم',
    '175': 'خوراسگان',     # واحد اصفهان (خوراسگان), as in the state booklets
    '702': 'خوراسگان',
    '672': 'دهق',          # مرکز مهردشت (دهق)
    '287': 'پارس‌آباد',     # واحد پارس آباد مغان
    '457': 'ایوان',        # واحد ایوان غرب
    '397': 'گناوه',        # واحد بندر گناوه
    '597': 'قشم',          # مرکز آموزش بین المللی قشم
    '916': 'کیش',          # مرکز بین المللی کیش
    '926': 'خرمشهر',       # واحد بین المللی اروند
    '906': 'جلفا',         # واحد ارس
    '260': 'آمل',          # واحد آیت ا... آملی (as in the state booklets)
    '536': 'کوار',         # مرکز کوارفارس / مرکز کوار
    '656': 'قرچک',         # مرکز قرچک ورامین / مرکز قرچک
    '162': 'فردوس',        # واحد فردوس (printed under خراسان رضوی in the exam booklet)
    '756': 'فردوس',        # واحد فردوس - ظرفیت خودگردان
}

# Cities in CITY_OF_UNIT that the unit name does not print; listed for review.
INFERRED = {'101', '123', '726', '503', '880', '886', '926', '906'}

PREFIX = re.compile(r'^(?:واحد|مرکز)\s+')
SELF_FUNDED = re.compile(r'\s*-\s*ظرفیت خودگردان$')


def is_self_funded(unit_name: str) -> bool:
    return bool(SELF_FUNDED.search(unit_name))


def province(printed: str) -> str:
    """Canonical province of a printed heading («استان سیستان وبلوچستان», «برون مرزی»)."""
    if printed == ABROAD:
        return ABROAD
    if not printed.startswith('استان '):
        raise ValueError(f'unknown province heading: {printed!r}')
    return state_province(printed[len('استان '):])


def _all_cities(prov: str) -> dict[str, str]:
    cities: dict[str, str] = {}
    for name in CITIES.get(prov, []) + AZAD_CITIES.get(prov, []):
        k = key(name)
        if k in cities and cities[k] != name:
            raise ValueError(f'{prov}: two spellings of one city: {cities[k]!r} / {name!r}')
        cities[k] = name
    return cities


def city(unit_code: str, unit_name: str, prov: str) -> str:
    if unit_code in CITY_OF_UNIT:
        return CITY_OF_UNIT[unit_code]
    place = PREFIX.sub('', SELF_FUNDED.sub('', unit_name))
    cities = _all_cities(prov)
    if key(place) in cities:
        return cities[key(place)]
    for (p, printed), canonical in PLACE_NAMES.items():
        if p == prov and key(printed) == key(place):
            return canonical
    raise ValueError(f'no city for unit {unit_code} {unit_name!r} in {prov}')


def known_cities() -> dict[str, dict[str, str]]:
    """key -> spelling of every city, per province, for the one-spelling check."""
    out = {}
    for prov in set(CITIES) | set(AZAD_CITIES):
        out[prov] = _all_cities(prov)
    return out
