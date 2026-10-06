"""Glyph-level reader for the Islamic Azad University (دانشگاه آزاد) 1405 booklets.

The five Azad booklets are CorelDRAW exports. Their text layer is in visual
order, so every glyph is placed by its position instead of trusting the
extracted string: table cells come from the ruling lines, words are read
right to left, digit and Latin runs keep their left-to-right order, and
brackets drawn in visual shape are turned back into logical ones.

The PDFs drop the zero-width non-joiner (نیم‌فاصله). It is recovered from the
shape each letter was drawn in: a joining letter drawn in its final or
isolated form with another letter right after it was separated by a ZWNJ.
The shape is read from the text layer when it carries Arabic presentation
forms (the BRoyaBold booklets), and otherwise from the embedded font: the
glyph name (uniFE91 is the initial ب) or the font's init/medi/fina tables.
"""
from __future__ import annotations

import io
import re
import unicodedata
from dataclasses import dataclass

import pymupdf
from fontTools.ttLib import TTFont

ARABIC_TO_PERSIAN = str.maketrans({'ي': 'ی', 'ى': 'ی', 'ك': 'ک', 'ـ': None})
TO_ASCII_DIGITS = str.maketrans('۰۱۲۳۴۵۶۷۸۹٠١٢٣٤٥٦٧٨٩', '01234567890123456789')
TO_PERSIAN_DIGITS = str.maketrans('0123456789٠١٢٣٤٥٦٧٨٩', '۰۱۲۳۴۵۶۷۸۹۰۱۲۳۴۵۶۷۸۹')
LTR_CHAR = re.compile(r'[0-9A-Za-z۰-۹٠-٩.:_%+/]')
MIRROR = {'(': ')', ')': '(', '[': ']', ']': '[', '«': '»', '»': '«', '<': '>', '>': '<'}
DUAL_JOINING = set('بپتثجچحخسشصضطظعغفقکگلمنهیئيكةـ')
FORMS = ('isolated', 'initial', 'medial', 'final')


@dataclass
class Glyph:
    c: str          # logical code points (base letters, Persian ی/ک); a ligature carries all of them
    x0: float
    x1: float
    y0: float
    y1: float
    oy: float       # baseline
    font: str
    size: float
    form: str | None  # contextual form of an Arabic-script letter, None when unknown or not a letter

    @property
    def cx(self) -> float:
        return (self.x0 + self.x1) / 2

    @property
    def cy(self) -> float:
        return (self.y0 + self.y1) / 2


def _form_of(ch: str) -> tuple[str, str]:
    """(base character, form) for a presentation-form code point; (ch, '') otherwise."""
    dec = unicodedata.decomposition(ch)
    if dec.startswith('<'):
        kind = dec[1:].split('>')[0]
        if kind in FORMS:
            base = ''.join(chr(int(code, 16)) for code in dec.split()[1:])
            return base, kind
    return ch, ''


