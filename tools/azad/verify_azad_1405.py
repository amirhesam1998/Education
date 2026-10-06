"""Check every parsed Azad row against a second reading of the PDF.

Usage: python3 verify_azad_1405.py <booklet_dir> <parsed.json>

The second reading is poppler's (pdftotext -bbox): its own glyph-to-word
assembly, independent of azad_pdf.py. For every row the words poppler finds
inside the row's table band must be exactly the words of the parsed row
(same letters, same word breaks, nothing missing, nothing extra), and the
same for every heading the row takes its province, unit or field from. Every
band of every table page must hold a parsed row. Word order inside a cell is
not compared (poppler emits visual order); the page images are checked for
that.
"""
from __future__ import annotations

import collections
import html
import json
import os
import re
import subprocess
import sys
import unicodedata

from parse_azad_1405 import BOOKLETS, COLUMNS, HEADERS

ARABIC = re.compile(r'[؀-ۿﭐ-﷿ﹰ-﻿]')
WORD = re.compile(r'<word xMin="([\d.]+)" yMin="([\d.]+)" xMax="([\d.]+)" yMax="([\d.]+)">(.*?)</word>')
# Brackets are compared as words of their own (poppler splits «(ICT)», the parser does not),
# and a ZWNJ as a word break: the only ZWNJs the parser writes replace a space glyph Corel drew
# under a letter tail (نجف‌آباد), which poppler still reads as a space.
LETTERS = str.maketrans({'ي': 'ی', 'ك': 'ک', 'ى': 'ی', 'ة': 'ه', '\u200c': ' ', '(': ' | ', ')': ' | '})
DIGITS = str.maketrans('۰۱۲۳۴۵۶۷۸۹٠١٢٣٤٥٦٧٨٩', '01234567890123456789')


def norm(word: str) -> str:
    word = unicodedata.normalize('NFKC', word)
    word = re.sub(r'[‎‏‪-‮⁦-⁩]', '', word)
    return word.translate(LETTERS).translate(DIGITS)


def words_of(text: str) -> list[str]:
    return norm(text).split()


def page_words(path: str, page: int) -> list[tuple[float, float, str]]:
    out = subprocess.run(['pdftotext', '-bbox', '-f', str(page), '-l', str(page), path, '-'],
                         capture_output=True, check=True).stdout.decode('utf-8')
    words = []
    for x0, y0, x1, y1, text in WORD.findall(out):
        text = html.unescape(text)
        if ARABIC.search(text):
            text = text[::-1]        # poppler writes right-to-left words in visual order
        for w in words_of(text):
            words.append(((float(y0) + float(y1)) / 2, (float(x0) + float(x1)) / 2, w))
    return words


def bag(texts: list[str]) -> collections.Counter:
    c: collections.Counter = collections.Counter()
    for t in texts:
        for w in words_of(t):
            c[w] += 1
    return c


def inside(words, box, pad=0.5):
    return collections.Counter(w for y, x, w in words if box[0] - pad <= y <= box[1] + pad and w)


HEADING_TEXT = {
    'province': lambda r: [r['province']],
    'field': lambda r: ['کد و نام رشته تحصیلی:', r['field_code'], r['field_name']],
    'unit': lambda r: [r['province'], f"{r['unit_name']} - {r['unit_code']}"],
    'list': lambda r: ['گروه', r['group']],
}


def check_lists(b, path, lists) -> tuple[int, int]:
    """The field lists printed after the tables, word for word."""
    problems = checked = 0
    cache: dict[int, list] = {}
    for e in lists:
        if e['booklet'] != b.key:
            continue
        checked += 1
        for page, box, want in ((e['page'], e['box'], bag([e['field_code'], e['field_name']])),
                                (e['heading_page'], e['heading_box'], bag(HEADING_TEXT['list'](e)))):
            if page not in cache:
                cache[page] = page_words(path, page)
            got = inside(cache[page], box)
            if want != got:
                problems += 1
                print(f'{b.key} list p{page} {e["field_code"]}: missing {dict(want - got)} extra {dict(got - want)}')
    return checked, problems


def check_coverage(b, words_on, rows_by_page) -> int:
    """Every word on a table page must be in a row, a heading, the column header, the
    running title or the page number. A heading left alone at the foot of a page
    must be repeated over the rows on the next page."""
    header = {w for band in HEADERS[b.layout] for cell in band for w in words_of(cell)}
    title = ' '.join(words_of(b.title))
    boxes = collections.defaultdict(list)
    for page in range(b.pages[0], b.pages[1] + 1):
        for r in rows_by_page[(b.key, page)]:
            boxes[page].append(r['box'])
            boxes[r['heading_page']].append(r['heading_box'])
    problems = 0
    for page in range(b.pages[0], b.pages[1] + 1):
        left: collections.Counter = collections.Counter()
        for y, x, w in words_on(page):
            if any(lo - 0.5 <= y <= hi + 0.5 for lo, hi in boxes[page]):
                continue
            if w in header or w == str(page) or (y < 30 and w in title):
                continue
            left[w] += 1
        if not left:
            continue
        following = rows_by_page.get((b.key, page + 1)) or [None]
        nxt = following[0]
        if nxt is not None and nxt['heading_page'] == page + 1:
            repeated = bag(HEADING_TEXT[b.layout](nxt))
            if not (left - repeated) and all(w in header for w in repeated - left):
                continue
        problems += 1
        print(f'{b.key} p{page}: words outside every row and heading: {dict(left)}')
    return problems


def main() -> None:
    booklet_dir, parsed_path = sys.argv[1:3]
    parsed = json.load(open(parsed_path, encoding='utf-8'))
    rows_by_page = collections.defaultdict(list)
    for r in parsed['rows']:
        rows_by_page[(r['booklet'], r['page'])].append(r)
    problems = 0
    checked = 0
    listed = 0
    for b in BOOKLETS:
        path = os.path.join(booklet_dir, b.file)
        cols = COLUMNS[b.layout]
        cache: dict[int, list] = {}

        def words_on(page: int) -> list:
            if page not in cache:
                cache[page] = page_words(path, page)
            return cache[page]
        for page in range(b.pages[0], b.pages[1] + 1):
            words = words_on(page)
            rows = rows_by_page[(b.key, page)]
            if not rows:
                print(f'{b.key} p{page}: no rows')
                problems += 1
            for r in rows:
                checked += 1
                want = bag([r[c] for c in cols if r[c] is not None])
                got = inside(words, r['box'])
                if want != got:
                    problems += 1
                    print(f'{b.key} p{page} row {r["unit_code"]}/{r["field_code"]}: missing {dict(want - got)} extra {dict(got - want)}')
                hp = r['heading_page']
                hwords = words_on(hp)
                hwant = bag(HEADING_TEXT[b.layout](r))
                hgot = inside(hwords, r['heading_box'])
                if hwant != hgot:
                    problems += 1
                    print(f'{b.key} p{hp} heading of {r["unit_code"]}/{r["field_code"]}: missing {dict(hwant - hgot)} extra {dict(hgot - hwant)}')
        problems += check_coverage(b, words_on, rows_by_page)
        n, p = check_lists(b, path, parsed['lists'])
        listed += n
        problems += p
    print(f'{checked} rows and {listed} field-list entries checked, {problems} problems')
    sys.exit(1 if problems else 0)


if __name__ == '__main__':
    main()
