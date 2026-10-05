"""Split booklet pages into table rows and loose text (section titles)."""
from __future__ import annotations

from dataclasses import dataclass

import pymupdf

from pdf_cells import GlyphForms, Glyph, Rule, _cluster, cell_text, logical_text, logical_units, normalize, page_glyphs, page_rules, split_lines


@dataclass
class Band:
    page: int
    y0: float
    y1: float
    cells: list[str]          # right-to-left: index 0 is the right-most column
    edges: list[float]        # x boundaries, descending
    glyphs: list[list[Glyph]]


@dataclass
class Loose:
    page: int
    y0: float
    y1: float
    text: str
    font: str
    size: float


# (page, column, first row y, rows) of every cell that spans several table rows
MERGED_CELLS: list[tuple[int, int, float, int]] = []


def merge_spanning_cells(bands: list[Band], horizontals: list[Rule], forms: GlyphForms | None) -> None:
    """Give a cell that spans several rows (no border between them in its column) to each of those rows.

    Rows are cut at every horizontal line of the page, so the text of a merged cell would otherwise be
    split by height over the rows it spans (ensani pp. 417-418, where one note covers twelve codes).
    """
    def bordered(y: float, lo: float, hi: float) -> bool:
        lo, hi = lo + 1, hi - 1
        covered = sum(max(0.0, min(hi, h.hi) - max(lo, h.lo)) for h in horizontals if abs(h.a - y) <= 1.0)
        return covered >= (hi - lo) / 2

    runs: list[list[Band]] = []
    for band in bands:
        prev = runs[-1][-1] if runs else None
        if (prev is not None and prev.y1 == band.y0 and len(prev.edges) == len(band.edges)
                and max(abs(a - b) for a, b in zip(prev.edges, band.edges)) <= 1.2):
            runs[-1].append(band)
        else:
            runs.append([band])
    for run in runs:
        for ci in range(len(run[0].edges) - 1):
            start = 0
            for i in range(1, len(run) + 1):
                if i < len(run) and not bordered(run[i].y0, run[i].edges[ci + 1], run[i].edges[ci]):
                    continue
                group = run[start:i]
                if len(group) > 1:
                    glyphs = [g for band in group for g in band.glyphs[ci]]
                    text = cell_text(glyphs, forms)
                    for band in group:
                        band.cells[ci] = text
                        band.glyphs[ci] = glyphs
                    MERGED_CELLS.append((group[0].page, ci, round(group[0].y0, 1), len(group)))
                start = i


def read_page(doc: pymupdf.Document, index: int, forms: GlyphForms | None = None) -> list[Band | Loose]:
    page = doc[index]
    glyphs = page_glyphs(page)
    verticals, horizontals = page_rules(page)
    hy = _cluster([h.a for h in horizontals if h.hi - h.lo > 20], 1.0)
    items: list[Band | Loose] = []
    used: set[int] = set()
    for top, bottom in zip(hy, hy[1:]):
        if bottom - top < 3:
            continue
        mid = (top + bottom) / 2
        xs = _cluster([v.a for v in verticals if v.lo - 1 <= mid <= v.hi + 1], 1.2)
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
        items.append(Band(index + 1, top, bottom, [cell_text(c, forms) for c in cells], edges, cells))
    merge_spanning_cells([it for it in items if isinstance(it, Band)], horizontals, forms)
    loose = [g for gi, g in enumerate(glyphs) if gi not in used]
    for line in split_lines(loose, tol=3.0):
        text = normalize(logical_text(line, forms))
        if text:
            items.append(Loose(index + 1, min(g.y0 for g in line), max(g.y1 for g in line), text, line[0].font, line[0].size))
    items.sort(key=lambda it: (it.y0, 0 if isinstance(it, Loose) else 1))
    return items
