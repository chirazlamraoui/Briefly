#!/usr/bin/env python3
"""Build a styled academic DOCX from the rapport markdown files."""

from __future__ import annotations

import re
import subprocess
import tempfile
from pathlib import Path

from docx import Document
from PIL import Image
from docx.enum.text import WD_ALIGN_PARAGRAPH, WD_LINE_SPACING, WD_TAB_ALIGNMENT, WD_TAB_LEADER
from docx.oxml import OxmlElement
from docx.oxml.ns import qn
from docx.shared import Cm, Pt, RGBColor
from docx.text.paragraph import Paragraph

ROOT = Path("/Users/chiraz/Projects/Briefly/docs/rapport")
REF = ROOT / "scripts" / "reference.docx"
OUT = ROOT / "rapport-briefly.docx"
ROSE = RGBColor(0x8B, 0x4D, 0x5C)
ROSE_SOFT = RGBColor(0xB7, 0x6E, 0x7E)
ROSE_MUTED = RGBColor(0x9A, 0x75, 0x80)
INK = RGBColor(0x1A, 0x1A, 0x1A)
ROSE_FILL = "F6E8ED"
ROSE_HEADER = "E8C5CE"
ROSE_LINE = "C9A0AB"


def set_run_font(run, name: str, size: Pt | None = None, bold=None, italic=None, color=None) -> None:
    run.font.name = name
    r = run._element
    rPr = r.get_or_add_rPr()
    rFonts = rPr.get_or_add_rFonts()
    rFonts.set(qn("w:ascii"), name)
    rFonts.set(qn("w:hAnsi"), name)
    rFonts.set(qn("w:cs"), name)
    if size is not None:
        run.font.size = size
    if bold is not None:
        run.bold = bold
    if italic is not None:
        run.italic = italic
    if color is not None:
        run.font.color.rgb = color


def shade_cell(cell, hex_color: str) -> None:
    tcPr = cell._tc.get_or_add_tcPr()
    existing = tcPr.find(qn("w:shd"))
    if existing is not None:
        tcPr.remove(existing)
    shd = OxmlElement("w:shd")
    shd.set(qn("w:fill"), hex_color)
    shd.set(qn("w:val"), "clear")
    tcPr.append(shd)


def _cm_to_dxa(cm: float) -> str:
    return str(int(round(cm * 567)))


def _set_element_attr(parent, tag: str, attrs: dict[str, str]) -> None:
    el = parent.find(qn(tag))
    if el is None:
        el = OxmlElement(tag)
        parent.append(el)
    for key, val in attrs.items():
        el.set(qn(key), val)


def set_cell_width(cell, width_cm: float) -> None:
    cell.width = Cm(width_cm)
    tcPr = cell._tc.get_or_add_tcPr()
    _set_element_attr(tcPr, "w:tcW", {"w:w": _cm_to_dxa(width_cm), "w:type": "dxa"})


def set_cell_margins(cell, top=50, bottom=50, left=50, right=50) -> None:
    tcPr = cell._tc.get_or_add_tcPr()
    existing = tcPr.find(qn("w:tcMar"))
    if existing is not None:
        tcPr.remove(existing)
    tc_mar = OxmlElement("w:tcMar")
    for edge, value in (("top", top), ("left", left), ("bottom", bottom), ("right", right)):
        node = OxmlElement(f"w:{edge}")
        node.set(qn("w:w"), str(value))
        node.set(qn("w:type"), "dxa")
        tc_mar.append(node)
    tcPr.append(tc_mar)


def set_cell_valign(cell, value: str = "center") -> None:
    tcPr = cell._tc.get_or_add_tcPr()
    _set_element_attr(tcPr, "w:vAlign", {"w:val": value})


def prevent_row_split(row) -> None:
    tr_pr = row._tr.get_or_add_trPr()
    if tr_pr.find(qn("w:cantSplit")) is None:
        tr_pr.append(OxmlElement("w:cantSplit"))


def mark_header_row(row) -> None:
    tr_pr = row._tr.get_or_add_trPr()
    if tr_pr.find(qn("w:tblHeader")) is None:
        tr_pr.append(OxmlElement("w:tblHeader"))


def unmark_header_row(row) -> None:
    tr_pr = row._tr.get_or_add_trPr()
    node = tr_pr.find(qn("w:tblHeader"))
    if node is not None:
        tr_pr.remove(node)


def suppress_hyphens(paragraph) -> None:
    p_pr = paragraph._p.get_or_add_pPr()
    _set_element_attr(p_pr, "w:suppressAutoHyphens", {"w:val": "true"})


def table_col_count(table) -> int:
    """Pandoc sometimes omits w:gridCol; python-docx then reports zero columns."""
    ncols = len(table.columns)
    if ncols > 0:
        return ncols
    if not table.rows:
        return 0
    return len(table.rows[0].cells)