class FontForms:
    """Contextual form of every glyph id of the embedded fonts, where the font tells it."""

    def __init__(self, doc: pymupdf.Document) -> None:
        self.doc = doc
        self.by_xref: dict[int, dict[int, str]] = {}
        self.page_fonts: dict[int, dict[str, int]] = {}

    def _load(self, xref: int) -> dict[int, str]:
        if xref in self.by_xref:
            return self.by_xref[xref]
        forms: dict[int, str] = {}
        buf = self.doc.extract_font(xref)[3]
        try:
            font = TTFont(io.BytesIO(buf)) if buf else None
        except Exception:
            font = None
        if font is not None:
            order = font.getGlyphOrder()
            targets: dict[str, str] = {}
            if 'GSUB' in font:
                table = font['GSUB'].table
                feature_form = {'init': 'initial', 'medi': 'medial', 'fina': 'final'}
                for record in table.FeatureList.FeatureRecord:
                    kind = feature_form.get(record.FeatureTag)
                    if not kind:
                        continue
                    for index in record.Feature.LookupListIndex:
                        for sub in table.LookupList.Lookup[index].SubTable:
                            for source, target in getattr(sub, 'mapping', {}).items():
                                if source != target:   # some fonts map a letter onto itself (uni062F -> uni062F)
                                    targets.setdefault(target, kind)
            for gid, name in enumerate(order):
                m = re.fullmatch(r'uni([0-9A-F]{4})', name)
                if m:
                    # a named glyph tells its form itself: uniFE91 is the initial ب, uni0628 the isolated one
                    _, kind = _form_of(chr(int(m.group(1), 16)))
                    forms[gid] = kind or 'isolated'
                    continue
                if name in targets:
                    forms[gid] = targets[name]
        self.by_xref[xref] = forms
        return forms

    def for_page(self, pno: int) -> dict[str, dict[int, str]]:
        out: dict[str, dict[int, str]] = {}
        for xref, _ext, _type, basefont, *_ in self.doc.get_page_fonts(pno):
            name = basefont.split('+', 1)[1] if re.match(r'^[A-Z]{6}\+', basefont) else basefont
            out.setdefault(name, self._load(xref))
        return out


def page_glyphs(doc: pymupdf.Document, pno: int, fonts: FontForms) -> list[Glyph]:
    forms = fonts.for_page(pno)
    out: list[Glyph] = []
    for span in doc[pno].get_texttrace():
        if span.get('type') not in (0, None) or span.get('opacity', 1) == 0:
            continue
        font_forms = forms.get(span['font'], {})
        for ucs, gid, origin, bbox in span['chars']:
            raw = chr(ucs)
            base, kind = _form_of(raw)
            if not kind and unicodedata.decomposition(raw).startswith('<'):
                base = unicodedata.normalize('NFKC', raw)  # a ligature such as ﻻ
            base = base.translate(ARABIC_TO_PERSIAN)
            if gid == -1 and out and out[-1].font == span['font'] and abs(out[-1].oy - origin[1]) < 0.5:
                out[-1].c += base
                continue
            if not kind and _is_letter(base[:1]):
                kind = font_forms.get(gid, 'isolated')
            x0, y0, x1, y1 = bbox
            out.append(Glyph(base, x0, x1, y0, y1, origin[1], span['font'], span['size'], kind or None))
    return out


def _is_letter(ch: str) -> bool:
    return bool(ch) and 'ؠ' <= ch <= 'ۿ' and ch.isalpha()


@dataclass
class Rule:
    a: float
    lo: float
    hi: float


def page_rules(page: pymupdf.Page) -> tuple[list[Rule], list[Rule]]:
    verticals: list[Rule] = []
    horizontals: list[Rule] = []
    for drawing in page.get_drawings():
        for item in drawing['items']:
            if item[0] == 'l':
                a, b = item[1], item[2]
                if abs(a.x - b.x) < 0.6:
                    verticals.append(Rule((a.x + b.x) / 2, min(a.y, b.y), max(a.y, b.y)))
                elif abs(a.y - b.y) < 0.6:
                    horizontals.append(Rule((a.y + b.y) / 2, min(a.x, b.x), max(a.x, b.x)))
            elif item[0] == 're':
                r = item[1]
                if r.width < 2 and r.height > 2:
                    verticals.append(Rule((r.x0 + r.x1) / 2, r.y0, r.y1))
                elif r.height < 2 and r.width > 2:
                    horizontals.append(Rule((r.y0 + r.y1) / 2, r.x0, r.x1))
                elif r.width >= 2 and r.height >= 2 and drawing.get('color') is not None:
                    verticals += [Rule(r.x0, r.y0, r.y1), Rule(r.x1, r.y0, r.y1)]
                    horizontals += [Rule(r.y0, r.x0, r.x1), Rule(r.y1, r.x0, r.x1)]
            elif item[0] == 'qu':
                q = item[1]
                r = q.rect
                if drawing.get('color') is not None and r.width >= 2 and r.height >= 2:
                    verticals += [Rule(r.x0, r.y0, r.y1), Rule(r.x1, r.y0, r.y1)]
                    horizontals += [Rule(r.y0, r.x0, r.x1), Rule(r.y1, r.x0, r.x1)]
    return verticals, horizontals


