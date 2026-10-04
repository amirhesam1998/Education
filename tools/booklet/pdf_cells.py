"""Glyph-level table reader for Sanjesh field-selection booklets (Word-generated PDFs).

Why glyph level: the booklets are right-to-left Word exports. Generic extractors
(pdftotext, pdfplumber word grouping, PyMuPDF spans) merge neighbouring cells,
insert spaces from glyph gaps ("دا نشگاه") and break the لا ligature ("سالمت").
Here every glyph is placed into a table cell by its position, words are only
split where the PDF has a real space glyph, and the ligature is repaired.
"""
from __future__ import annotations

import re
import unicodedata
from dataclasses import dataclass, field

import pymupdf

ARABIC_TO_PERSIAN = str.maketrans({'ي': 'ی', 'ى': 'ی', 'ك': 'ک', 'ـ': None})
LTR_CHAR = re.compile(r'[0-9A-Za-z۰-۹٠-٩./:_%+]')


@dataclass
class Glyph:
    c: str          # one or more code points (a ligature carries all of them, in logical order)
    x0: float
    x1: float
    y0: float
    y1: float
    oy: float       # baseline
    font: str
    size: float
    gid: int = -1   # glyph id of the base glyph: tells the contextual form (initial/medial/final)

    @property
    def cx(self) -> float:
        return (self.x0 + self.x1) / 2

    @property
    def cy(self) -> float:
        return (self.y0 + self.y1) / 2

    @property
    def width(self) -> float:
        return self.x1 - self.x0


def page_glyphs(page: pymupdf.Page) -> list[Glyph]:
    """Every drawn glyph with its position and glyph id.

    A ligature (for example لا) is one drawn glyph followed by extra code points
    with glyph id -1; they are kept together, in the order the font maps them.
    """
    out: list[Glyph] = []
    for span in page.get_texttrace():
        if span.get('type') not in (0, None) or span.get('opacity', 1) == 0:
            continue
        for ucs, gid, origin, bbox in span['chars']:
            ch = chr(ucs)
            if gid == -1 and out and out[-1].font == span['font'] and abs(out[-1].oy - origin[1]) < 0.5:
                out[-1].c += ch
                continue
            x0, y0, x1, y1 = bbox
            out.append(Glyph(ch, x0, x1, y0, y1, origin[1], span['font'], span['size'], gid))
    return out


def _cluster(values: list[float], tol: float) -> list[float]:
    values = sorted(values)
    groups: list[list[float]] = []
    for v in values:
        if groups and v - groups[-1][-1] <= tol:
            groups[-1].append(v)
        else:
            groups.append([v])
    return [sum(g) / len(g) for g in groups]


@dataclass
class Rule:
    a: float  # position (x for vertical, y for horizontal)
    lo: float
    hi: float


def page_rules(page: pymupdf.Page) -> tuple[list[Rule], list[Rule]]:
    """Vertical and horizontal ruling lines (as thin rects or line items)."""
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
                    # stroked rectangle: four edges
                    verticals += [Rule(r.x0, r.y0, r.y1), Rule(r.x1, r.y0, r.y1)]
                    horizontals += [Rule(r.y0, r.x0, r.x1), Rule(r.y1, r.x0, r.x1)]
    return verticals, horizontals


DUAL_JOINING = set('بپتثجچحخسشصضطظعغفقکگلمنهیئيكة')


@dataclass
class Unit:
    text: str
    gid: int
    font: str