def set_fixed_table(table, widths_cm: list[float]) -> None:
    if not widths_cm:
        return
    table.autofit = False
    table.allow_autofit = False
    total = sum(widths_cm)
    tbl = table._tbl
    tbl_pr = tbl.tblPr
    _set_element_attr(tbl_pr, "w:tblW", {"w:w": _cm_to_dxa(total), "w:type": "dxa"})
    _set_element_attr(tbl_pr, "w:tblLayout", {"w:type": "fixed"})
    _set_element_attr(tbl_pr, "w:jc", {"w:val": "center"})

    grid = tbl.find(qn("w:tblGrid"))
    if grid is None:
        grid = OxmlElement("w:tblGrid")
        tbl_pr.addnext(grid)
    cols = grid.findall(qn("w:gridCol"))
    while len(cols) < len(widths_cm):
        grid.append(OxmlElement("w:gridCol"))
        cols = grid.findall(qn("w:gridCol"))
    for i, width in enumerate(widths_cm):
        cols[i].set(qn("w:w"), _cm_to_dxa(width))

    for row in table.rows:
        for i, cell in enumerate(row.cells):
            if i < len(widths_cm):
                set_cell_width(cell, widths_cm[i])


def set_table_borders(table) -> None:
    tbl_pr = table._tbl.tblPr
    existing = tbl_pr.find(qn("w:tblBorders"))
    if existing is not None:
        tbl_pr.remove(existing)
    borders = OxmlElement("w:tblBorders")
    for edge in ("top", "left", "bottom", "right", "insideH", "insideV"):
        node = OxmlElement(f"w:{edge}")
        node.set(qn("w:val"), "single")
        node.set(qn("w:sz"), "4")
        node.set(qn("w:space"), "0")
        node.set(qn("w:color"), ROSE_LINE)
        borders.append(node)
    tbl_pr.append(borders)


def table_column_widths(ncols: int) -> list[float]:
    usable = 16.0
    if ncols <= 0:
        return []
    if ncols == 2:
        return [4.4, 11.6]
    if ncols == 3:
        return [4.2, 7.6, 4.2]
    if ncols == 4:
        return [3.4, 4.4, 5.0, 3.2]
    if ncols >= 6:
        first = 2.4
        rest = round((usable - first) / (ncols - 1), 2)
        widths = [first] + [rest] * (ncols - 1)
        widths[-1] = round(usable - sum(widths[:-1]), 2)
        return widths
    even = round(usable / ncols, 2)
    widths = [even] * ncols
    widths[-1] = round(usable - even * (ncols - 1), 2)
    return widths


def _set_vmerge_tc(tc, mode: str) -> None:
    tc_pr = tc.get_or_add_tcPr()
    existing = tc_pr.find(qn("w:vMerge"))
    if existing is not None:
        tc_pr.remove(existing)
    vmerge = OxmlElement("w:vMerge")
    if mode == "restart":
        vmerge.set(qn("w:val"), "restart")
    tc_pr.append(vmerge)


def repeats_table_header(first_cell: str, ncols: int) -> bool:
    """Word repeats row 0 on each page when w:tblHeader is set; skip for long multi-page tables."""
    if not first_cell:
        return True
    no_repeat = {
        "Activité type",
        "Nom de sprint",
        "Cas de test",
        "Classe",
        "Table",
        "Risque",
        "Critère",
    }
    if first_cell in no_repeat:
        return False
    return True


def merge_activity_type_column(table) -> None:
    """Vertically merge repeated activity-type labels in Tableau 1.2 (CDA)."""
    if not table.rows or table.cell(0, 0).text.strip() != "Activité type":
        return
    texts: list[str] = []
    tcs = []
    cells = []
    for i in range(1, len(table.rows)):
        cell = table.rows[i].cells[0]
        texts.append(cell.text.strip())
        tcs.append(cell._tc)
        cells.append(cell)

    modes: list[str | None] = [None] * len(texts)
    group_start: int | None = None
    for i, text in enumerate(texts):
        if text:
            group_start = i
            modes[i] = "restart"
        elif group_start is not None:
            modes[i] = "continue"

    for i, mode in enumerate(modes):
        if mode is None:
            continue
        if mode == "continue":
            cells[i].text = ""
        _set_vmerge_tc(tcs[i], mode)
        if mode == "restart":
            set_cell_valign(cells[i], "center")