def cluster(values: list[float], tol: float) -> list[float]:
    groups: list[list[float]] = []
    for v in sorted(values):
        if groups and v - groups[-1][-1] <= tol:
            groups[-1].append(v)
        else:
            groups.append([v])
    return [sum(g) / len(g) for g in groups]


class Zwnj:
    """Collects every place a ZWNJ was inserted, for review."""

    def __init__(self) -> None:
        self.inserted: dict[str, int] = {}

    def note(self, word: str) -> None:
        self.inserted[word] = self.inserted.get(word, 0) + 1


class Phantoms:
    """Collects every space glyph dropped as drawn on top of letters, for review."""

    def __init__(self) -> None:
        self.dropped: dict[str, int] = {}

    def note(self, text: str) -> None:
        self.dropped[text] = self.dropped.get(text, 0) + 1


def drop_phantom_spaces(ordered: list[Glyph], phantoms: 'Phantoms | None' = None) -> list[Glyph]:
    """Remove space glyphs that take no room between the letters around them.

    Corel sometimes leaves a space glyph on top of the end of a letter
    («نجف آباد» is drawn as نجف‌آباد: the space sits under the tail of ف and
    آ touches ف). A real space keeps its neighbours about one space width
    apart; under a phantom one they touch.
    """
    out: list[Glyph] = []
    for i, g in enumerate(ordered):
        if g.c == ' ':
            right = next((x for x in reversed(ordered[:i]) if x.c.strip()), None)
            left = next((x for x in ordered[i + 1:] if x.c.strip()), None)
            width = g.x1 - g.x0
            if right is not None and left is not None and width > 0 and right.x0 - left.x1 < 0.35 * width:
                if phantoms is not None:
                    before = ''.join(x.c for x in ordered[max(0, i - 6):i])
                    after = ''.join(x.c for x in ordered[i + 1:i + 7])
                    phantoms.note(before + '·' + after)
                continue
        out.append(g)
    return out


def logical_text(glyphs: list[Glyph], zwnj: Zwnj | None = None, phantoms: 'Phantoms | None' = None) -> str:
    """One visual line of glyphs in reading order."""
    ordered = sorted(glyphs, key=lambda g: (-round(g.x1, 1), -g.x0))
    ordered = drop_phantom_spaces(ordered, phantoms)
    units: list[Glyph] = []
    i = 0
    while i < len(ordered):
        if LTR_CHAR.match(ordered[i].c[:1]):
            j = i
            while j < len(ordered) and LTR_CHAR.match(ordered[j].c[:1]):
                j += 1
            run = ordered[i:j]
            # a bracket pair drawn around a Latin/digit run belongs to that run: (ICT)
            units.extend(reversed(run))
            i = j
        else:
            units.append(ordered[i])
            i += 1
    parts: list[str] = []
    for k, g in enumerate(units):
        text = g.c
        if text in MIRROR:
            prev_ltr = k > 0 and LTR_CHAR.match(units[k - 1].c[:1])
            next_ltr = k + 1 < len(units) and LTR_CHAR.match(units[k + 1].c[:1])
            if not (prev_ltr and next_ltr):
                text = MIRROR[text]
        parts.append(text)
        nxt = units[k + 1] if k + 1 < len(units) else None
        if (nxt is not None and len(g.c) == 1 and g.c in DUAL_JOINING and g.form in ('isolated', 'final')
                and _is_letter(nxt.c[:1])):
            parts.append('‌')
            if zwnj is not None:
                left = ''.join(u.c for u in units[max(0, k - 6):k + 1])
                right = ''.join(u.c for u in units[k + 1:k + 7])
                zwnj.note((left.split(' ')[-1] + '‌' + right.split(' ')[0]))
    return ''.join(parts)


