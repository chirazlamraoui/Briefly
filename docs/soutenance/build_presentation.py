#!/usr/bin/env python3
"""Génère la soutenance Briefly (contenu et organisation)."""

from __future__ import annotations

from pathlib import Path

from PIL import Image
from pptx import Presentation
from pptx.dml.color import RGBColor
from pptx.enum.shapes import MSO_SHAPE
from pptx.enum.text import MSO_ANCHOR, PP_ALIGN
from pptx.oxml.ns import qn
from pptx.util import Emu, Inches, Pt

ROOT = Path(__file__).resolve().parents[2]
CAP = ROOT / "docs" / "rapport" / "captures"
OUT_DIR = Path(__file__).resolve().parent
ASSET = OUT_DIR / "_assets"
PPTX = OUT_DIR / "Soutenance_Briefly_editable.pptx"

W = Inches(13.333)
H = Inches(7.5)

# Palette du mémoire : rose #8B4D5C, fond #F6E8ED, filet #E8C5CE.
BG = RGBColor(0xFB, 0xF6, 0xF8)
INK = RGBColor(0x1A, 0x1A, 0x1A)
MUTED = RGBColor(0x5E, 0x4E, 0x53)
ROSE = RGBColor(0x8B, 0x4D, 0x5C)
ROSE_DEEP = RGBColor(0x6E, 0x3A, 0x48)
ROSE_SOFT = RGBColor(0xF6, 0xE8, 0xED)
ROSE_MID = RGBColor(0xE8, 0xC5, 0xCE)
CARD = RGBColor(0xFF, 0xFF, 0xFF)
LINE = RGBColor(0xE8, 0xC5, 0xCE)
WHITE = RGBColor(0xFF, 0xFF, 0xFF)

FONT = "Arial"
TOTAL = 27


def emu(inches: float) -> Emu:
    return Inches(inches)


def set_run(paragraph, text, size, bold=False, color=INK, align=None, font=FONT):
    if align is not None:
        paragraph.alignment = align
    run = paragraph.add_run()
    run.text = text
    run.font.name = font
    run.font.size = Pt(size)
    run.font.bold = bold
    run.font.color.rgb = color
    run.font.italic = False
    return run


def add_text(slide, text, x, y, w, h, size=16, bold=False, color=INK, align=PP_ALIGN.LEFT, anchor=MSO_ANCHOR.TOP):
    shape = slide.shapes.add_textbox(emu(x), emu(y), emu(w), emu(h))
    tf = shape.text_frame
    tf.word_wrap = True
    tf.auto_size = None
    tf.margin_left = Emu(0)
    tf.margin_right = Emu(0)
    tf.margin_top = Emu(0)
    tf.margin_bottom = Emu(0)
    tf.paragraphs[0].alignment = align
    try:
        tf._txBody.bodyPr.set("anchor", {MSO_ANCHOR.TOP: "t", MSO_ANCHOR.MIDDLE: "ctr", MSO_ANCHOR.BOTTOM: "b"}[anchor])
    except Exception:
        pass
    set_run(tf.paragraphs[0], text, size, bold, color, align)
    return shape


def add_lines(slide, lines, x, y, w, h, align=PP_ALIGN.LEFT, anchor=MSO_ANCHOR.TOP):
    """lines: list of (text, size, bold, color, space_after)."""
    shape = slide.shapes.add_textbox(emu(x), emu(y), emu(w), emu(h))
    tf = shape.text_frame
    tf.word_wrap = True
    tf.auto_size = None
    tf.margin_left = emu(0.02)
    tf.margin_right = emu(0.02)
    tf.margin_top = emu(0.02)
    tf.margin_bottom = emu(0.02)
    anchor_map = {MSO_ANCHOR.TOP: "t", MSO_ANCHOR.MIDDLE: "ctr", MSO_ANCHOR.BOTTOM: "b"}
    tf._txBody.bodyPr.set("anchor", anchor_map[anchor])
    for i, item in enumerate(lines):
        text, size, bold, color, space = item
        p = tf.paragraphs[0] if i == 0 else tf.add_paragraph()
        p.alignment = align
        p.space_after = Pt(space)
        p.space_before = Pt(0)
        set_run(p, text, size, bold, color, align)
    return shape


def rect(slide, x, y, w, h, fill, line=None, radius=0.1):
    kind = MSO_SHAPE.ROUNDED_RECTANGLE if radius else MSO_SHAPE.RECTANGLE
    shape = slide.shapes.add_shape(kind, emu(x), emu(y), emu(w), emu(h))
    if radius:
        shape.adjustments[0] = radius
    shape.fill.solid()
    shape.fill.fore_color.rgb = fill
    if line is None:
        shape.line.fill.background()
    else:
        shape.line.color.rgb = line
        shape.line.width = Pt(1.25)
    sp_pr = shape._element.spPr
    effect = sp_pr.find(qn("a:effectLst"))
    if effect is not None:
        sp_pr.remove(effect)
    return shape


def no_shadow(shape):
    sp_pr = shape._element.spPr
    effect = sp_pr.find(qn("a:effectLst"))
    if effect is not None:
        sp_pr.remove(effect)


def bg(slide):
    fill = slide.background.fill
    fill.solid()
    fill.fore_color.rgb = BG


def notes(slide, text):
    slide.notes_slide.notes_text_frame.text = text


def footer(slide, number, competence=""):
    rect(slide, 0.5, 6.98, 12.33, 0.012, ROSE_MID, radius=0)
    add_text(slide, "Briefly", 0.5, 7.08, 1.3, 0.28, 11, True, ROSE)
    add_text(slide, "Chiraz Lamraoui", 1.85, 7.08, 3.2, 0.28, 11, False, MUTED)
    if competence:
        add_text(slide, competence, 5.0, 7.08, 6.5, 0.28, 11, False, ROSE, PP_ALIGN.RIGHT)
    add_text(slide, f"{number:02d}", 11.7, 7.08, 1.1, 0.28, 11, True, ROSE, PP_ALIGN.RIGHT)


def kicker(slide, section):
    add_text(slide, section.upper(), 0.5, 0.28, 10, 0.26, 11, True, ROSE)


def title(slide, text, y=0.52, h=0.5, size=28):
    add_text(slide, text, 0.5, y, 12.3, h, size, True, INK)


def trim(src: Path, name: str, pad=12) -> Path:
    im = Image.open(src).convert("RGBA")
    bg_px = im.getpixel((0, 0))
    pixels = im.load()
    w, h = im.size
    minx, miny, maxx, maxy = w, h, 0, 0
    for y in range(h):
        for x in range(w):
            r, g, b, a = pixels[x, y]
            if a < 8:
                continue
            if abs(r - bg_px[0]) < 8 and abs(g - bg_px[1]) < 8 and abs(b - bg_px[2]) < 8 and r > 248:
                continue
            minx = min(minx, x)
            miny = min(miny, y)
            maxx = max(maxx, x)
            maxy = max(maxy, y)
    if maxx <= minx or maxy <= miny:
        out = ASSET / name
        im.save(out)
        return out
    minx = max(0, minx - pad)
    miny = max(0, miny - pad)
    maxx = min(w - 1, maxx + pad)
    maxy = min(h - 1, maxy + pad)
    cropped = im.crop((minx, miny, maxx + 1, maxy + 1))
    out = ASSET / name
    cropped.save(out)
    return out


def crop_ui(src: Path, name: str) -> Path:
    """Coupe le blanc sous les captures d'écran."""
    im = Image.open(src).convert("RGB")
    w, h = im.size
    px = im.load()

    def inky(y: int) -> bool:
        n = 0
        for x in range(0, w, 10):
            r, g, b = px[x, y]
            if r < 238 or g < 238 or b < 238:
                n += 1
                if n > 6:
                    return True
        return False

    rows = [y for y in range(h) if inky(y)]
    out = ASSET / name
    if not rows:
        im.save(out)
        return out
    top = max(0, rows[0] - 6)
    bot = min(h, rows[-1] + 20)
    im.crop((0, top, w, bot)).save(out)
    return out