def style_tables(doc: Document) -> None:
    for table in doc.tables:
        ncols = table_col_count(table)
        first = table.cell(0, 0).text.strip() if table.rows else ""
        comparison = first == "Critère" and ncols >= 6
        cda_competences = first == "Activité type" and ncols == 3
        compact = comparison or ncols >= 6
        widths = table_column_widths(ncols)
        set_fixed_table(table, widths)
        set_table_borders(table)
        merge_activity_type_column(table)
        if compact:
            font_size = Pt(9)
        elif cda_competences:
            font_size = Pt(9)
        else:
            font_size = Pt(10)

        for row_i, row in enumerate(table.rows):
            prevent_row_split(row)
            if row_i == 0:
                if repeats_table_header(first, ncols):
                    mark_header_row(row)
                else:
                    unmark_header_row(row)
            for col_i, cell in enumerate(row.cells):
                set_cell_valign(cell)
                set_cell_margins(cell, 60, 60, 55, 55)
                if row_i == 0:
                    shade_cell(cell, ROSE_HEADER)
                elif col_i == 0 and comparison:
                    shade_cell(cell, ROSE_FILL)
                elif row_i % 2 == 0:
                    shade_cell(cell, ROSE_FILL)
                align = WD_ALIGN_PARAGRAPH.LEFT
                if compact and col_i > 0:
                    align = WD_ALIGN_PARAGRAPH.CENTER
                if row_i == 0:
                    align = WD_ALIGN_PARAGRAPH.CENTER
                for p in cell.paragraphs:
                    p.paragraph_format.space_before = Pt(0)
                    p.paragraph_format.space_after = Pt(0)
                    p.paragraph_format.line_spacing = 1.08
                    p.alignment = align
                    suppress_hyphens(p)
                    for run in p.runs:
                        set_run_font(
                            run,
                            "Times New Roman",
                            font_size,
                            bold=(row_i == 0 or (comparison and col_i == 0)),
                            color=ROSE if row_i == 0 else INK,
                        )


def style_font(style, name: str, size: Pt, bold=False, italic=False, color=None) -> None:
    style.font.name = name
    style.font.size = size
    style.font.bold = bold
    style.font.italic = italic
    style.font.color.rgb = color if color is not None else INK
    rPr = style.element.get_or_add_rPr()
    rFonts = rPr.get_or_add_rFonts()
    rFonts.set(qn("w:ascii"), name)
    rFonts.set(qn("w:hAnsi"), name)
    rFonts.set(qn("w:cs"), name)


def add_paragraph_style(doc: Document, name: str, size: int, *, bold=False, italic=False, center=True, space_before=0, space_after=6, uppercase=False):
    styles = doc.styles
    try:
        st = styles[name]
    except KeyError:
        st = styles.add_style(name, 1)
    style_font(st, "Times New Roman", Pt(size), bold=bold, italic=italic)
    pf = st.paragraph_format
    pf.alignment = WD_ALIGN_PARAGRAPH.CENTER if center else WD_ALIGN_PARAGRAPH.LEFT
    pf.space_before = Pt(space_before)
    pf.space_after = Pt(space_after)
    pf.line_spacing = 1.15
    if uppercase:
        st.font.small_caps = True
    return st


def build_reference() -> None:
    doc = Document()
    for section in doc.sections:
        section.page_width = Cm(21.0)
        section.page_height = Cm(29.7)
        section.left_margin = Cm(2.5)
        section.right_margin = Cm(2.5)
        section.top_margin = Cm(2.5)
        section.bottom_margin = Cm(2.5)
        section.header_distance = Cm(1.2)
        section.footer_distance = Cm(1.2)

    normal = doc.styles["Normal"]
    style_font(normal, "Times New Roman", Pt(12))
    normal.paragraph_format.line_spacing_rule = WD_LINE_SPACING.ONE_POINT_FIVE
    normal.paragraph_format.space_after = Pt(8)
    normal.paragraph_format.space_before = Pt(0)
    normal.paragraph_format.alignment = WD_ALIGN_PARAGRAPH.JUSTIFY

    h1 = doc.styles["Heading 1"]
    style_font(h1, "Times New Roman", Pt(16), bold=True, color=ROSE)
    h1.paragraph_format.space_before = Pt(18)
    h1.paragraph_format.space_after = Pt(12)
    h1.paragraph_format.page_break_before = True
    h1.paragraph_format.alignment = WD_ALIGN_PARAGRAPH.LEFT
    h1.paragraph_format.line_spacing = 1.15

    h2 = doc.styles["Heading 2"]
    style_font(h2, "Times New Roman", Pt(14), bold=True, color=ROSE)
    h2.paragraph_format.space_before = Pt(16)
    h2.paragraph_format.space_after = Pt(8)
    h2.paragraph_format.page_break_before = False

    h3 = doc.styles["Heading 3"]
    style_font(h3, "Times New Roman", Pt(12), bold=True, color=ROSE_SOFT)
    h3.paragraph_format.space_before = Pt(12)
    h3.paragraph_format.space_after = Pt(6)

    add_paragraph_style(doc, "CoverOrg", 13, bold=True, space_before=18, space_after=2)
    add_paragraph_style(doc, "CoverSub", 11, italic=True, space_before=0, space_after=10)
    add_paragraph_style(doc, "CoverLabel", 11, space_before=14, space_after=2, uppercase=True)
    add_paragraph_style(doc, "CoverDiploma", 16, bold=True, space_before=2, space_after=4)
    add_paragraph_style(doc, "CoverTitle", 20, bold=True, space_before=10, space_after=8)
    add_paragraph_style(doc, "CoverSubtitle", 13, italic=True, space_before=0, space_after=12)
    add_paragraph_style(doc, "CoverMeta", 12, space_before=2, space_after=2)
    add_paragraph_style(doc, "CoverRule", 6, space_before=8, space_after=8)

    cap = add_paragraph_style(doc, "Caption", 11, italic=True, center=True, space_before=4, space_after=14)
    cap.paragraph_format.alignment = WD_ALIGN_PARAGRAPH.CENTER

    toc = doc.styles["TOC Heading"] if "TOC Heading" in [s.name for s in doc.styles] else None
    if toc is not None:
        style_font(toc, "Times New Roman", Pt(16), bold=True, color=ROSE)

    doc.save(REF)