def normalize(text: str) -> str:
    text = unicodedata.normalize('NFC', text).translate(ARABIC_TO_PERSIAN)
    text = text.replace(' ', ' ').replace('‏', '').replace('‎', '')
    text = re.sub(r'[ \t]+', ' ', text)
    return text.strip()


def split_lines(glyphs: list[Glyph], tol: float = 2.5) -> list[list[Glyph]]:
    glyphs = [g for g in glyphs if g.c not in ('\n', '\r')]
    rows: list[list[Glyph]] = []
    for g in sorted(glyphs, key=lambda g: g.oy):
        if rows and abs(g.oy - rows[-1][0].oy) <= tol:
            rows[-1].append(g)
        else:
            rows.append([g])
    return rows


def split_segments(line: list[Glyph], gap: float) -> list[list[Glyph]]:
    """Pieces of a visual line separated by more than `gap` points of empty space."""
    ordered = sorted(line, key=lambda g: -g.x1)
    segments: list[list[Glyph]] = []
    for g in ordered:
        if segments:
            solid = [s for s in segments[-1] if s.c.strip()]
            last = solid[-1] if solid else segments[-1][-1]
            if g.c.strip() and last.x0 - g.x1 > gap:
                segments.append([g])
                continue
        if not segments:
            segments.append([g])
        else:
            segments[-1].append(g)
    return segments


def cell_text(glyphs: list[Glyph], zwnj: Zwnj | None = None, phantoms: Phantoms | None = None) -> str:
    lines = [normalize(logical_text(line, zwnj, phantoms)) for line in split_lines(glyphs)]
    return normalize(' '.join(l for l in lines if l))


@dataclass
class Band:
    page: int
    y0: float
    y1: float
    cells: list[str]          # right to left: index 0 is the right-most column
    edges: list[float]        # x boundaries, descending
    glyphs: list[list[Glyph]]


@dataclass
class Loose:
    page: int
    y0: float
    y1: float
    segments: list[str]       # right to left
    size: float
    font: str

    @property
    def text(self) -> str:
        return ' | '.join(self.segments)


def read_page(doc: pymupdf.Document, pno: int, fonts: FontForms, zwnj: Zwnj | None = None,
              phantoms: Phantoms | None = None) -> list[Band | Loose]:
    page = doc[pno]
    glyphs = page_glyphs(doc, pno, fonts)
    verticals, horizontals = page_rules(page)
    hy = cluster([h.a for h in horizontals if h.hi - h.lo > 20], 1.0)
    items: list[Band | Loose] = []
    used: set[int] = set()
    for top, bottom in zip(hy, hy[1:]):
        if bottom - top < 3:
            continue
        mid = (top + bottom) / 2
        xs = cluster([v.a for v in verticals if v.lo - 1 <= mid <= v.hi + 1], 1.2)
        if len(xs) < 2:
            continue
        edges = sorted(xs, reverse=True)
        cells: list[list[Glyph]] = [[] for _ in range(len(edges) - 1)]
        for gi, g in enumerate(glyphs):
            if gi in used or not (top - 0.5 <= g.cy <= bottom + 0.5):
                continue
            for ci in range(len(edges) - 1):
                if edges[ci + 1] - 0.5 <= g.cx <= edges[ci] + 0.5:
                    cells[ci].append(g)
                    used.add(gi)
                    break
        items.append(Band(pno + 1, top, bottom, [cell_text(c, zwnj, phantoms) for c in cells], edges, cells))
    loose = [g for gi, g in enumerate(glyphs) if gi not in used]
    for line in split_lines(loose, tol=3.0):
        size = max(g.size for g in line)
        segments = [normalize(logical_text(seg, zwnj, phantoms)) for seg in split_segments(line, gap=4.0)]
        segments = [s for s in segments if s]
        if segments:
            items.append(Loose(pno + 1, min(g.y0 for g in line), max(g.y1 for g in line), segments, size, line[0].font))
    items.sort(key=lambda it: (it.y0, 0 if isinstance(it, Loose) else 1))
    return items