def fit(slide, path: Path, x, y, w, h, valign="middle"):
    """Place l'image dans la boîte, ratio conservé."""
    with Image.open(path) as im:
        iw, ih = im.size
    box_ratio = w / h
    img_ratio = iw / ih
    if img_ratio > box_ratio:
        dw = w
        dh = w / img_ratio
    else:
        dh = h
        dw = h * img_ratio
    ox = x + (w - dw) / 2
    if valign == "top":
        oy = y
    elif valign == "bottom":
        oy = y + (h - dh)
    else:
        oy = y + (h - dh) / 2
    return slide.shapes.add_picture(str(path), emu(ox), emu(oy), emu(dw), emu(dh))


def new(prs, band=True):
    slide = prs.slides.add_slide(prs.slide_layouts[6])
    bg(slide)
    if band:
        rect(slide, 0, 0, 13.333, 0.07, ROSE, radius=0)
    return slide


def build():
    ASSET.mkdir(parents=True, exist_ok=True)
    uc = trim(CAP / "diag-uc-overview.png", "uc.png")
    arch = trim(CAP / "diag-arch-unified.png", "arch.png")
    seq = trim(CAP / "diag-seq-progress.png", "seq.png")

    prs = Presentation()
    prs.slide_width = W
    prs.slide_height = H
    prs.core_properties.title = "Soutenance Briefly — Chiraz Lamraoui"
    prs.core_properties.author = "Chiraz Lamraoui"
    prs.core_properties.subject = "Conception et développement de Briefly"

    slide_garde(prs)
    slide_plan(prs)
    slide_referentiel(prs)
    slide_entreprise(prs)
    slide_probleme(prs)
    slide_existant(prs)
    slide_solution(prs)
    slide_roles(prs)
    slide_cu(prs, uc)
    slide_matin(prs)
    slide_membre_vue(prs)
    slide_chef_vue(prs)
    slide_admin_vue(prs)
    slide_fonctions(prs)
    slide_cas_detail(prs)
    slide_sprints(prs)
    slide_sequence(prs, seq)
    slide_donnees(prs)
    slide_schema(prs)
    slide_archi(prs, arch)
    slide_web(prs)
    slide_mobile(prs)
    slide_securite(prs)
    slide_tests(prs)
    slide_deploy(prs)
    slide_bilan(prs)
    slide_merci(prs)

    finalize(prs)
    prs.save(PPTX)
    print(PPTX)


def finalize(prs):
    """Retire le style de thème et nomme chaque objet, pour édition dans PowerPoint."""
    style_tag = qn("p:style")
    for index, slide in enumerate(prs.slides, start=1):
        for style in list(slide._element.iter(style_tag)):
            parent = style.getparent()
            if parent is not None:
                parent.remove(style)
        seen = {}
        for shape in slide.shapes:
            text = ""
            if shape.has_text_frame:
                text = " ".join(
                    paragraph.text.strip()
                    for paragraph in shape.text_frame.paragraphs
                    if paragraph.text.strip()
                )
            if text:
                label = text.replace("\n", " ")
                if len(label) > 42:
                    label = label[:39].rstrip() + "…"
                base = f"{index:02d} {label}"
            elif shape.shape_type is not None and "PICTURE" in str(shape.shape_type):
                base = f"{index:02d} Image"
            else:
                base = f"{index:02d} Forme"
            count = seen.get(base, 0) + 1
            seen[base] = count
            shape.name = base if count == 1 else f"{base} {count}"


def slide_garde(prs):
    s = new(prs, band=False)
    rect(s, 9.15, 0, 4.183, 7.5, ROSE_SOFT, radius=0)
    rect(s, 9.15, 0, 0.07, 7.5, ROSE, radius=0)
    logo = CAP / "logo-adg-education.png"
    s.shapes.add_picture(str(logo), emu(0.58), emu(0.42), emu(1.25), emu(0.8))
    add_text(s, "ADG Education  ·  Campus Paris", 2.0, 0.52, 6.6, 0.28, 13, False, MUTED)
    add_text(s, "Titre professionnel CDA  ·  RNCP 37873  ·  niveau 6", 2.0, 0.82, 6.8, 0.28, 13, False, MUTED)

    add_text(s, "Briefly", 0.58, 2.15, 8.2, 0.95, 60, True, ROSE)
    add_lines(
        s,
        [
            ("Conception et développement d'une application", 20, False, INK, 2),
            ("web et mobile de suivi de l'avancement des équipes", 20, False, INK, 0),
        ],
        0.55,
        3.2,
        8.2,
        0.95,
    )
    rect(s, 0.58, 4.35, 1.7, 0.045, ROSE, radius=0)
    add_text(s, "Mémoire de fin de formation", 0.58, 4.55, 6, 0.35, 14, False, MUTED)

    add_lines(
        s,
        [
            ("Soutenance", 12, True, ROSE, 10),
            ("Chiraz Lamraoui", 20, True, INK, 16),
            ("Drif Consult", 15, False, INK, 8),
            ("Tuteur", 12, True, ROSE, 1),
            ("Yasser El Habib Drif", 14, False, INK, 10),
            ("Enseignant", 12, True, ROSE, 1),
            ("Anis Benammar", 14, False, INK, 14),
            ("27 octobre 2026", 14, True, ROSE_DEEP, 0),
        ],
        9.5,
        1.55,
        3.4,
        4.6,
    )
    notes(
        s,
        "Je me présente, je nomme Briefly et le titre. L'entreprise d'accueil est Drif Consult. "
        "J'annonce que je pars du contexte, puis du besoin, avant de montrer l'application.",
    )


def slide_plan(prs):
    s = new(prs)
    kicker(s, "Déroulé")
    title(s, "Plan de la soutenance")
    items = [
        ("01", "Contexte", "L'alternance, le constat, la question, ce que les outils du marché ne couvrent pas."),
        ("02", "Besoins et méthode", "Rôles, journée de chaque utilisateur, cas, puis six sprints."),
        ("03", "Conception", "Le point d'avancement, le schéma des tables, un serveur pour deux clients."),
        ("04", "Réalisation", "Les écrans web et l'application sur iPhone."),
        ("05", "Sécurité, tests, mise en ligne", "Droits, tests, alwaysdata."),
        ("06", "Bilan", "Réponse à la question, et ce qui reste ouvert."),
    ]
    for i, (num, name, desc) in enumerate(items):
        col = i % 3
        row = i // 3
        x = 0.5 + col * 4.2
        y = 1.5 + row * 2.6
        rect(s, x, y, 3.95, 2.35, CARD, LINE, 0.1)
        rect(s, x + 0.16, y + 0.22, 0.055, 1.9, ROSE, radius=0)
        add_text(s, num, x + 0.38, y + 0.2, 1.4, 0.38, 18, True, ROSE)
        add_text(s, name, x + 0.38, y + 0.62, 3.3, 0.55, 18, True, INK)
        add_text(s, desc, x + 0.38, y + 1.25, 3.3, 0.9, 14, False, MUTED)
    footer(s, 2)
    notes(
        s,
        "Je lis les six parties sans les détailler. Le fil est le suivant : d'où vient le besoin, "
        "comment il a été spécifié, comment l'application est construite, ce qui est en ligne.",
    )