def add_page_number(paragraph) -> None:
    paragraph.alignment = WD_ALIGN_PARAGRAPH.CENTER
    run = paragraph.add_run()
    set_run_font(run, "Times New Roman", Pt(10), color=ROSE_MUTED)

    def fld(kind, text=None):
        el = OxmlElement("w:fldChar")
        el.set(qn("w:fldCharType"), kind)
        return el

    r = run._r
    r.append(fld("begin"))
    instr = OxmlElement("w:instrText")
    instr.set(qn("xml:space"), "preserve")
    instr.text = " PAGE "
    r.append(instr)
    r.append(fld("end"))


def insert_p_before(element, text: str, style) -> None:
    doc = element.part.document if False else None
    new_p = OxmlElement("w:p")
    element.addprevious(new_p)
    from docx.text.paragraph import Paragraph
    para = Paragraph(new_p, element._parent)
    para.style = style
    if text:
        run = para.add_run(text)
        set_run_font(run, "Times New Roman", Pt(12))
    return para


def strip_pandoc_toc(doc: Document) -> None:
    body = doc.element.body
    for sdt in list(body.findall(qn("w:sdt"))):
        gallery = sdt.find(".//" + qn("w:docPartGallery"))
        val = gallery.get(qn("w:val")) if gallery is not None else ""
        if val == "Table of Contents":
            body.remove(sdt)


_TOC_BM = 0


def add_bookmark(paragraph, name: str) -> None:
    global _TOC_BM
    _TOC_BM += 1
    bid = str(50_000 + _TOC_BM)
    start = OxmlElement("w:bookmarkStart")
    start.set(qn("w:id"), bid)
    start.set(qn("w:name"), name)
    end = OxmlElement("w:bookmarkEnd")
    end.set(qn("w:id"), bid)
    paragraph._p.insert(0, start)
    paragraph._p.append(end)


CAPTION = re.compile(r"^(Figure|Tableau) \d+\.\d+ – ")
LIST_LINES_PER_PAGE = 36


def estimate_pages(doc: Document, bids: dict[tuple[str, str], str], front_pages: int) -> dict[str, int]:
    """Rough page of each bookmarked heading or caption. Word replaces these on field update."""
    page = 1
    y = 0.0
    usable = 24.0
    marks: dict[str, int] = {}

    def bump(cm: float, bid: str | None = None) -> None:
        nonlocal page, y
        if y > 0 and y + cm > usable:
            page += 1
            y = 0.0
        if bid:
            marks[bid] = page
        y += cm

    for p in doc.paragraphs:
        st = p.style.name if p.style else ""
        t = p.text.strip()
        if st.startswith("Cover"):
            continue
        if st == "Heading 1" and t == "Remerciements":
            page, y = 2, 2.0
            bid = bids.get((st, t))
            if bid:
                marks[bid] = 2
            continue
        if t == "Table des matières":
            continue
        if st == "Heading 1":
            page += 1
            y = 0.0
            bump(1.3, bids.get((st, t)))
        elif st == "Heading 2":
            bump(1.0, bids.get((st, t)))
        elif st == "Heading 3":
            bump(0.85, bids.get((st, t)))
        elif CAPTION.match(t):
            bump(8.2, bids.get(("caption", t)))
        elif t:
            bump(0.42 + 0.26 * max(1, len(t) / 88))

    return {bid: pg + front_pages if pg >= 3 else pg for bid, pg in marks.items()}