def _phantom_spaces(glyphs: list[Glyph]) -> set[int]:
    """Indexes of space glyphs that do not separate words.

    Word sometimes leaves the space that ended the previous line on top of the
    letters of the next one ("ا زنا" for ازنا). A real space takes its own slot
    between two letters, so they stand about one space width apart; under a
    phantom space the two letters touch. Lines set with condensed spacing pull
    every glyph closer by the same amount, so that amount is measured first.
    """
    order = sorted(range(len(glyphs)), key=lambda i: -glyphs[i].x1)
    solid = [i for i in order if glyphs[i].c.strip()]
    pairs = [glyphs[b].x1 - glyphs[a].x0 for a, b in zip(solid, solid[1:])
             if order.index(b) == order.index(a) + 1]
    pairs.sort()
    squeeze = pairs[len(pairs) // 2] if pairs else 0.0
    phantom: set[int] = set()
    for pos, i in enumerate(order):
        if glyphs[i].c.strip():
            continue
        right = next((j for j in reversed(order[:pos]) if glyphs[j].c.strip()), None)
        left = next((j for j in order[pos + 1:] if glyphs[j].c.strip()), None)
        if right is None or left is None:
            continue
        gap = glyphs[right].x0 - glyphs[left].x1
        width = glyphs[i].width
        # halfway between a real slot (width - 2*squeeze) and touching letters (-squeeze)
        if gap < (width - 3 * squeeze) / 2:
            phantom.add(i)
    return phantom


def _is_mark(g: Glyph) -> bool:
    return g.width < 0.01 and unicodedata.category(g.c[0]) == 'Mn'


def logical_units(glyphs: list[Glyph]) -> list[Unit]:
    """Glyphs of one visual line in logical (reading) order.

    Persian runs are read right-to-left; digit/Latin runs keep left-to-right order.
    """
    phantom = _phantom_spaces(glyphs)
    glyphs = [g for i, g in enumerate(glyphs) if i not in phantom]
    marks = [g for g in glyphs if _is_mark(g)]
    ordered = sorted((g for g in glyphs if not _is_mark(g)), key=lambda g: -round(g.x1, 2))
    # A mark has no width of its own; it is drawn over the letter it belongs to
    # (the shadda of فنّاوری sits at the left edge of ن, where ا ends).
    for m in sorted(marks, key=lambda g: -g.x0):
        host = next((i for i, g in enumerate(ordered) if g.c.strip() and g.x0 - 0.05 <= m.x0 < g.x1 - 0.05), None)
        if host is None:
            host = min(range(len(ordered)), key=lambda i: abs(ordered[i].cx - m.x0))
        while host + 1 < len(ordered) and _is_mark(ordered[host + 1]):
            host += 1
        ordered.insert(host + 1, m)
    units = [Unit(g.c, g.gid, g.font) for g in ordered]
    out: list[Unit] = []
    i = 0
    while i < len(units):
        if LTR_CHAR.match(units[i].text):
            j = i
            while j < len(units) and LTR_CHAR.match(units[j].text):
                j += 1
            out.extend(reversed(units[i:j]))
            i = j
        else:
            out.append(units[i])
            i += 1
    return out


class GlyphForms:
    """Tells which contextual form each Arabic-script glyph was drawn in.

    Word drops the zero-width non-joiner (نیم‌فاصله) when it writes the PDF, so
    "رشته‌ها" comes out as "رشتهها" in the text layer. The drawn glyphs still
    show it: the ه is in its final form although a letter follows. The booklet
    fonts name every glyph after its Unicode presentation form (uniFEEA is
    the final ه, uniFEEB the initial one), so the form is read straight from
    the embedded font instead of being guessed.
    """

    def __init__(self, doc: pymupdf.Document) -> None:
        from fontTools.ttLib import TTFont
        import io

        self.names: dict[str, list[str] | None] = {}
        self.checked = 0
        self.mismatches: list[tuple[str, str, str]] = []
        self.dangling: list[str] = []
        self.dropped: list[str] = []
        done: set[int] = set()
        for pno in range(doc.page_count):
            for xref, _ext, _type, basefont, *_ in doc.get_page_fonts(pno):
                if xref in done:
                    continue
                done.add(xref)
                buf = doc.extract_font(xref)[3]
                if not buf:
                    continue
                order = TTFont(io.BytesIO(buf)).getGlyphOrder()
                base = basefont.split('+')[-1]
                if base in self.names and self.names[base] != order:
                    self.names[base] = None   # two different fonts share a name: never trust it
                else:
                    self.names.setdefault(base, order)

    def form(self, u: 'Unit') -> str | None:
        names = self.names.get(u.font)
        if not names or not 0 <= u.gid < len(names):
            return None
        m = re.fullmatch(r'uni([0-9A-F]{4})', names[u.gid])
        if not m:
            return None
        ch = chr(int(m.group(1), 16))
        dec = unicodedata.decomposition(ch)
        if dec.startswith('<'):
            kind, code = dec[1:].split('>')[0], dec.split()[1]
            base = chr(int(code, 16))
        else:
            kind, base = 'isolated', ch
        self.checked += 1
        if base.translate(ARABIC_TO_PERSIAN) != u.text[:1].translate(ARABIC_TO_PERSIAN):
            if u.text == 'ة' and base == 'ۀ':
                # The text layer maps the drawn ۀ (رشتۀ, همۀ) to ة; keep what is printed.
                u.text = 'ۀ'
                return kind
            self.mismatches.append((u.font, u.text, names[u.gid]))
            return None
        return kind

    def drop_phantom_spaces(self, units: list['Unit']) -> list['Unit']:
        """Remove a space drawn inside a word.

        A letter drawn in its initial or medial form is joined to the letter
        after it, so a space glyph right after it cannot be a word break; it is
        the space that ended the previous line, placed on top of this one.
        """
        out: list[Unit] = []
        for i, u in enumerate(units):
            if (u.text == ' ' and out and i + 1 < len(units)
                    and len(out[-1].text) == 1 and out[-1].text in DUAL_JOINING
                    and _is_letter(units[i + 1].text[:1])
                    and self.form(out[-1]) in ('initial', 'medial')):
                self.dropped.append(''.join(x.text for x in units[max(0, i - 6):i + 7]))
                continue
            out.append(u)
        return out

    def needs_zwnj(self, u: 'Unit', nxt: 'Unit') -> bool:
        if len(u.text) != 1 or u.text not in DUAL_JOINING:
            return False
        kind = self.form(u)
        if not _is_letter(nxt.text[:1]):
            if kind in ('initial', 'medial'):
                self.dangling.append(u.text + nxt.text)
            return False
        return kind in ('isolated', 'final')


def _is_letter(ch: str) -> bool:
    return bool(ch) and '\u0620' <= ch <= '\u06FF' and ch.isalpha()


FormTable = GlyphForms


def units_text(units: list[Unit], forms: FormTable | None = None) -> str:
    parts: list[str] = []
    if forms:
        for u in units:
            if u.text == 'ة':
                forms.form(u)
    if forms:
        units = forms.drop_phantom_spaces(units)
    for i, u in enumerate(units):
        # the letter after u, past any marks drawn over u
        j = i + 1
        while j < len(units) and unicodedata.category(units[j].text[:1] or 'x') == 'Mn':
            j += 1
        zwnj = bool(forms) and j < len(units) and forms.needs_zwnj(u, units[j])
        if zwnj and j > i + 1:
            # the ZWNJ goes after the marks of u
            units[j - 1] = Unit(units[j - 1].text + '\u200c', units[j - 1].gid, units[j - 1].font)
            zwnj = False
        parts.append(u.text)
        if zwnj:
            parts.append('\u200c')
    return ''.join(parts)


def logical_text(glyphs: list[Glyph], forms: FormTable | None = None) -> str:
    if not glyphs:
        return ''
    return units_text(logical_units(glyphs), forms)


def split_lines(glyphs: list[Glyph], tol: float = 2.2) -> list[list[Glyph]]:
    glyphs = [g for g in glyphs if g.c not in ('\n', '\r')]
    if not glyphs:
        return []
    rows: list[list[Glyph]] = []
    for g in sorted(glyphs, key=lambda g: g.oy):
        if rows and abs(g.oy - rows[-1][0].oy) <= tol:
            rows[-1].append(g)
        else:
            rows.append([g])
    return rows


def normalize(text: str) -> str:
    # A tatweel standing on its own is a printed dash («رسام ( ویژه خواهران) ـ کرج»), not elongation.
    text = re.sub(r'(?<=\s)ـ(?=\s)', '-', text)
    text = unicodedata.normalize('NFC', text).translate(ARABIC_TO_PERSIAN)
    text = text.replace(' ', ' ').replace('‏', '').replace('‎', '')
    text = re.sub(r'[ \t]+', ' ', text)
    text = re.sub(r' +([\u064B-\u0652\u0670])', r'\1', text)   # harakat belong to the letter before them
    return text.strip()


def cell_text(glyphs: list[Glyph], forms: FormTable | None = None) -> str:
    lines = [logical_text(line, forms) for line in split_lines(glyphs)]
    return normalize(' '.join(normalize(l) for l in lines if normalize(l)))