def slide_referentiel(prs):
    s = new(prs)
    kicker(s, "Référentiel")
    title(s, "Compétences du titre CDA")
    add_text(
        s,
        "Le référentiel RNCP 37873 regroupe onze compétences en trois activités. Briefly les met en œuvre.",
        0.5,
        1.15,
        12.2,
        0.4,
        15,
        False,
        MUTED,
    )
    cols = [
        (
            "1",
            "Développer une application sécurisée",
            [
                "Environnement de développement, application mobile",
                "Interfaces web et mobile, français et anglais",
                "Services métier et politiques d'accès",
                "Scrum en six sprints",
            ],
        ),
        (
            "2",
            "Concevoir une application en couches",
            [
                "Acteurs, cas d'utilisation, maquettes",
                "Architecture 3-tiers, HTML et JSON",
                "Schéma relationnel",
                "Modèles et relations",
            ],
        ),
        (
            "3",
            "Préparer le déploiement",
            [
                "Plans de tests à chaque sprint",
                "Mise en ligne alwaysdata, web et iPhone",
                "GitHub Actions à chaque push",
                "Déploiement du serveur documenté",
            ],
        ),
    ]
    for i, (num, name, bullets) in enumerate(cols):
        x = 0.5 + i * 4.2
        rect(s, x, 1.7, 3.95, 4.35, CARD, LINE, 0.1)
        rect(s, x, 1.7, 3.95, 1.15, ROSE_SOFT, radius=0.1)
        rect(s, x, 2.55, 3.95, 0.3, ROSE_SOFT, radius=0)
        circ = s.shapes.add_shape(MSO_SHAPE.OVAL, emu(x + 0.22), emu(1.92), emu(0.46), emu(0.46))
        circ.fill.solid()
        circ.fill.fore_color.rgb = ROSE
        circ.line.fill.background()
        no_shadow(circ)
        add_text(s, num, x + 0.22, 1.99, 0.46, 0.34, 15, True, WHITE, PP_ALIGN.CENTER)
        add_text(s, name, x + 0.82, 1.88, 2.9, 0.8, 15, True, INK)
        lines = [(b, 14, False, INK, 12) for b in bullets]
        add_lines(s, lines, x + 0.28, 3.1, 3.4, 3.2)
    footer(s, 3, "Référentiel CDA  ·  activités 1 à 3")
    notes(
        s,
        "Je ne lis pas les onze compétences. Je dis que le projet couvre les trois activités du titre : "
        "développer, concevoir en couches, préparer la mise en production. Le détail est dans le tableau 1.2 du mémoire.",
    )


def slide_entreprise(prs):
    s = new(prs)
    kicker(s, "01   Contexte")
    title(s, "Où j'ai fait mon alternance")
    cards = [
        ("Entreprise d'accueil", "Drif Consult"),
        ("Tuteur", "Yasser El Habib Drif"),
        ("Produit", "Roman.ai, coworker pour Slack"),
    ]
    for i, (label, value) in enumerate(cards):
        x = 0.5 + i * 4.2
        rect(s, x, 1.35, 3.95, 1.85, CARD, LINE, 0.1)
        add_text(s, label, x + 0.24, 1.52, 3.45, 0.32, 12, True, ROSE)
        add_text(s, value, x + 0.24, 2.0, 3.45, 0.9, 18, True, INK)
    rect(s, 0.5, 3.45, 12.3, 2.45, CARD, LINE, 0.1)
    add_text(s, "Missions, au niveau du produit", 0.78, 3.65, 8, 0.32, 13, True, ROSE)
    add_lines(
        s,
        [
            ("Front-end. Interfaces du produit, cohérence visuelle.", 16, False, INK, 8),
            ("Parrainage. La fonction qui relie un utilisateur à ceux qu'il invite.", 16, False, INK, 14),
            ("Ce travail en équipe produit m'a menée au projet de titre.", 16, True, INK, 6),
            ("Briefly n'est pas une fonction de Roman.ai.", 16, True, INK, 0),
        ],
        0.8,
        4.05,
        11.6,
        2.3,
    )
    footer(s, 4)
    notes(
        s,
        "L'alternance est rattachée à Drif Consult, avec Yasser comme tuteur. J'ai travaillé sur Roman.ai, "
        "sur le front-end et sur le parrainage. Je précise tout de suite que Briefly est mon application de mémoire, "
        "pas une brique de Roman.ai. Je ne développe pas le produit de l'entreprise.",
    )