def add_pageref(paragraph, bookmark: str, cached: int | str, size: int, bold: bool, color) -> None:
    run = paragraph.add_run(str(cached) if cached != "" else "")
    set_run_font(run, "Times New Roman", Pt(size), bold=bold, color=color)


def update_fields_on_open(doc: Document) -> None:
    settings = doc.settings.element
    node = settings.find(qn("w:updateFields"))
    if node is None:
        node = OxmlElement("w:updateFields")
        settings.append(node)
    node.set(qn("w:val"), "true")


def add_list_page(anchor, doc: Document, title: str, entries: list[tuple[str, str, int, str, bool, object]]) -> None:
    heading = OxmlElement("w:p")
    anchor._p.addprevious(heading)
    heading_p = Paragraph(heading, anchor._parent)
    heading_p.style = doc.styles["Heading 1"]
    run = heading_p.add_run(title)
    set_run_font(run, "Times New Roman", Pt(16), bold=True, color=ROSE)

    for text, bid, page, indent_cm, bold, color in entries:
        size = 11 if indent_cm >= 1.4 else 12
        node = OxmlElement("w:p")
        anchor._p.addprevious(node)
        para = Paragraph(node, anchor._parent)
        para.style = doc.styles["Normal"]
        para.paragraph_format.space_after = Pt(3)
        para.paragraph_format.space_before = Pt(1)
        para.paragraph_format.line_spacing = 1.15
        para.paragraph_format.alignment = WD_ALIGN_PARAGRAPH.LEFT
        para.paragraph_format.left_indent = Cm(indent_cm)
        para.paragraph_format.tab_stops.add_tab_stop(Cm(16), WD_TAB_ALIGNMENT.RIGHT, WD_TAB_LEADER.DOTS)
        run = para.add_run(text + "\t")
        set_run_font(run, "Times New Roman", Pt(size), bold=bold, color=color)
        add_pageref(para, bid, page, size, bold, color)


