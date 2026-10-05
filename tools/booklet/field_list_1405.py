"""Read a group's field list (رشته‌های تحصیلی گروه آزمایشی ...، مقطع تحصیلی و نوع گزینش) from the booklet.

Usage: python3 field_list_1405.py <booklet.pdf> <first_page> <last_page> <academic_fields.json>

The list is a five-column table (ترتیب، مقطع تحصیلی، نام رشته، نوع گزینش، کد ردیف رشته); the
texts are kept as printed. A name marked with a star ("پزشکی *") is stored without it and gets
gender_min_30_percent_note, and the star's footnote is kept as star_note. The orders must run
1, 2, 3, … without a gap and every numbered row on the pages must be read, or nothing is written.
"""
from __future__ import annotations

import json
import os
import sys

import pymupdf

from booklet_pages import Band, Loose, read_page
from pdf_cells import GlyphForms

HEADER = ['ترتیب', 'مقطع تحصیلی', 'نام رشته', 'نوع گزینش (دوره‌های روزانه)', 'کد ردیف رشته']
ASCII_DIGITS = str.maketrans('۰۱۲۳۴۵۶۷۸۹٠١٢٣٤٥٦٧٨٩', '01234567890123456789')


def numbered(cells: list[str]) -> bool:
    return len(cells) == 5 and all(c.translate(ASCII_DIGITS).strip().isdigit() for c in (cells[0], cells[4]))


def read(pdf: str, first: int, last: int) -> tuple[list[dict], str | None]:
    doc = pymupdf.open(pdf)
    forms = GlyphForms(doc)
    fields: list[dict] = []
    rows_on_pages = 0
    star_lines: list[str] = []
    for page in range(first, last + 1):
        in_table = False
        for it in read_page(doc, page - 1, forms):
            if isinstance(it, Loose) and it.text.lstrip().startswith('*'):
                star_lines.append(it.text.lstrip(' *'))
            if not isinstance(it, Band):
                continue
            rows_on_pages += numbered(it.cells)
            if it.cells == HEADER:
                in_table = True
                continue
            if not in_table or len(it.cells) != 5:
                in_table = False
                continue
            if not numbered(it.cells):
                in_table = False
                continue
            order, degree, name, selection, row_code = it.cells
            starred = name.rstrip().endswith('*')
            fields.append({
                'order': int(order.translate(ASCII_DIGITS)),
                'degree_level': degree,
                'name': name.rstrip(' *') if starred else name,
                'selection_type': selection,
                'field_row_code': int(row_code.translate(ASCII_DIGITS)),
                'gender_min_30_percent_note': starred,
                'page': page,
            })
    orders = [f['order'] for f in fields]
    if orders != list(range(1, len(fields) + 1)):
        raise SystemExit(f'field list orders are not 1..{len(fields)}: {orders}')
    if rows_on_pages != len(fields):
        raise SystemExit(f'{rows_on_pages} numbered rows on the pages but {len(fields)} read as the field list')
    starred = any(f['gender_min_30_percent_note'] for f in fields)
    if starred and len(star_lines) != 1:
        raise SystemExit(f'starred fields need one footnote starting with "*", found {star_lines}')
    return fields, star_lines[0] if starred else None


def main() -> None:
    pdf, first, last, out = sys.argv[1], int(sys.argv[2]), int(sys.argv[3]), sys.argv[4]
    fields, star_note = read(pdf, first, last)
    doc = {
        'source': f'{os.path.basename(pdf)}، جدول رشته‌های تحصیلی صفحه‌های {first} تا {last} (شماره صفحه PDF)',
        'star_note': star_note,
        'transcribed_from': 'PDF text layer, tools/booklet/field_list_1405.py',
        'fields': [{k: v for k, v in f.items() if k != 'page'} for f in fields],
    }
    with open(out, 'w', encoding='utf-8') as fh:
        json.dump(doc, fh, ensure_ascii=False, indent=1)
        fh.write('\n')
    print(len(fields), 'fields')


if __name__ == '__main__':
    main()