def slide_probleme(prs):
    s = new(prs)
    kicker(s, "01   Contexte")
    title(s, "La question")
    rect(s, 0.5, 1.22, 12.3, 2.05, ROSE_SOFT, LINE, 0.1)
    rect(s, 0.5, 1.22, 0.09, 2.05, ROSE, radius=0)
    add_text(
        s,
        "Comment offrir à une organisation fermée un suivi d'avancement quotidien, structuré autour des tâches, accessible sur le web et sur mobile, avec des rôles nets ?",
        0.85,
        1.42,
        11.6,
        1.65,
        22,
        True,
        INK,
    )
    points = [
        ("1", "Le point quotidien se fait souvent dans Slack ou Teams."),
        ("2", "Il n'est pas relié à une tâche."),
        ("3", "Un blocage disparaît dans le fil."),
        ("4", "Le chef d'équipe n'a pas de tableau de bord à jour."),
    ]
    for i, (n, text) in enumerate(points):
        x = 0.5 + (i % 2) * 6.4
        y = 3.65 + (i // 2) * 1.55
        rect(s, x, y, 6.15, 1.38, CARD, LINE, 0.1)
        circ = s.shapes.add_shape(MSO_SHAPE.OVAL, emu(x + 0.22), emu(y + 0.42), emu(0.5), emu(0.5))
        circ.fill.solid()
        circ.fill.fore_color.rgb = ROSE_SOFT
        circ.line.color.rgb = ROSE_MID
        no_shadow(circ)
        add_text(s, n, x + 0.22, y + 0.5, 0.5, 0.36, 16, True, ROSE, PP_ALIGN.CENTER)
        add_text(s, text, x + 0.9, y + 0.38, 5.0, 0.7, 16, False, INK)
    footer(s, 5, "C5  ·  Analyser les besoins")
    notes(
        s,
        "Je lis la question, puis je la découpe : organisation fermée, tâche, deux supports, rôles. "
        "Le stand-up est le problème. Il n'existe pas d'objet brief séparé : la tâche porte l'avancement. "
        "Les quatre pastilles décrivent le constat, pas un incident chez Roman.ai.",
    )


def slide_existant(prs):
    s = new(prs)
    kicker(s, "01   Contexte")
    title(s, "Ce que les outils du marché ne font pas")
    cols = [
        (
            CAP / "existant-slack-teams.png",
            "Messagerie",
            "Slack, Teams",
            "Immédiat. Rien ne reste lié à la tâche.",
        ),
        (
            CAP / "existant-trello.jpg",
            "Tableau léger",
            "Trello",
            "Cartes et colonnes. Pas de rôles métier, ni de note de blocage obligatoire.",
        ),
        (
            CAP / "existant-jira.png",
            "Forge",
            "Jira",
            "Tickets et workflows. Trop lourd pour le point du matin.",
        ),
    ]
    for i, (img, kind, name, limit) in enumerate(cols):
        x = 0.45 + i * 4.25
        rect(s, x, 1.22, 4.05, 3.4, CARD, LINE, 0.1)
        add_text(s, kind, x + 0.2, 1.36, 3.65, 0.26, 12, True, ROSE)
        add_text(s, name, x + 0.2, 1.62, 3.65, 0.32, 16, True, INK)
        rect(s, x + 0.18, 2.05, 3.7, 1.55, ROSE_SOFT, radius=0.06)
        fit(s, img, x + 0.22, 2.1, 3.62, 1.45, valign="middle")
        add_text(s, limit, x + 0.2, 3.68, 3.65, 1.1, 14, False, INK)
    rect(s, 0.45, 4.82, 12.4, 1.15, ROSE_SOFT, LINE, 0.08)
    add_text(
        s,
        "Aucun ne réunit une organisation fermée, trois rôles, un avancement en trois champs, et le web plus le mobile sur les mêmes règles.",
        0.7,
        5.05,
        11.95,
        0.8,
        15,
        True,
        INK,
    )
    footer(s, 6, "C5  ·  Analyser les besoins")
    notes(
        s,
        "Un outil par colonne, une limite, puis la phrase du bandeau. Monday et Linear restent dans le mémoire, "
        "tableau 1.1. Je ne fais pas un benchmark de cinq produits à l'oral.",
    )


def slide_solution(prs):
    s = new(prs)
    kicker(s, "01   Contexte")
    title(s, "Briefly")
    add_text(
        s,
        "Application interne, web et mobile. Pas d'inscription publique. L'administrateur crée les comptes.",
        0.5,
        1.15,
        12.3,
        0.4,
        16,
        False,
        INK,
    )
    pillars = [
        ("Organiser", "Équipes, projets, tâches."),
        ("Rendre compte", "Réalisé, à venir, blocage."),
        ("Piloter", "Taux par membre, équipe et projet."),
        ("Cloisonner", "Administrateur, chef d'équipe, membre."),
    ]
    for i, (name, desc) in enumerate(pillars):
        y = 1.68 + i * 1.08
        rect(s, 0.5, y, 5.15, 0.98, CARD, LINE, 0.1)
        rect(s, 0.68, y + 0.16, 0.055, 0.66, ROSE, radius=0)
        add_text(s, name, 0.9, y + 0.1, 4.5, 0.32, 16, True, INK)
        add_text(s, desc, 0.9, y + 0.46, 4.5, 0.4, 14, False, MUTED)
    shot = crop_ui(CAP / "web-dashboard.png", "dash.png")
    with Image.open(shot) as im:
        shot_h = 6.54 * im.size[1] / im.size[0]
    rect(s, 5.9, 1.68, 6.9, shot_h + 0.28, CARD, LINE, 0.1)
    fit(s, shot, 6.08, 1.82, 6.54, shot_h + 0.08, valign="top")
    footer(s, 7, "C5  ·  Analyser les besoins")
    notes(
        s,
        "Je dis le nom, pour qui, et que le stand-up tient dans la tâche. Les quatre cartes sont les fonctions réelles. "
        "J'annonce les trois rôles, détaillés juste après.",
    )


def slide_roles(prs):
    s = new(prs)
    kicker(s, "02   Besoins")
    title(s, "Trois rôles")
    roles = [
        (
            "Administrateur",
            "Peut",
            "Créer les comptes, les équipes et les projets. Voir le tableau de bord global.",
            "Ne peut pas",
            "S'il ouvre les tâches, il revient à l'administration.",
        ),
        (
            "Chef d'équipe",
            "Peut",
            "Créer les tâches de ses équipes, filtrer, ouvrir la fiche d'un membre. Diriger plusieurs équipes.",
            "Ne peut pas",
            "Créer les projets ni les comptes.",
        ),
        (
            "Membre",
            "Peut",
            "Voir ses tâches, saisir l'avancement, consulter l'historique.",
            "Ne peut pas",
            "Modifier une tâche qui ne lui est pas assignée.",
        ),
    ]
    for i, (name, yes, yes_t, no, no_t) in enumerate(roles):
        x = 0.4 + i * 4.28
        rect(s, x, 1.22, 4.1, 5.45, CARD, LINE, 0.1)
        rect(s, x, 1.22, 4.1, 0.78, ROSE, radius=0.1)
        rect(s, x, 1.7, 4.1, 0.3, ROSE, radius=0)
        add_text(s, name, x + 0.22, 1.38, 3.65, 0.42, 18, True, WHITE)
        add_text(s, yes, x + 0.22, 2.18, 3.65, 0.28, 12, True, ROSE)
        add_text(s, yes_t, x + 0.22, 2.5, 3.65, 1.2, 14, False, INK)
        rect(s, x + 0.22, 3.8, 3.66, 0.012, ROSE_MID, radius=0)
        add_text(s, no, x + 0.22, 3.98, 3.65, 0.28, 12, True, ROSE_DEEP)
        add_text(s, no_t, x + 0.22, 4.32, 3.65, 1.5, 14, False, INK)
    footer(s, 8, "C5  ·  Analyser les besoins")
    notes(
        s,
        "is_team_lead est le drapeau de team_user. Un compte peut diriger plusieurs équipes. "
        "Une équipe n'a qu'un chef à la fois. Quand l'administrateur en désigne un, le serveur met les autres drapeaux de cette équipe à faux. "
        "La table n'a pas de contrainte d'unicité.",
    )


def slide_cu(prs, uc):
    s = new(prs)
    kicker(s, "02   Besoins")
    title(s, "Cas d'utilisation")
    add_text(
        s,
        "Trois acteurs. Les grands cas seulement.",
        0.5,
        1.12,
        12,
        0.32,
        14,
        False,
        MUTED,
    )
    rect(s, 0.5, 1.55, 12.3, 5.3, CARD, LINE, 0.06)
    fit(s, uc, 0.7, 1.7, 11.9, 5.0)
    footer(s, 9, "C5  ·  Analyser les besoins et maquetter")
    notes(
        s,
        "Je lis un cas par acteur. L'administrateur crée le projet et y affecte une équipe. "
        "Le chef définit les tâches et les assigne. Le membre gère les tâches qui lui sont assignées. "
        "Les six slides suivantes reprennent chaque personne, les fonctions, puis les cas du schéma.",
    )


def slide_matin(prs):
    s = new(prs)
    kicker(s, "02   Besoins")
    title(s, "Trois points de vue")
    add_text(
        s,
        "Les trois rôles ouvrent la même application. Le compte est créé par l'administrateur.",
        0.5,
        1.15,
        12.2,
        0.35,
        15,
        False,
        MUTED,
    )
    people = [
        (
            "Membre",
            [
                "J'ouvre mes tâches.",
                "Je note ce qui est fait, ce qui vient, et ce qui bloque.",
                "Je relis les points que j'ai déjà saisis.",
            ],
        ),
        (
            "Chef d'équipe",
            [
                "Je regarde ce qui bloque dans les équipes que je dirige.",
                "Je crée une tâche et je la donne à quelqu'un de l'équipe.",
                "J'ouvre la fiche d'un membre de ces équipes.",
            ],
        ),
        (
            "Administrateur",
            [
                "Je crée les personnes, les équipes et les projets.",
                "Je regarde le taux de l'ensemble.",
                "Je rattache les équipes aux projets.",
            ],
        ),
    ]
    for i, (name, lines) in enumerate(people):
        x = 0.45 + i * 4.25
        rect(s, x, 1.7, 4.05, 3.7, CARD, LINE, 0.1)
        rect(s, x, 1.7, 4.05, 0.78, ROSE, radius=0.1)
        rect(s, x, 2.18, 4.05, 0.3, ROSE, radius=0)
        add_text(s, name, x + 0.22, 1.88, 3.6, 0.42, 18, True, WHITE)
        add_lines(
            s,
            [(line, 15, False, INK, 12) for line in lines],
            x + 0.22,
            2.7,
            3.6,
            2.4,
        )
    rect(s, 0.45, 5.6, 12.4, 1.05, ROSE_SOFT, LINE, 0.08)
    add_text(
        s,
        "Un chef peut diriger plusieurs équipes. Une équipe n'a qu'un chef à la fois.",
        0.7,
        5.82,
        11.95,
        0.65,
        15,
        False,
        INK,
    )
    footer(s, 10, "C5  ·  Analyser les besoins")
    notes(
        s,
        "Je parle à la première personne, une phrase par rôle. "
        "Le détail de chaque journée vient juste après. "
        "Si on me demande le chef unique : is_team_lead est un booléen, et le serveur remet les autres drapeaux de l'équipe à faux.",
    )


def slide_membre_vue(prs):
    s = new(prs)
    kicker(s, "02   Besoins")
    title(s, "Tâches et avancement")
    steps = [
        ("1", "J'ouvre mon tableau de bord. Le taux est le nombre de tâches terminées, divisé par le total."),
        ("2", "Je vois mes tâches, regroupées par statut. À faire 0, en cours 60, bloqué 30, terminé 100."),
        ("3", "Je remplis réalisé et à venir. Si je choisis bloqué, la note est obligatoire."),
        ("4", "Je relis l'historique de mes points."),
    ]
    for i, (n, text) in enumerate(steps):
        y = 1.22 + i * 1.15
        rect(s, 0.5, y, 12.3, 1.05, CARD, LINE, 0.1)
        add_text(s, n, 0.72, y + 0.28, 0.45, 0.45, 20, True, ROSE)
        add_text(s, text, 1.35, y + 0.18, 11.1, 0.72, 15, False, INK)
    rect(s, 0.5, 5.9, 12.3, 0.85, ROSE_SOFT, LINE, 0.08)
    add_text(
        s,
        "Je ne modifie pas le titre, la description, ni l'assigné. Cela revient au chef d'équipe.",
        0.72,
        6.08,
        11.85,
        0.5,
        15,
        False,
        INK,
    )
    footer(s, 11, "C5  ·  Analyser les besoins")
    notes(
        s,
        "Je reste sur le geste du membre : le point d'avancement. "
        "Je distingue le taux du tableau de bord et le pourcentage du statut.",
    )


def slide_chef_vue(prs):
    s = new(prs)
    kicker(s, "02   Besoins")
    title(s, "Équipes et tâches")
    steps = [
        ("1", "Pour chaque équipe que je dirige : total, en cours, bloquées, terminées cette semaine, taux, dernières tâches bloquées."),
        ("2", "Je crée une tâche sur un projet de mes équipes. La personne assignée doit en faire partie."),
        ("3", "Je filtre les tâches de l'équipe par statut. J'ouvre la fiche d'un membre si nous partageons une équipe que je dirige."),
        ("4", "Sur les tâches qui me sont assignées, je rends compte comme un membre."),
    ]
    for i, (n, text) in enumerate(steps):
        y = 1.22 + i * 1.15
        rect(s, 0.5, y, 12.3, 1.05, CARD, LINE, 0.1)
        add_text(s, n, 0.72, y + 0.28, 0.45, 0.45, 20, True, ROSE)
        add_text(s, text, 1.35, y + 0.16, 11.1, 0.75, 15, False, INK)
    rect(s, 0.5, 5.9, 12.3, 0.85, ROSE_SOFT, LINE, 0.08)
    add_text(
        s,
        "Je ne crée ni les comptes ni les projets.",
        0.72,
        6.08,
        11.85,
        0.5,
        15,
        False,
        INK,
    )
    footer(s, 12, "C5  ·  Analyser les besoins")
    notes(
        s,
        "J'insiste sur deux limites. L'assigné doit appartenir à l'équipe. "
        "La fiche membre n'est visible que si le chef dirige une équipe en commun.",
    )


def slide_admin_vue(prs):
    s = new(prs)
    kicker(s, "02   Besoins")
    title(s, "Comptes, équipes et projets")
    steps = [
        ("1", "Je vois les effectifs, le taux global, la répartition des statuts, et le taux par équipe et par projet."),
        ("2", "Je crée un compte, rattaché à au moins une équipe. S'il dirige une équipe, il devient chef."),
        ("3", "Je compose une équipe : son nom, son chef, ses membres, ses projets."),
        ("4", "Je crée un projet et j'y rattache des équipes."),
    ]
    for i, (n, text) in enumerate(steps):
        y = 1.22 + i * 1.15
        rect(s, 0.5, y, 12.3, 1.05, CARD, LINE, 0.1)
        add_text(s, n, 0.72, y + 0.28, 0.45, 0.45, 20, True, ROSE)
        add_text(s, text, 1.35, y + 0.18, 11.1, 0.72, 15, False, INK)
    rect(s, 0.5, 5.9, 12.3, 0.85, ROSE_SOFT, LINE, 0.08)
    add_text(
        s,
        "Si j'ouvre les tâches, l'application me ramène vers l'administration.",
        0.72,
        6.08,
        11.85,
        0.5,
        15,
        False,
        INK,
    )
    footer(s, 13, "C5  ·  Analyser les besoins")
    notes(
        s,
        "L'administrateur tient la structure. Il ne fait pas le stand-up. "
        "Un compte nouveau est membre, puis le rôle suit les équipes qu'il dirige.",
    )


def slide_fonctions(prs):
    s = new(prs)
    kicker(s, "02   Besoins")
    title(s, "Fonctions")
    groups = [
        (
            "Entrer et son compte",
            "Courriel et mot de passe. Se souvenir de moi, sur le web. Mot de passe oublié. Nom, courriel, mot de passe. Français ou anglais. Thème clair ou sombre.",
        ),
        (
            "Rendre compte",
            "Statut, réalisé, à venir. Note de blocage obligatoire si le statut est bloqué. Historique des points.",
        ),
        (
            "Piloter une équipe",
            "Compteurs de l'équipe, filtre par statut, fiche d'un membre, création et modification d'une tâche.",
        ),
        (
            "Tenir l'organisation",
            "Utilisateurs, équipes, projets, tableau de bord global. Pas de formulaire d'inscription.",
        ),
    ]
    for i, (name, desc) in enumerate(groups):
        col = i % 2
        row = i // 2
        x = 0.45 + col * 6.4
        y = 1.4 + row * 2.1
        rect(s, x, y, 6.15, 1.9, CARD, LINE, 0.1)
        rect(s, x + 0.16, y + 0.18, 0.055, 1.54, ROSE, radius=0)
        add_text(s, name, x + 0.38, y + 0.16, 5.5, 0.38, 18, True, INK)
        add_text(s, desc, x + 0.38, y + 0.6, 5.5, 1.15, 15, False, MUTED)
    footer(s, 14, "C5  ·  Analyser les besoins")
    notes(
        s,
        "Je regroupe les fonctions par ce que la personne vient faire, pas par écran. "
        "La première carte est commune aux trois rôles. Les trois autres suivent le membre, le chef, l'administrateur.",
    )


def slide_cas_detail(prs):
    s = new(prs)
    kicker(s, "02   Besoins")
    title(s, "Cas par acteur")
    columns = [
        (
            "Administrateur",
            [
                "Consulter les statistiques.",
                "Créer un projet. Le cas inclut d'y affecter une équipe.",
                "Affecter un chef d'équipe.",
            ],
        ),
        (
            "Chef d'équipe",
            [
                "Suivre l'évolution d'un projet.",
                "Définir les tâches du projet.",
                "Ce cas inclut de les affecter aux membres de l'équipe.",
            ],
        ),
        (
            "Membre",
            [
                "Gérer les tâches du projet.",
                "Le geste couvre le statut, ce qui est réalisé, ce qui vient, et l'historique.",
                "La note de blocage n'est demandée que si le statut est bloqué.",
            ],
        ),
    ]
    for i, (name, lines) in enumerate(columns):
        x = 0.45 + i * 4.25
        rect(s, x, 1.28, 4.05, 2.55, CARD, LINE, 0.1)
        add_text(s, name, x + 0.22, 1.42, 3.6, 0.38, 16, True, ROSE)
        add_lines(
            s,
            [(line, 15, False, INK, 8) for line in lines],
            x + 0.22,
            1.9,
            3.6,
            1.75,
        )
    rect(s, 0.45, 4.1, 12.4, 1.15, ROSE_SOFT, LINE, 0.08)
    add_text(
        s,
        "Le schéma ne montre pas d'inscription, ni un chef qui crée un projet, ni un administrateur qui tient ses tâches.",
        0.7,
        4.38,
        11.95,
        0.6,
        15,
        False,
        INK,
    )
    footer(s, 15, "C5  ·  Analyser les besoins et maquetter")
    notes(
        s,
        "Je reviens au diagramme des cas, montré avant ces journées, et je lis un cas par acteur. "
        "J'explique l'include du projet, l'include de l'assignation, et la note seulement si la tâche est bloquée.",
    )


def slide_sprints(prs):
    s = new(prs)
    kicker(s, "02   Besoins et méthode")
    title(s, "Scrum, six sprints")
    add_text(
        s,
        "Sprints de deux à quatre semaines, sauf le sprint 5, plus long. L'été ne contient pas de sprint.",
        0.5,
        1.12,
        12.2,
        0.35,
        14,
        False,
        MUTED,
    )
    sprints = [
        ("S1", "Accès", "2 au 22 février", "Connexion, profil, langue, thème. Pas d'inscription publique."),
        ("S2", "Membre", "23 février au 15 mars", "Tâches, avancement, historique. Seul l'assigné met à jour."),
        ("S3", "Chef d'équipe", "16 mars au 5 avril", "Tâches d'équipe, filtre, fiche membre. Pas de création de projet."),
        ("S4", "Administration", "6 au 26 avril", "Comptes, équipes, projets, tableau global."),
        ("S5", "Tests et sécurité", "27 avril au 25 juillet", "Tests automatisés, mesures de sécurité, intégration continue."),
        ("S6", "Mobile et mise en ligne", "1er sept. au 10 octobre", "Flutter, mêmes parcours, iPhone sur le serveur HTTPS."),
    ]
    for i, (code, name, dates, desc) in enumerate(sprints):
        col = i % 3
        row = i // 3
        x = 0.45 + col * 4.25
        y = 1.6 + row * 2.55
        rect(s, x, y, 4.08, 2.38, CARD, LINE, 0.1)
        chip = rect(s, x + 0.18, y + 0.16, 0.62, 0.36, ROSE_SOFT, radius=0.3)
        chip.line.fill.background()
        add_text(s, code, x + 0.18, y + 0.18, 0.62, 0.32, 12, True, ROSE, PP_ALIGN.CENTER)
        add_text(s, name, x + 0.92, y + 0.16, 2.95, 0.36, 16, True, INK)
        add_text(s, dates, x + 0.18, y + 0.58, 3.7, 0.3, 12, False, MUTED)
        add_text(s, desc, x + 0.18, y + 1.0, 3.7, 1.15, 13, False, INK)
    footer(s, 16, "C4  ·  Contribuer à la gestion d'un projet")
    notes(
        s,
        "Je ne lis pas chaque date. L'ordre : accès, membre, chef, administration, tests, mobile. "
        "Le sprint 5 dure treize semaines, je le dis. Entre le 25 juillet et le 1er septembre, pas de sprint. "
        "La mêlée Scrum n'est pas le stand-up que l'application outille.",
    )


def slide_sequence(prs, seq):
    s = new(prs)
    kicker(s, "03   Conception")
    title(s, "Enregistrer l'avancement", size=26)
    steps = [
        ("1", "Le membre envoie le statut, le réalisé, l'à venir et, si besoin, la note."),
        ("2", "Le serveur vérifie que la tâche lui est assignée, et que la saisie est valide."),
        ("3", "Statut bloqué sans note : le serveur refuse."),
        ("4", "Sinon, la tâche est mise à jour et une ligne d'historique est ajoutée."),
        ("5", "Statut terminé : la date de clôture est posée."),
    ]
    for i, (n, text) in enumerate(steps):
        y = 1.2 + i * 1.05
        rect(s, 0.4, y, 6.15, 0.95, CARD, LINE, 0.1)
        circ = s.shapes.add_shape(MSO_SHAPE.OVAL, emu(0.58), emu(y + 0.24), emu(0.46), emu(0.46))
        circ.fill.solid()
        circ.fill.fore_color.rgb = ROSE
        circ.line.fill.background()
        no_shadow(circ)
        add_text(s, n, 0.58, y + 0.31, 0.46, 0.34, 14, True, WHITE, PP_ALIGN.CENTER)
        add_text(s, text, 1.2, y + 0.16, 5.15, 0.66, 13, False, INK)
    rect(s, 6.75, 1.2, 6.1, 5.5, CARD, LINE, 0.1)
    fit(s, seq, 6.85, 1.32, 5.9, 5.25)
    footer(s, 17, "C6  ·  Définir l'architecture")
    notes(
        s,
        "Je marche le schéma. Le cas que je retiens est le refus : tâche bloquée sans note. "
        "Le mobile subit la même règle, parce que la validation est sur le serveur. "
        "Chaque point reste dans l'historique, il n'y a pas de table brief.",
    )


def slide_donnees(prs):
    s = new(prs)
    kicker(s, "03   Conception")
    title(s, "Les données")
    add_text(
        s,
        "Une tâche appartient à un projet et à un assigné. Un point d'avancement est une ligne d'historique.",
        0.5,
        1.12,
        12.2,
        0.35,
        15,
        False,
        MUTED,
    )
    boxes = [
        (0.45, 1.65, 3.9, 1.7, "Utilisateur", "Rôle : administrateur, chef d'équipe ou membre. Mot de passe haché."),
        (4.7, 1.65, 3.9, 1.7, "Équipe", "Plusieurs membres, un seul chef. Nommer un chef retire le précédent."),
        (8.95, 1.65, 3.9, 1.7, "Projet", "Lié à une ou plusieurs équipes."),
        (0.45, 3.6, 6.05, 1.85, "Tâche", "Un projet, un assigné, un statut. Le statut donne un pourcentage : à faire 0, en cours 60, bloqué 30, terminé 100."),
        (6.75, 3.6, 6.1, 1.85, "Point d'avancement", "Réalisé, à venir, note de blocage, date. Plusieurs lignes par tâche."),
    ]
    for x, y, w, h, name, desc in boxes:
        rect(s, x, y, w, h, CARD, LINE, 0.1)
        add_text(s, name, x + 0.2, y + 0.14, w - 0.4, 0.36, 16, True, ROSE)
        add_text(s, desc, x + 0.2, y + 0.55, w - 0.4, h - 0.7, 13, False, MUTED)
    rect(s, 0.45, 5.65, 12.4, 1.15, ROSE_SOFT, LINE, 0.08)
    add_text(
        s,
        "Le taux des tableaux de bord est le nombre de tâches terminées divisé par le total. Le pourcentage affiché sur une tâche vient du statut, pas de ce taux.",
        0.7,
        5.85,
        11.95,
        0.85,
        15,
        False,
        INK,
    )
    footer(s, 18, "C7  ·  Base de données relationnelle")
    notes(
        s,
        "Le pivot team_user relie plusieurs comptes à une équipe. Un seul is_team_lead reste vrai. "
        "Je distingue ensuite le taux du tableau de bord et le pourcentage du statut.",
    )


def schema_table(slide, x, y, w, name, fields):
    """fields: (libellé, pk | fk | pkfk | col)."""
    row = 0.2
    head = 0.34
    height = head + 0.08 + row * len(fields) + 0.06
    rect(slide, x, y, w, height, CARD, LINE, 0.08)
    rect(slide, x, y, w, head, ROSE, radius=0.08)
    rect(slide, x, y + head - 0.12, w, 0.12, ROSE, radius=0)
    add_text(slide, name, x + 0.1, y + 0.03, w - 0.2, 0.28, 13, True, WHITE)
    for index, (label, kind) in enumerate(fields):
        bold = kind in ("pk", "pkfk")
        color = ROSE_DEEP if kind in ("fk", "pkfk") else INK
        add_text(
            slide,
            label,
            x + 0.1,
            y + head + 0.05 + index * row,
            w - 0.18,
            row,
            11,
            bold,
            color,
        )
    return height


def slide_schema(prs):
    s = new(prs)
    kicker(s, "03   Conception")
    title(s, "Schéma relationnel")
    top = [
        (
            0.38,
            4.08,
            "teams",
            [
                ("id", "pk"),
                ("name", "col"),
            ],
        ),
        (
            4.62,
            4.08,
            "team_user",
            [
                ("team_id → teams", "pkfk"),
                ("user_id → users", "pkfk"),
                ("is_team_lead", "col"),
            ],
        ),
        (
            8.86,
            4.08,
            "users",
            [
                ("id", "pk"),
                ("name", "col"),
                ("job_title", "col"),
                ("email, unique", "col"),
                ("password, haché", "col"),
                ("role", "col"),
            ],
        ),
    ]
    heights = []
    for x, w, name, fields in top:
        heights.append(schema_table(s, x, 1.12, w, name, fields))
    add_text(
        s,
        "Plusieurs comptes par équipe. Un seul is_team_lead reste vrai. Nommer un chef remet les autres à faux.",
        4.62,
        1.12 + heights[1] + 0.08,
        4.08,
        0.72,
        12,
        False,
        MUTED,
    )
    bottom = [
        (
            "projects",
            [
                ("id", "pk"),
                ("name", "col"),
                ("description", "col"),
            ],
        ),
        (
            "project_team",
            [
                ("project_id → projects", "pkfk"),
                ("team_id → teams", "pkfk"),
            ],
        ),
        (
            "tasks",
            [
                ("id", "pk"),
                ("project_id → projects", "fk"),
                ("assigned_to → users", "fk"),
                ("title", "col"),
                ("description", "col"),
                ("status", "col"),
                ("progress_done", "col"),
                ("progress_next", "col"),
                ("blocker_note", "col"),
                ("completed_at", "col"),
            ],
        ),
        (
            "task_updates",
            [
                ("id", "pk"),
                ("task_id → tasks", "fk"),
                ("user_id → users", "fk"),
                ("status", "col"),
                ("progress_done", "col"),
                ("progress_next", "col"),
                ("blocker_note", "col"),
            ],
        ),
    ]
    width = 3.02
    for index, (name, fields) in enumerate(bottom):
        schema_table(s, 0.38 + index * (width + 0.15), 3.28, width, name, fields)
    rect(s, 0.38, 6.12, 12.56, 0.72, ROSE_SOFT, LINE, 0.08)
    add_text(
        s,
        "La clé primaire est en gras, la clé étrangère en rose. created_at et updated_at sont sur chaque table, sauf project_team.",
        0.55,
        6.26,
        12.2,
        0.46,
        14,
        False,
        INK,
    )
    footer(s, 19, "C7  ·  Base de données relationnelle")
    notes(
        s,
        "Je lis les sept tables du métier. team_user porte plusieurs comptes, et le drapeau de chef. "
        "La base n'empêche pas deux drapeaux vrais. Le serveur remet les autres à faux quand on nomme un chef. "
        "tasks.assigned_to pointe vers un compte, task_updates est l'historique du point. "
        "progress_done et progress_next sont le réalisé et l'à venir. "
        "Je ne dessine pas les tables techniques de session, de cache et de mot de passe oublié.",
    )


def slide_archi(prs, arch):
    s = new(prs)
    kicker(s, "03   Conception")
    title(s, "Un serveur, deux clients")
    rect(s, 0.45, 1.2, 12.4, 3.15, CARD, LINE, 0.06)
    fit(s, arch, 0.7, 1.35, 11.95, 2.85)
    pills = [
        ("Navigateur", "Session, pages HTML."),
        ("iPhone", "Réponses JSON."),
        ("Même catalogue", "Les mêmes adresses pour les deux."),
        ("Réponse", "Une page, ou des données."),
    ]
    for i, (name, desc) in enumerate(pills):
        x = 0.45 + i * 3.2
        rect(s, x, 4.55, 3.05, 1.25, CARD, LINE, 0.1)
        add_text(s, name, x + 0.15, 4.68, 2.75, 0.32, 14, True, INK)
        add_text(s, desc, x + 0.15, 5.05, 2.75, 0.58, 13, False, MUTED)
    add_text(
        s,
        "Laravel, PHP, MySQL en local, MariaDB en ligne, Flutter.",
        0.5,
        6.0,
        12.3,
        0.4,
        14,
        False,
        MUTED,
    )
    footer(s, 20, "C6  ·  Définir l'architecture logicielle")
    notes(
        s,
        "Je dis pourquoi une seule fois : la validation, les politiques et le métier ne sont pas recopiés dans le téléphone. "
        "Le serveur choisit la page ou les données. Les clients ne parlent jamais à la base.",
    )


def slide_web(prs):
    s = new(prs)
    kicker(s, "04   Réalisation")
    title(s, "L'application web")
    shots = [
        (CAP / "web-dashboard-lead.png", "Chef d'équipe", "Taux, tâches, compteurs de l'équipe, dont les bloquées."),
        (CAP / "web-task-detail.png", "Membre", "Statut, réalisé, à venir. L'historique sous la fiche."),
        (CAP / "web-admin-dashboard.png", "Administrateur", "Effectifs, taux global, répartition des statuts."),
    ]
    prepared = []
    for img, who, desc in shots:
        path = crop_ui(img, img.stem + "-crop.png")
        with Image.open(path) as im:
            ih = 3.8 * im.size[1] / im.size[0]
        prepared.append((path, who, desc, ih))
    img_h = max(item[3] for item in prepared)
    card_h = 1.08 + img_h + 0.16
    for i, (path, who, desc, ih) in enumerate(prepared):
        x = 0.4 + i * 4.28
        rect(s, x, 1.22, 4.12, card_h, CARD, LINE, 0.1)
        add_text(s, who, x + 0.18, 1.32, 3.76, 0.28, 14, True, ROSE)
        add_text(s, desc, x + 0.18, 1.6, 3.76, 0.55, 13, False, INK)
        fit(s, path, x + 0.16, 2.22, 3.8, ih + 0.04, valign="top")
    add_text(
        s,
        "Français et anglais. Thème clair et thème sombre.",
        0.5,
        1.38 + card_h,
        12.2,
        0.4,
        14,
        False,
        MUTED,
        PP_ALIGN.CENTER,
    )
    footer(s, 21, "C2  ·  Développer des interfaces")
    notes(
        s,
        "Un écran, une phrase. Chef : je vois ce qui bloque. Membre : je remplis réalisé et à venir. "
        "Admin : je vois les équipes, les projets et le taux global. Français et anglais, thème clair et sombre, si on me le demande. "
        "Je peux aussi ouvrir https://lamraoui.alwaysdata.net.",
    )


def slide_mobile(prs):
    s = new(prs)
    kicker(s, "04   Réalisation")
    title(s, "L'application sur iPhone")
    add_text(
        s,
        "Les mêmes parcours, sur le serveur en ligne.",
        0.5,
        1.12,
        12.2,
        0.4,
        15,
        False,
        MUTED,
    )
    phones = [
        (CAP / "mobile-dashboard.png", "Tableau de bord"),
        (CAP / "mobile-task-detail.png", "Détail de tâche"),
        (CAP / "mobile-admin-dashboard.png", "Administration"),
    ]
    for i, (img, cap) in enumerate(phones):
        x = 1.15 + i * 4.0
        rect(s, x, 1.65, 3.15, 4.85, CARD, LINE, 0.12)
        fit(s, img, x + 0.55, 1.78, 2.05, 4.15)
        add_text(s, cap, x, 6.52, 3.15, 0.32, 13, True, INK, PP_ALIGN.CENTER)
    footer(s, 22, "C2  ·  Développer des interfaces")
    notes(
        s,
        "Ce n'est pas une maquette. L'application est installée sur un iPhone en release, URL https://lamraoui.alwaysdata.net. "
        "Les droits ne sont pas réécrits dans Flutter. Le contrôle de la note sur le téléphone est un confort. Le serveur reste l'autorité.",
    )


def slide_securite(prs):
    s = new(prs)
    kicker(s, "05   Sécurité")
    title(s, "Droits et protections")
    items = [
        ("Mots de passe", "Ils sont hachés dès l'enregistrement. Ils n'apparaissent pas dans les réponses."),
        ("Session et jeton", "Le navigateur garde une session, régénérée à la connexion. Le téléphone reçoit un jeton."),
        ("Formulaires", "Le site vérifie que le formulaire vient de ses pages. Le téléphone s'authentifie par son jeton."),
        ("Accès", "Sans session, refus. Ensuite le rôle. Ensuite la politique sur la ressource."),
        ("Données", "Validation avant écriture. Requêtes préparées. Le texte des pages est échappé."),
        ("Assignation", "Une tâche ne peut être donnée qu'à un membre de l'équipe de contexte."),
    ]
    for i, (name, desc) in enumerate(items):
        col = i % 3
        row = i // 3
        x = 0.45 + col * 4.25
        y = 1.25 + row * 2.15
        rect(s, x, y, 4.08, 2.0, CARD, LINE, 0.08)
        add_text(s, name, x + 0.2, y + 0.18, 3.7, 0.38, 16, True, INK)
        add_text(s, desc, x + 0.2, y + 0.65, 3.7, 1.15, 13, False, MUTED)
    rect(s, 0.45, 5.6, 12.4, 1.15, ROSE_SOFT, LINE, 0.08)
    add_text(s, "Limites connues", 0.7, 5.74, 3, 0.28, 12, True, ROSE)
    add_text(
        s,
        "Pas de plafond sur les tentatives de connexion. Les jetons du téléphone n'expirent pas.",
        0.7,
        6.06,
        11.9,
        0.5,
        15,
        False,
        INK,
    )
    footer(s, 23, "C3  ·  Développer des composants métier")
    notes(
        s,
        "Je choisis une protection et je la tiens. Si on me demande le mot de passe oublié : le parcours existe, "
        "En production, le courriel de réinitialisation est écrit dans le journal, il n'est pas envoyé. "
        "Je dis les deux limites avant qu'on me les trouve : pas de plafond sur les tentatives, jetons sans expiration.",
    )


def slide_tests(prs):
    s = new(prs)
    kicker(s, "05   Tests")
    title(s, "Quatre cas de test")
    headers = ["Cas", "Démarche", "Résultat"]
    rows = [
        ["Visiteur", "Ouvrir le tableau de bord sans être connecté.", "Renvoi vers la page de connexion."],
        ["Blocage sans note", "Passer une tâche au statut bloqué sans note.", "Le serveur refuse l'enregistrement."],
        ["Tâche d'un autre", "Un membre met à jour la tâche d'un collègue.", "Accès refusé."],
        ["Projet par le chef", "Un chef d'équipe tente de créer un projet.", "Accès refusé. Aucune ligne créée."],
    ]
    table_shape = s.shapes.add_table(5, 3, emu(0.45), emu(1.3), emu(12.4), emu(4.55))
    table = table_shape.table
    table.columns[0].width = emu(2.5)
    table.columns[1].width = emu(5.7)
    table.columns[2].width = emu(4.2)
    for c, text in enumerate(headers):
        cell = table.cell(0, c)
        cell.text = ""
        cell.fill.solid()
        cell.fill.fore_color.rgb = ROSE
        p = cell.text_frame.paragraphs[0]
        set_run(p, text, 14, True, WHITE)
        cell.vertical_anchor = MSO_ANCHOR.MIDDLE
        cell.text_frame.margin_left = emu(0.12)
    for r, row in enumerate(rows, start=1):
        for c, text in enumerate(row):
            cell = table.cell(r, c)
            cell.text = ""
            cell.fill.solid()
            cell.fill.fore_color.rgb = WHITE if r % 2 else ROSE_SOFT
            p = cell.text_frame.paragraphs[0]
            set_run(p, text, 14, c == 0, INK)
            cell.vertical_anchor = MSO_ANCHOR.MIDDLE
            cell.text_frame.margin_left = emu(0.12)
            cell.text_frame.word_wrap = True
    add_text(
        s,
        "La suite tourne en local, et à chaque envoi du code.",
        0.5,
        6.05,
        12.2,
        0.7,
        14,
        False,
        MUTED,
    )
    footer(s, 24, "C9  ·  Préparer et exécuter les plans de tests")
    notes(
        s,
        "Je prends le cas du blocage sans note, parce qu'il porte le métier. "
        "Les refus d'accès et les saisies refusées sont dans les tableaux 4.4 à 4.8. "
        "Mot de passe oublié, profil, se souvenir de moi et thème sont réalisés, ils ne sont pas tous dans cette suite. "
        "Je le dis si on me le demande, je ne l'annonce pas.",
    )


def slide_deploy(prs):
    s = new(prs)
    kicker(s, "05   Mise en ligne")
    title(s, "Démonstration")
    rect(s, 0.5, 1.22, 12.3, 1.7, ROSE_SOFT, LINE, 0.1)
    add_text(s, "Application en ligne", 0.78, 1.38, 6, 0.28, 12, True, ROSE)
    add_text(s, "https://lamraoui.alwaysdata.net", 0.78, 1.72, 11.6, 0.85, 28, True, ROSE_DEEP)
    facts = [
        ("Hébergement", "alwaysdata, PHP, MariaDB, HTTPS."),
        ("Production", "Les erreurs détaillées sont coupées. Les comptes de démonstration ne sont pas recréés."),
        ("iPhone", "Installée sur iPhone, même adresse."),
        ("Intégration continue", "À chaque envoi, les tests du serveur et du mobile sont lancés."),
    ]
    for i, (name, desc) in enumerate(facts):
        col = i % 2
        row = i // 2
        x = 0.5 + col * 6.4
        y = 3.2 + row * 1.7
        rect(s, x, y, 6.15, 1.55, CARD, LINE, 0.08)
        add_text(s, name, x + 0.22, y + 0.18, 5.7, 0.34, 15, True, INK)
        add_text(s, desc, x + 0.22, y + 0.6, 5.7, 0.75, 14, False, MUTED)
    footer(s, 25, "C10  ·  Déploiement    C11  ·  DevOps")
    notes(
        s,
        "J'ouvre le site si le réseau le permet, sinon je reste sur les captures. "
        "Le seeder ne tourne pas en production. Un administrateur a été créé à la main pour les essais. "
        "Le job CI ne déploie pas tout seul : la mise en ligne reste manuelle. C'est une piste, pas un oubli caché.",
    )


def slide_bilan(prs):
    s = new(prs)
    kicker(s, "06   Bilan")
    title(s, "Réponse à la question")
    answers = [
        ("Organisation fermée", "Pas d'inscription publique. L'administrateur crée les comptes."),
        ("Autour des tâches", "Réalisé, à venir, blocage. Note obligatoire si le statut est bloqué."),
        ("Web et mobile", "Les mêmes règles. Un serveur, deux réponses."),
        ("Rôles nets", "Administrateur, chef d'équipe, membre. Politiques sur la ressource."),
    ]
    for i, (name, desc) in enumerate(answers):
        y = 1.2 + i * 1.05
        rect(s, 0.45, y, 7.55, 0.95, CARD, LINE, 0.1)
        add_text(s, name, 0.7, y + 0.1, 7.05, 0.32, 15, True, INK)
        add_text(s, desc, 0.7, y + 0.46, 7.05, 0.38, 13, False, MUTED)
    rect(s, 8.2, 1.2, 4.65, 4.15, ROSE_SOFT, LINE, 0.1)
    add_text(s, "Plus tard, pas dans cette version", 8.42, 1.4, 4.2, 0.6, 14, True, ROSE)
    later = [
        "Notification quand une tâche bloque.",
        "Brief du jour, à partir de l'historique des points.",
        "Export PDF des tableaux de bord.",
        "Expiration des jetons, plafond de tentatives.",
    ]
    add_lines(s, [(t, 13, False, MUTED, 8) for t in later], 8.4, 2.1, 4.25, 2.9)
    add_text(
        s,
        "Apports : mener l'application de l'analyse UML jusqu'à l'iPhone en ligne, avec les règles testées à chaque push.",
        0.5,
        5.55,
        12.3,
        1.2,
        15,
        False,
        INK,
    )
    footer(s, 26)
    notes(
        s,
        "Je reviens à la question de la slide 5 et je coche les quatre bouts. "
        "Les pastilles de droite ne sont pas dans l'application. Je le dis, puis je passe à la dernière slide.",
    )


def slide_merci(prs):
    s = new(prs, band=False)
    rect(s, 0, 0, 13.333, 0.07, ROSE, radius=0)
    rect(s, 0, 7.43, 13.333, 0.07, ROSE, radius=0)
    add_text(s, "BRIEFLY", 0.9, 2.25, 11.5, 0.32, 13, True, ROSE, PP_ALIGN.CENTER)
    add_text(s, "Merci pour votre attention.", 0.9, 2.65, 11.5, 0.75, 40, True, INK, PP_ALIGN.CENTER)
    rect(s, 6.05, 3.55, 1.25, 0.045, ROSE, radius=0)
    add_text(
        s,
        "Je suis à votre disposition pour répondre à vos questions.",
        0.9,
        3.8,
        11.5,
        0.5,
        20,
        False,
        MUTED,
        PP_ALIGN.CENTER,
    )
    add_text(s, "Chiraz Lamraoui    ·    27 octobre 2026", 0.9, 4.55, 11.5, 0.4, 15, False, ROSE, PP_ALIGN.CENTER)
    notes(s, "Je lis la phrase, je m'arrête, j'attends la question.")


if __name__ == "__main__":
    build()