def add_toc(doc: Document) -> None:
    global _TOC_BM
    _TOC_BM = 0
    anchor = None
    items: list[tuple[str, str, str]] = []
    figures: list[tuple[str, str]] = []
    tables: list[tuple[str, str]] = []
    bids: dict[tuple[str, str], str] = {}
    skip_h2 = {"Introduction", "Conclusion"}
    for p in doc.paragraphs:
        name = p.style.name if p.style else ""
        title = p.text.strip()
        if anchor is None and title in ("Résumé", "Introduction générale") and name == "Heading 1":
            anchor = p
        if CAPTION.match(title):
            target = figures if title.startswith("Figure") else tables
            bid = f"{'fig' if target is figures else 'tab'}{len(target) + 1}"
            add_bookmark(p, bid)
            target.append((title, bid))
            bids[("caption", title)] = bid
            continue
        if name not in ("Heading 1", "Heading 2", "Heading 3") or not title:
            continue
        if title == "Table des matières":
            continue
        if name == "Heading 2" and title in skip_h2:
            continue
        bid = f"toc{len(items)+1}"
        add_bookmark(p, bid)
        items.append((name, title, bid))
        bids[(name, title)] = bid
    if anchor is None or not items:
        return

    front_pages = sum(-(-(len(lst) + 2) // LIST_LINES_PER_PAGE) for lst in (items, figures, tables) if lst)
    pages = estimate_pages(doc, bids, front_pages)

    indents = {"Heading 1": 0.0, "Heading 2": 0.7, "Heading 3": 1.4}
    toc_entries = [
        (text, bid, pages.get(bid, ""), indents[lvl], lvl == "Heading 1", ROSE if lvl == "Heading 1" else INK)
        for lvl, text, bid in items
    ]
    add_list_page(anchor, doc, "Table des matières", toc_entries)
    if figures:
        add_list_page(anchor, doc, "Table des figures", [(t, b, pages.get(b, ""), 0.0, False, INK) for t, b in figures])
    if tables:
        add_list_page(anchor, doc, "Liste des tableaux", [(t, b, pages.get(b, ""), 0.0, False, INK) for t, b in tables])


def polish(path: Path) -> None:
    doc = Document(path)
    for section in doc.sections:
        section.page_width = Cm(21.0)
        section.page_height = Cm(29.7)
        section.left_margin = Cm(2.5)
        section.right_margin = Cm(2.5)
        section.top_margin = Cm(2.5)
        section.bottom_margin = Cm(2.5)
        section.different_first_page_header_footer = True
        footer = section.footer
        footer.is_linked_to_previous = False
        for p in footer.paragraphs:
            p.text = ""
        p = footer.paragraphs[0] if footer.paragraphs else footer.add_paragraph()
        add_page_number(p)
        section.first_page_footer.is_linked_to_previous = False
        if section.first_page_footer.paragraphs:
            section.first_page_footer.paragraphs[0].text = ""

    try:
        body_style = doc.styles["Body Text"]
        style_font(body_style, "Times New Roman", Pt(12))
        body_style.paragraph_format.line_spacing_rule = WD_LINE_SPACING.ONE_POINT_FIVE
        body_style.paragraph_format.space_after = Pt(8)
        body_style.paragraph_format.alignment = WD_ALIGN_PARAGRAPH.JUSTIFY
    except KeyError:
        pass

    for para in doc.paragraphs:
        style_name = para.style.name if para.style is not None else ""
        text = para.text.strip()
        if style_name.startswith("Cover"):
            para.alignment = WD_ALIGN_PARAGRAPH.CENTER
            for run in para.runs:
                set_run_font(run, "Times New Roman")
        if style_name == "CoverOrg":
            for run in para.runs:
                set_run_font(run, "Times New Roman", Pt(13), bold=True, color=ROSE)
        if style_name in ("CoverTitle", "CoverDiploma"):
            for run in para.runs:
                set_run_font(run, "Times New Roman", color=ROSE, bold=True)
        if style_name == "CoverSubtitle":
            for run in para.runs:
                set_run_font(run, "Times New Roman", Pt(13), italic=True, color=ROSE_SOFT)
        if style_name == "CoverLabel":
            for run in para.runs:
                run.font.all_caps = True
                run.font.size = Pt(11)
                run.font.color.rgb = ROSE_MUTED
        if style_name.startswith("Heading"):
            tone = ROSE_SOFT if style_name == "Heading 3" else ROSE
            for run in para.runs:
                if run.font.color is None or run.font.color.rgb is None:
                    run.font.color.rgb = tone
                else:
                    run.font.color.rgb = tone
        if text.startswith("Figure ") or text.startswith("Tableau "):
            para.alignment = WD_ALIGN_PARAGRAPH.CENTER
            para.paragraph_format.space_before = Pt(4)
            para.paragraph_format.space_after = Pt(8)
            para.paragraph_format.keep_with_next = text.startswith("Tableau ")
            if text.startswith("Tableau 1.1"):
                para.paragraph_format.page_break_before = True
            for run in para.runs:
                set_run_font(run, "Times New Roman", Pt(11), italic=True, bold=False, color=ROSE_MUTED)
        if style_name in ("Normal", "Body Text"):
            para.alignment = WD_ALIGN_PARAGRAPH.JUSTIFY
            para.paragraph_format.line_spacing_rule = WD_LINE_SPACING.ONE_POINT_FIVE
        if re.match(r"^\[\d+\] https?://", text):
            para.alignment = WD_ALIGN_PARAGRAPH.LEFT
            para.paragraph_format.line_spacing = 1.15
            para.paragraph_format.space_after = Pt(3)

        if style_name in ("CoverDiploma", "CoverSubtitle"):
            pPr = para._p.get_or_add_pPr()
            pBdr = OxmlElement("w:pBdr")
            bottom = OxmlElement("w:bottom")
            bottom.set(qn("w:val"), "single")
            bottom.set(qn("w:sz"), "8")
            bottom.set(qn("w:space"), "8")
            bottom.set(qn("w:color"), ROSE_LINE)
            pBdr.append(bottom)
            pPr.append(pBdr)

    style_tables(doc)
    drop_alt_captions(doc)

    if doc.paragraphs:
        first = doc.paragraphs[0]
        first.alignment = WD_ALIGN_PARAGRAPH.CENTER
        first.paragraph_format.space_before = Pt(36)
        first.paragraph_format.space_after = Pt(18)

    strip_pandoc_toc(doc)
    add_toc(doc)
    pair_screenshots(doc)
    doc.save(path)


PRINT_DIR = ROOT / "captures" / "_print"
IMAGE_REF = re.compile(r"!\[([^\]]*)\]\(captures/([^)\s]+)\)(\{width=[^}]*\})?")
SCREEN_MAX_H = 8.6
PHONE_MAX_H = 9.2
DIAGRAM_MAX_H = 11.0
MAX_W = 14.5


def _is_uniform(pixels: list[tuple[int, int, int]], tolerance: int = 10) -> bool:
    first = pixels[0]
    return all(
        abs(px[0] - first[0]) <= tolerance
        and abs(px[1] - first[1]) <= tolerance
        and abs(px[2] - first[2]) <= tolerance
        for px in pixels
    )


def trim_uniform_edges(im: Image.Image, pad: int = 14) -> Image.Image:
    """Cut empty background bands on each edge of a screenshot."""
    rgb = im.convert("RGB")
    w, h = rgb.size
    pix = rgb.load()
    step = 2 if w * h > 2_000_000 else 1
    top, bottom, left, right = 0, h - 1, 0, w - 1

    def row(y: int) -> list[tuple[int, int, int]]:
        return [pix[x, y] for x in range(left, right + 1, step)]

    def col(x: int) -> list[tuple[int, int, int]]:
        return [pix[x, y] for y in range(top, bottom + 1, step)]

    while bottom > top + 10 and _is_uniform(row(bottom)):
        bottom -= 1
    while top < bottom - 10 and _is_uniform(row(top)):
        top += 1
    while right > left + 10 and _is_uniform(col(right)):
        right -= 1
    while left < right - 10 and _is_uniform(col(left)):
        left += 1

    return rgb.crop((
        max(0, left - pad),
        max(0, top - pad),
        min(w, right + pad + 1),
        min(h, bottom + pad + 1),
    ))


AUTH_CARDS = {"web-login", "web-forgot-password", "web-reset-password"}
PAIR_ROW_W = 15.6
PAIR_ROW_MAX_H = 8.0
PAIR_MARK = "pair|"
_ASSEMBLE_MTIME = Path(__file__).stat().st_mtime


def is_pairable(stem: str) -> bool:
    return stem in AUTH_CARDS or stem.startswith("mobile-")


def print_image(name: str) -> Path:
    src = ROOT / "captures" / name
    PRINT_DIR.mkdir(exist_ok=True)
    out = PRINT_DIR / (Path(name).stem + ".png")
    if out.exists() and out.stat().st_mtime >= max(src.stat().st_mtime, _ASSEMBLE_MTIME):
        return out
    im = Image.open(src).convert("RGB")
    if Path(name).stem in AUTH_CARDS:
        # The language pill in the top band would otherwise stop the trim at the page edge.
        im = im.crop((0, int(im.size[1] * 0.08), im.size[0], im.size[1]))
    trimmed = trim_uniform_edges(im)
    trimmed.save(out, "PNG", optimize=True)
    return out


def display_width_cm(name: str, path: Path, requested: float | None) -> float:
    w, h = Image.open(path).size
    aspect = h / w if w else 1.0
    stem = Path(name).stem
    if stem in AUTH_CARDS:
        width, max_h = 7.2, PAIR_ROW_MAX_H
    elif stem == "web-preferences":
        width, max_h = 6.0, SCREEN_MAX_H
    elif stem.startswith("mobile-") and aspect >= 1.2:
        width, max_h = 6.2, PHONE_MAX_H
    elif stem.startswith("mobile-"):
        width, max_h = 13.0, PHONE_MAX_H
    elif stem.startswith(("web-", "existant-")):
        width, max_h = 14.0, SCREEN_MAX_H
    else:
        width, max_h = requested or MAX_W, DIAGRAM_MAX_H
    width = min(width, MAX_W)
    if width * aspect > max_h:
        width = max_h / aspect
    return round(width, 2)


def rewrite_images(markdown: str) -> str:
    def replace(match: re.Match) -> str:
        alt, name, attrs = match.group(1), match.group(2), match.group(3) or ""
        if name.startswith("_print/") or name.startswith("logo-"):
            return match.group(0)
        requested = None
        width_match = re.search(r"width=([\d.]+)cm", attrs)
        if width_match:
            requested = float(width_match.group(1))
        path = print_image(name)
        width = display_width_cm(name, path, requested)
        if is_pairable(Path(name).stem):
            alt = PAIR_MARK + alt
        return f"![{alt}](captures/_print/{path.name}){{width={width}cm}}"

    return IMAGE_REF.sub(replace, markdown)


EMU_PER_CM = 360000


def _picture_parts(paragraph):
    extent = paragraph._p.find(".//" + qn("wp:extent"))
    doc_pr = paragraph._p.find(".//" + qn("wp:docPr"))
    xfrm_ext = paragraph._p.find(".//" + qn("a:ext"))
    return extent, doc_pr, xfrm_ext


def _is_pair_picture(paragraph) -> bool:
    if not paragraph._p.xpath(".//pic:pic"):
        return False
    _, doc_pr, _ = _picture_parts(paragraph)
    return doc_pr is not None and (doc_pr.get("descr") or "").startswith(PAIR_MARK)


def _resize_picture(paragraph, width_cm: float, height_cm: float) -> None:
    extent, _, xfrm_ext = _picture_parts(paragraph)
    cx, cy = str(int(width_cm * EMU_PER_CM)), str(int(height_cm * EMU_PER_CM))
    for node in (extent, xfrm_ext):
        if node is not None:
            node.set("cx", cx)
            node.set("cy", cy)


def _picture_aspect(paragraph) -> float:
    extent, _, _ = _picture_parts(paragraph)
    return int(extent.get("cy")) / int(extent.get("cx"))


def _strip_pair_mark(paragraph) -> None:
    _, doc_pr, _ = _picture_parts(paragraph)
    if doc_pr is not None:
        doc_pr.set("descr", (doc_pr.get("descr") or "").removeprefix(PAIR_MARK))


def _hide_borders(table) -> None:
    tbl_pr = table._tbl.tblPr
    existing = tbl_pr.find(qn("w:tblBorders"))
    if existing is not None:
        tbl_pr.remove(existing)
    borders = OxmlElement("w:tblBorders")
    for edge in ("top", "left", "bottom", "right", "insideH", "insideV"):
        node = OxmlElement(f"w:{edge}")
        node.set(qn("w:val"), "nil")
        borders.append(node)
    tbl_pr.append(borders)


def pair_screenshots(doc: Document) -> None:
    """Place consecutive phone or form screenshots side by side, each with its own caption."""
    paragraphs = doc.paragraphs
    i = 0
    while i + 3 < len(paragraphs):
        first_img, first_cap, second_img, second_cap = paragraphs[i:i + 4]
        if not (
            _is_pair_picture(first_img)
            and first_cap.text.strip().startswith("Figure ")
            and _is_pair_picture(second_img)
            and second_cap.text.strip().startswith("Figure ")
        ):
            i += 1
            continue

        aspects = [_picture_aspect(first_img), _picture_aspect(second_img)]
        row_h = min(PAIR_ROW_MAX_H, PAIR_ROW_W / sum(1 / a for a in aspects))
        widths = [row_h / a for a in aspects]
        gap = 0.6
        cell_widths = [w + gap for w in widths]

        table = doc.add_table(rows=1, cols=2)
        first_img._p.addprevious(table._tbl)
        set_fixed_table(table, cell_widths)
        _hide_borders(table)
        prevent_row_split(table.rows[0])

        for cell, img, cap, width in zip(table.rows[0].cells, (first_img, second_img), (first_cap, second_cap), widths):
            for empty in list(cell.paragraphs):
                empty._p.getparent().remove(empty._p)
            _resize_picture(img, width, width * _picture_aspect(img))
            _strip_pair_mark(img)
            img.alignment = WD_ALIGN_PARAGRAPH.CENTER
            img.paragraph_format.space_before = Pt(0)
            img.paragraph_format.space_after = Pt(2)
            cap.paragraph_format.space_after = Pt(0)
            cap.paragraph_format.line_spacing = 1.1
            for run in cap.runs:
                run.font.size = Pt(10)
            cell._tc.append(img._p)
            cell._tc.append(cap._p)
            set_cell_valign(cell, "bottom")

        spacer = OxmlElement("w:p")
        table._tbl.addnext(spacer)
        paragraphs = doc.paragraphs
        i = 0

    for para in doc.paragraphs:
        if para._p.xpath(".//pic:pic"):
            _strip_pair_mark(para)


def drop_alt_captions(doc: Document) -> None:
    """Remove pandoc's alt-text caption when a numbered caption follows, and keep picture with caption."""
    paragraphs = doc.paragraphs
    for i, para in enumerate(paragraphs):
        if not para._p.xpath(".//pic:pic") or i + 2 >= len(paragraphs):
            continue
        p_pr = para._p.get_or_add_pPr()
        if p_pr.find(qn("w:keepNext")) is None:
            p_pr.append(OxmlElement("w:keepNext"))
        alt = paragraphs[i + 1]
        numbered = paragraphs[i + 2]
        if numbered.text.strip().startswith("Figure ") and alt.text.strip() and not alt._p.xpath(".//pic:pic"):
            alt._p.getparent().remove(alt._p)


def main() -> None:
    build_reference()
    files = [
        "00-preliminaires.md",
        "01-introduction.md",
        "02-chapitre-1-etude-prealable.md",
        "03-chapitre-2-analyse-besoins.md",
        "04-chapitre-3-conception.md",
        "05-chapitre-4-realisation.md",
        "05b-chapitre-5-deploiement.md",
        "06-conclusion-netographie.md",
    ]
    build_dir = Path(tempfile.mkdtemp(prefix="rapport-md-"))
    sources = []
    for f in files:
        target = build_dir / f
        target.write_text(rewrite_images((ROOT / f).read_text(encoding="utf-8")), encoding="utf-8")
        sources.append(str(target))
    cmd = [
        "pandoc",
        *sources,
        "-o",
        str(OUT),
        "--resource-path",
        str(ROOT),
        "--reference-doc",
        str(REF),
        "--metadata",
        "lang=fr",
    ]
    subprocess.check_call(cmd)
    polish(OUT)
    print("wrote", OUT, "size", OUT.stat().st_size)


if __name__ == "__main__":
    main()
