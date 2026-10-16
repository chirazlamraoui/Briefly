#!/usr/bin/env python3
"""Draw rapport UML figures in the same simple style as the student use-case diagram."""

from __future__ import annotations

import math
import re
from pathlib import Path

from PIL import Image, ImageDraw, ImageFont

CAP = Path("/Users/chiraz/Projects/Briefly/docs/rapport/captures")
ROOT = Path("/Users/chiraz/Projects/Briefly")
FONT = "/System/Library/Fonts/Supplemental/Arial.ttf"
FONTB = "/System/Library/Fonts/Supplemental/Arial Bold.ttf"

BLACK = (32, 32, 32)
GRAY = (232, 232, 232)
WHITE = (255, 255, 255)
LIGHT = (248, 248, 248)
CODE_BG = (250, 250, 248)
CODE_BAR = (236, 236, 232)
KW = (0, 70, 130)
STR = (120, 80, 20)
CMT = (110, 110, 110)


def fnt(size: int, bold: bool = False) -> ImageFont.FreeTypeFont:
    return ImageFont.truetype(FONTB if bold else FONT, size)


def new(w: int, h: int) -> tuple[Image.Image, ImageDraw.ImageDraw]:
    im = Image.new("RGB", (w, h), WHITE)
    return im, ImageDraw.Draw(im)


def wrap(draw: ImageDraw.ImageDraw, text: str, font: ImageFont.FreeTypeFont, max_w: int) -> list[str]:
    words = text.replace("\n", " \n ").split()
    lines: list[str] = []
    cur = ""
    for word in words:
        if word == "\n":
            lines.append(cur)
            cur = ""
            continue
        trial = (cur + " " + word).strip()
        if draw.textlength(trial, font=font) <= max_w:
            cur = trial
        else:
            if cur:
                lines.append(cur)
            cur = word
    if cur:
        lines.append(cur)
    return lines or [""]


def center_text(draw: ImageDraw.ImageDraw, cx: float, cy: float, text: str, font: ImageFont.FreeTypeFont, fill=BLACK) -> None:
    bbox = draw.textbbox((0, 0), text, font=font)
    w, h = bbox[2] - bbox[0], bbox[3] - bbox[1]
    draw.text((cx - w / 2, cy - h / 2 - bbox[1] / 2), text, font=font, fill=fill)


def stick(draw: ImageDraw.ImageDraw, cx: int, top: int, label: str, scale: float = 1.0) -> int:
    r = int(13 * scale)
    head = (cx - r, top, cx + r, top + 2 * r)
    draw.ellipse(head, outline=BLACK, width=2)
    body_top = top + 2 * r
    body_bot = body_top + int(30 * scale)
    draw.line((cx, body_top, cx, body_bot), fill=BLACK, width=2)
    draw.line((cx - int(18 * scale), body_top + int(10 * scale), cx + int(18 * scale), body_top + int(10 * scale)), fill=BLACK, width=2)
    draw.line((cx, body_bot, cx - int(14 * scale), body_bot + int(22 * scale)), fill=BLACK, width=2)
    draw.line((cx, body_bot, cx + int(14 * scale), body_bot + int(22 * scale)), fill=BLACK, width=2)
    font = fnt(15)
    lines = wrap(draw, label, font, 140)
    y = body_bot + int(28 * scale)
    for line in lines:
        center_text(draw, cx, y + 8, line, font)
        y += 18
    return y


def oval(draw: ImageDraw.ImageDraw, box: tuple[int, int, int, int], text: str, font_size: int = 14) -> tuple[int, int]:
    x1, y1, x2, y2 = box
    draw.ellipse(box, fill=GRAY, outline=BLACK, width=2)
    font = fnt(font_size)
    cx, cy = (x1 + x2) / 2, (y1 + y2) / 2
    lines = wrap(draw, text, font, int((x2 - x1) * 0.78))
    h = 16 * len(lines)
    ty = cy - h / 2 + 6
    for line in lines:
        center_text(draw, cx, ty, line, font)
        ty += 16
    return (int(cx), int((y1 + y2) / 2))


def arrow(draw: ImageDraw.ImageDraw, a: tuple[int, int], b: tuple[int, int], dashed: bool = False, label: str | None = None) -> None:
    x1, y1 = a
    x2, y2 = b
    if dashed:
        segs = 18
        for i in range(segs):
            if i % 2 == 0:
                sx = x1 + (x2 - x1) * i / segs
                sy = y1 + (y2 - y1) * i / segs
                ex = x1 + (x2 - x1) * (i + 1) / segs
                ey = y1 + (y2 - y1) * (i + 1) / segs
                draw.line((sx, sy, ex, ey), fill=BLACK, width=2)
    else:
        draw.line((x1, y1, x2, y2), fill=BLACK, width=2)
    ang = math.atan2(y2 - y1, x2 - x1)
    L = 10
    draw.polygon(
        [
            (x2, y2),
            (x2 - L * math.cos(ang - 0.4), y2 - L * math.sin(ang - 0.4)),
            (x2 - L * math.cos(ang + 0.4), y2 - L * math.sin(ang + 0.4)),
        ],
        fill=BLACK,
    )
    if label:
        mx, my = (x1 + x2) / 2, (y1 + y2) / 2 - 12
        center_text(draw, mx, my, label, fnt(12, True))


def box(draw: ImageDraw.ImageDraw, x: int, y: int, w: int, h: int, lines: list[str], fill=LIGHT) -> tuple[int, int]:
    draw.rounded_rectangle((x, y, x + w, y + h), radius=6, fill=fill, outline=BLACK, width=2)
    cy = y + h / 2 - 8 * (len(lines) - 1)
    for line in lines:
        center_text(draw, x + w / 2, cy, line, fnt(14, True) if line == lines[0] else fnt(12))
        cy += 18
    return (x + w // 2, y + h // 2)


def save(im: Image.Image, name: str) -> None:
    dest = CAP / name
    im.save(dest, "PNG", optimize=True)
    print(name, im.size)


def crop_user_uc() -> None:
    src = Path("/Users/chiraz/.cursor/projects/Users-chiraz-Projects-Briefly/assets/diagramme_cas_d_utilisation-1238aeaf-e4f7-4552-be1a-ce15dafd0093.png")
    im = Image.open(src).convert("RGB")
    px = im.load()
    w, h = im.size
    left, top, right, bot = w, h, 0, 0
    for y in range(h):
        for x in range(w):
            r, g, b = px[x, y]
            if r + g + b > 80:
                left = min(left, x)
                right = max(right, x)
                top = min(top, y)
                bot = max(bot, y)
    cropped = im.crop((max(0, left - 8), max(0, top - 8), min(w, right + 9), min(h, bot + 9)))
    save(cropped, "diag-uc-overview.png")


def uc_admin() -> None:
    im, d = new(1100, 620)
    d.rectangle((220, 30, 1070, 590), outline=BLACK, width=2)
    center_text(d, 645, 52, "Briefly", fnt(16, True))
    stick(d, 90, 220, "Administrateur", 1.15)
    c1 = oval(d, (280, 90, 520, 170), "Consulter le tableau de bord")
    c2 = oval(d, (280, 210, 520, 290), "Gérer les utilisateurs")
    c3 = oval(d, (280, 330, 520, 410), "Gérer les équipes")
    c4 = oval(d, (280, 450, 520, 530), "Gérer les projets")
    i1 = oval(d, (700, 150, 980, 230), "Affecter au moins une équipe")
    i2 = oval(d, (700, 260, 980, 340), "Synchroniser le rôle chef")
    i3 = oval(d, (700, 430, 980, 510), "Rattacher des équipes")
    a = (170, 280)
    arrow(d, a, (280, 130))
    arrow(d, a, (280, 250))
    arrow(d, a, (280, 370))
    arrow(d, a, (280, 490))
    arrow(d, (520, 250), (700, 190), dashed=True, label="«include»")
    arrow(d, (520, 250), (700, 300), dashed=True, label="«include»")
    arrow(d, (520, 490), (700, 470), dashed=True, label="«include»")
    save(im, "diag-uc-admin.png")


def uc_lead() -> None:
    im, d = new(1100, 620)
    d.rectangle((220, 30, 1070, 590), outline=BLACK, width=2)
    center_text(d, 645, 52, "Briefly", fnt(16, True))
    stick(d, 90, 220, "Chef d'équipe", 1.15)
    c1 = oval(d, (280, 80, 560, 160), "Suivre l'évolution d'un projet")
    c2 = oval(d, (280, 190, 560, 270), "Définir les tâches du projet")
    c3 = oval(d, (280, 300, 560, 380), "Consulter les tâches d'équipe")
    c4 = oval(d, (280, 410, 560, 490), "Consulter la fiche d'un membre")
    i1 = oval(d, (700, 190, 1000, 270), "Affecter les tâches aux membres")
    i2 = oval(d, (700, 400, 1000, 500), "Vérifier le partage d'une équipe")
    a = (170, 280)
    arrow(d, a, (280, 120))
    arrow(d, a, (280, 230))
    arrow(d, a, (280, 340))
    arrow(d, a, (280, 450))
    arrow(d, (560, 230), (700, 230), dashed=True, label="«include»")
    arrow(d, (560, 450), (700, 450), dashed=True, label="«include»")
    save(im, "diag-uc-lead.png")


def uc_member() -> None:
    im, d = new(1100, 560)
    d.rectangle((220, 30, 1070, 530), outline=BLACK, width=2)
    center_text(d, 645, 52, "Briefly", fnt(16, True))
    stick(d, 90, 200, "Membre", 1.15)
    c1 = oval(d, (280, 90, 560, 170), "Consulter le tableau de bord")
    c2 = oval(d, (280, 200, 560, 280), "Lister ses tâches")
    c3 = oval(d, (280, 310, 560, 390), "Mettre à jour l'avancement")
    c4 = oval(d, (280, 420, 560, 500), "Consulter l'historique")
    i1 = oval(d, (700, 300, 1020, 400), "Valider la note de blocage")
    a = (170, 260)
    arrow(d, a, (280, 130))
    arrow(d, a, (280, 240))
    arrow(d, a, (280, 350))
    arrow(d, a, (280, 460))
    arrow(d, (700, 350), (560, 350), dashed=True, label="«extend»")
    center_text(d, 640, 318, "{statut = BLOCKED}", fnt(12))
    save(im, "diag-uc-member.png")


def scrum() -> None:
    im, d = new(980, 420)
    nodes = [
        (60, 160, "Carnet de produit"),
        (250, 160, "Planification\ndu sprint"),
        (440, 160, "Carnet de sprint"),
        (630, 160, "Sprint\n(2 à 4 semaines)"),
        (630, 320, "Mêlée quotidienne"),
        (440, 320, "Incrément"),
        (250, 320, "Revue de sprint"),
        (60, 320, "Rétrospective"),
    ]
    pts = []
    for x, y, label in nodes:
        pts.append(box(d, x, y, 160, 70, label.split("\n")))
    order = [0, 1, 2, 3, 4, 5, 6, 7, 0]
    for i in range(len(order) - 1):
        a = pts[order[i]]
        b = pts[order[i + 1]]
        if i == 3:
            arrow(d, (a[0], a[1] + 35), (b[0], b[1] - 35))
        elif i == 7:
            arrow(d, (a[0] + 80, a[1] - 35), (b[0] + 80, b[1] + 35))
        elif i < 3:
            arrow(d, (a[0] + 80, a[1]), (b[0] - 80, b[1]))
        else:
            arrow(d, (a[0] - 80, a[1]), (b[0] + 80, b[1]))
    save(im, "diag-scrum.png")


def arch_physical() -> None:
    im, d = new(980, 360)
    box(d, 40, 40, 220, 90, ["Navigateur web", "vues Blade / session"])
    box(d, 40, 200, 220, 90, ["Application Flutter", "JSON / jeton Sanctum"])
    box(d, 380, 120, 240, 100, ["Laravel", "contrôleurs, services"])
    d.ellipse((740, 125, 940, 215), fill=GRAY, outline=BLACK, width=2)
    center_text(d, 840, 160, "MySQL", fnt(16, True))
    center_text(d, 840, 182, "données métier", fnt(12))
    arrow(d, (260, 85), (380, 155))
    arrow(d, (260, 245), (380, 185))
    arrow(d, (620, 170), (740, 170))
    center_text(d, 150, 22, "Niveau 1 : clients", fnt(13, True))
    center_text(d, 500, 100, "Niveau 2 : application", fnt(13, True))
    center_text(d, 840, 108, "Niveau 3 : données", fnt(13, True))
    save(im, "diag-arch-physical.png")


def arch_unified() -> None:
    im, d = new(980, 420)
    box(d, 40, 50, 200, 80, ["Navigateur", "Accept: text/html"])
    box(d, 40, 250, 200, 80, ["Flutter / Dio", "Accept: application/json"])
    box(d, 360, 140, 260, 110, ["routes/web.php", "auth + respond()"])
    box(d, 740, 50, 200, 80, ["Vue Blade", "ou redirection"])
    box(d, 740, 250, 200, 80, ["JSON", "Resource ou tableau"])
    arrow(d, (240, 90), (360, 175))
    arrow(d, (240, 290), (360, 215))
    arrow(d, (620, 175), (740, 90))
    arrow(d, (620, 215), (740, 290))
    save(im, "diag-arch-unified.png")


def arch_layers() -> None:
    im, d = new(720, 520)
    labels = [
        "Présentation  ·  Blade + Bootstrap, widgets Flutter",
        "Coordination  ·  routes, middleware, contrôleurs, Form Requests",
        "Autorisation  ·  TaskPolicy, ProjectPolicy",
        "Application / métier  ·  TaskService, services d'administration",
        "Domaine  ·  User, Team, Project, Task, TaskUpdate",
        "Persistance  ·  Eloquent / MySQL",
    ]
    y = 30
    for i, label in enumerate(labels):
        w = 640 - i * 18
        x = 40 + i * 9
        box(d, x, y, w, 62, [label])
        if i < len(labels) - 1:
            arrow(d, (360, y + 62), (360, y + 78))
        y += 78
    save(im, "diag-arch-layers.png")


MSG_H, SELF_H, TAB_H, GUARD_H, SEP_H, PAD_H, GAP_H = 52, 70, 26, 36, 18, 22, 24


def _inner_height(steps: list[tuple]) -> int:
    h = 0
    for step in steps:
        h += _step_height(step)
        if step[0] in ("alt", "opt", "loop"):
            h += GAP_H
    return h


def _step_height(step: tuple) -> int:
    kind = step[0]
    if kind == "msg":
        return MSG_H
    if kind == "self":
        return SELF_H
    if kind == "alt":
        h = TAB_H
        for i, (_guard, inner) in enumerate(step[1]):
            if i:
                h += SEP_H
            h += GUARD_H + _inner_height(inner)
        return h + PAD_H
    if kind in ("opt", "loop"):
        return TAB_H + GUARD_H + _inner_height(step[2]) + PAD_H
    raise ValueError(kind)


def _draw_frag_tab(d: ImageDraw.ImageDraw, x: int, y: int, kind: str) -> None:
    tw = int(d.textlength(kind, fnt(13, True)))
    w, h = tw + 16, 22
    d.rectangle((x, y, x + w, y + h), outline=BLACK, fill=WHITE, width=2)
    d.polygon([(x + w, y), (x + w + 8, y + h // 2), (x + w, y + h)], outline=BLACK, fill=WHITE)
    d.text((x + 8, y + 3), kind, font=fnt(13, True), fill=BLACK)


def _draw_seq_steps(d: ImageDraw.ImageDraw, xs: list[int], y: int, steps: list[tuple], left: int, right: int) -> int:
    for step in steps:
        kind = step[0]
        if kind == "msg":
            _, a, b, label = step
            arrow(d, (xs[a], y), (xs[b], y))
            center_text(d, (xs[a] + xs[b]) / 2, y - 14, label, fnt(12))
            y += MSG_H
        elif kind == "self":
            _, a, label = step
            x = xs[a]
            d.line((x, y, x + 72, y), fill=BLACK, width=2)
            d.line((x + 72, y, x + 72, y + 22), fill=BLACK, width=2)
            arrow(d, (x + 72, y + 22), (x, y + 22))
            center_text(d, x + 50, y - 14, label, fnt(12))
            y += SELF_H
        elif kind == "alt":
            h = _step_height(step)
            d.rectangle((left, y, right, y + h), outline=BLACK, width=2)
            _draw_frag_tab(d, left, y, "alt")
            inner_y = y + TAB_H
            for i, (guard, inner) in enumerate(step[1]):
                if i:
                    dashed_line(d, (left, inner_y), (right, inner_y))
                    inner_y += SEP_H
                d.text((left + 10, inner_y), f"[{guard}]", font=fnt(12, True), fill=BLACK)
                inner_y += GUARD_H
                inner_y = _draw_seq_steps(d, xs, inner_y, inner, left + 10, right - 10)
            y += h + GAP_H
        else:
            h = _step_height(step)
            d.rectangle((left, y, right, y + h), outline=BLACK, width=2)
            _draw_frag_tab(d, left, y, kind)
            d.text((left + 10, y + TAB_H), f"[{step[1]}]", font=fnt(12, True), fill=BLACK)
            _draw_seq_steps(d, xs, y + TAB_H + GUARD_H, step[2], left + 10, right - 10)
            y += h + GAP_H
    return y


def draw_sequence(name: str, actors: list[str], steps: list[tuple], *, stick_first: bool = False, gap: int = 200) -> None:
    n = len(actors)
    width = max(gap * n + 80, 920)
    body = _inner_height(steps)
    top = 150 if stick_first else 120
    h = top + body + 36
    im, d = new(width, h)
    xs = [80 + i * gap for i in range(n)]
    if stick_first:
        stick(d, xs[0], 12, actors[0], 0.85)
        for i, label in enumerate(actors[1:], start=1):
            box(d, xs[i] - 70, 28, 140, 52, label.split("\n"))
    else:
        for i, label in enumerate(actors):
            box(d, xs[i] - 70, 20, 140, 56, label.split("\n"))
    for x in xs:
        d.line((x, 88, x, h - 14), fill=BLACK, width=1)
    _draw_seq_steps(d, xs, top, steps, 20, width - 20)
    save(im, name)


def seq_auth() -> None:
    draw_sequence(
        "diag-seq-auth.png",
        ["Utilisateur", "Système"],
        [
            ("msg", 0, 1, "Ouvrir l'écran de connexion"),
            ("msg", 1, 0, "Formulaire de connexion"),
            ("opt", "client web", [("msg", 0, 1, "Cocher « se souvenir de moi »")]),
            ("msg", 0, 1, "Soumettre courriel et mot de passe"),
            (
                "alt",
                [
                    ("identifiants valides", [("msg", 1, 0, "Session web ou jeton Sanctum")]),
                    ("sinon", [("msg", 1, 0, "Erreurs 422")]),
                ],
            ),
        ],
        stick_first=True,
        gap=360,
    )


def seq_forgot() -> None:
    draw_sequence(
        "diag-seq-forgot.png",
        ["Utilisateur", "Système"],
        [
            ("msg", 0, 1, "Demander un lien (courriel)"),
            (
                "alt",
                [
                    ("compte existant", [("msg", 1, 0, "Envoi du lien et du jeton")]),
                    ("sinon", [("msg", 1, 0, "Erreur de validation 422")]),
                ],
            ),
            ("msg", 0, 1, "Nouveau mot de passe confirmé"),
            (
                "alt",
                [
                    ("jeton valide", [("msg", 1, 0, "Mot de passe mis à jour")]),
                    ("sinon", [("msg", 1, 0, "Mot de passe inchangé")]),
                ],
            ),
        ],
        stick_first=True,
        gap=360,
    )


def seq_create_user() -> None:
    draw_sequence(
        "diag-seq-create-user.png",
        ["Administrateur", "Contrôleur", "AdminUserService", "Modèle"],
        [
            ("msg", 0, 1, "POST /admin/users"),
            ("self", 1, "StoreUserRequest"),
            (
                "alt",
                [
                    (
                        "saisie valide",
                        [
                            ("msg", 1, 2, "createUser()"),
                            ("msg", 2, 3, "User.create"),
                            (
                                "loop",
                                "pour chaque team_id",
                                [("msg", 2, 3, "attacher l'équipe (team_user)")],
                            ),
                            (
                                "opt",
                                "team_id dans team_lead_ids",
                                [("msg", 2, 3, "is_team_lead = vrai, rôle TEAM_LEAD")],
                            ),
                            ("msg", 3, 2, "User persisté"),
                            ("msg", 2, 1, "User"),
                            ("msg", 1, 0, "Liste / JSON 201"),
                        ],
                    ),
                    ("sinon", [("msg", 1, 0, "Erreurs 422")]),
                ],
            ),
        ],
    )


def seq_create_task() -> None:
    draw_sequence(
        "diag-seq-create-task.png",
        ["Chef d'équipe", "TaskController", "TaskService", "Modèle"],
        [
            ("msg", 0, 1, "POST /projects/{id}/tasks"),
            ("self", 1, "StoreTaskRequest + politiques"),
            ("msg", 1, 2, "leadTeamsForProject()"),
            (
                "alt",
                [
                    (
                        "assigné dans l'équipe de contexte",
                        [
                            ("msg", 1, 3, "project.tasks().create()"),
                            ("msg", 3, 1, "Task"),
                            ("msg", 1, 0, "Tâche créée"),
                        ],
                    ),
                    ("sinon", [("msg", 2, 1, "ValidationException"), ("msg", 1, 0, "Erreurs 422")]),
                ],
            ),
        ],
    )


def seq_progress() -> None:
    draw_sequence(
        "diag-seq-progress.png",
        ["Membre", "TaskController", "TaskService", "Task / TaskUpdate"],
        [
            ("msg", 0, 1, "PATCH /tasks/{id}/progress"),
            ("self", 1, "UpdateTaskProgressRequest"),
            (
                "alt",
                [
                    ("BLOCKED sans note", [("msg", 1, 0, "Erreurs 422")]),
                    (
                        "sinon",
                        [
                            ("msg", 1, 2, "recordProgress()"),
                            ("msg", 2, 3, "Task.update + TaskUpdate.create"),
                            ("opt", "statut = DONE", [("msg", 2, 3, "poser completed_at")]),
                            ("msg", 3, 2, "Instantané enregistré"),
                            ("msg", 2, 1, "TaskUpdate"),
                            ("msg", 1, 0, "Tâche à jour"),
                        ],
                    ),
                ],
            ),
        ],
    )


def activity() -> None:
    im, d = new(920, 760)

    def rbox(x, y, w, h, text):
        box(d, x, y, w, h, wrap(d, text, fnt(13), w - 24))
        return x + w // 2, y + h // 2

    def diamond(cx, cy, wtext):
        s = 70
        d.polygon([(cx, cy - s), (cx + s, cy), (cx, cy + s), (cx - s, cy)], fill=GRAY, outline=BLACK, width=2)
        lines = wrap(d, wtext, fnt(12), 110)
        ty = cy - 7 * len(lines) + 2
        for line in lines:
            center_text(d, cx, ty, line, fnt(12))
            ty += 15
        return cx, cy

    def start_node(cx, cy):
        d.ellipse((cx - 10, cy - 10, cx + 10, cy + 10), fill=BLACK, outline=BLACK)
        return cx, cy

    def end_node(cx, cy):
        d.ellipse((cx - 12, cy - 12, cx + 12, cy + 12), outline=BLACK, width=2)
        d.ellipse((cx - 6, cy - 6, cx + 6, cy + 6), fill=BLACK, outline=BLACK)
        return cx, cy

    s = start_node(460, 28)
    a = rbox(310, 58, 300, 48, "Ouvrir la tâche assignée")
    b = rbox(310, 140, 300, 48, "Saisir statut, réalisé, prochain pas")
    blocked = diamond(460, 280, "Statut BLOCKED ?")
    note = diamond(200, 450, "Note renseignée ?")
    reject = rbox(20, 580, 230, 48, "Rejeter : note obligatoire")
    save_box = rbox(500, 580, 380, 48, "Mettre à jour la tâche et créer un TaskUpdate")
    fail = end_node(135, 690)
    ok = end_node(690, 690)

    arrow(d, (s[0], s[1] + 10), (a[0], a[1] - 24))
    arrow(d, (a[0], a[1] + 24), (b[0], b[1] - 24))
    arrow(d, (b[0], b[1] + 24), (blocked[0], blocked[1] - 70))

    d.line((blocked[0] - 70, blocked[1], note[0], blocked[1]), fill=BLACK, width=2)
    arrow(d, (note[0], blocked[1]), (note[0], note[1] - 70))
    center_text(d, 300, 264, "oui", fnt(12))

    d.line((blocked[0] + 70, blocked[1], save_box[0], blocked[1]), fill=BLACK, width=2)
    arrow(d, (save_box[0], blocked[1]), (save_box[0], save_box[1] - 24))
    center_text(d, 640, 264, "non", fnt(12))

    d.line((note[0] - 70, note[1], 135, note[1]), fill=BLACK, width=2)
    arrow(d, (135, note[1]), (135, reject[1] - 24))
    center_text(d, 108, 434, "non", fnt(12))
    arrow(d, (reject[0], reject[1] + 24), (fail[0], fail[1] - 12))

    d.line((note[0] + 70, note[1], save_box[0], note[1]), fill=BLACK, width=2)
    center_text(d, 400, 434, "oui", fnt(12))
    arrow(d, (save_box[0], save_box[1] + 24), (ok[0], ok[1] - 12))
    save(im, "diag-activity-progress.png")


def dashed_line(draw: ImageDraw.ImageDraw, a: tuple[float, float], b: tuple[float, float], dash: int = 8) -> None:
    x1, y1 = a
    x2, y2 = b
    length = math.hypot(x2 - x1, y2 - y1)
    steps = max(1, int(length // dash))
    for i in range(0, steps, 2):
        t1, t2 = i / steps, min(1, (i + 1) / steps)
        draw.line((x1 + (x2 - x1) * t1, y1 + (y2 - y1) * t1, x1 + (x2 - x1) * t2, y1 + (y2 - y1) * t2), fill=BLACK, width=2)


def class_diag() -> None:
    im, d = new(1420, 960)
    title_font, text_font, label_font = fnt(16, True), fnt(14), fnt(14)
    row = 22

    def klass(x, y, w, title, attrs, methods, stereotype=None):
        head = 36 if stereotype is None else 54
        attrs_h = row * len(attrs) + 14
        methods_h = row * len(methods) + 14 if methods else 14
        h = head + attrs_h + methods_h
        d.rectangle((x, y, x + w, y + h), outline=BLACK, width=2, fill=WHITE)
        d.rectangle((x, y, x + w, y + head), outline=BLACK, width=2, fill=GRAY)
        if stereotype is None:
            center_text(d, x + w / 2, y + head / 2, title, title_font)
        else:
            center_text(d, x + w / 2, y + 17, stereotype, fnt(13))
            center_text(d, x + w / 2, y + 37, title, title_font)
        ty = y + head + 8
        for line in attrs:
            d.text((x + 12, ty), line, font=text_font, fill=BLACK)
            ty += row
        d.line((x, y + head + attrs_h, x + w, y + head + attrs_h), fill=BLACK, width=2)
        ty = y + head + attrs_h + 8
        for line in methods:
            d.text((x + 12, ty), line, font=text_font, fill=BLACK)
            ty += row
        return x, y, w, h

    def path(points):
        for a, b in zip(points, points[1:]):
            d.line((*a, *b), fill=BLACK, width=2)

    def label(x, y, text, bold=False):
        d.text((x, y), text, font=fnt(14, True) if bold else label_font, fill=BLACK)

    klass(40, 30, 270, "UserRole", ["MEMBER", "TEAM_LEAD", "ADMIN"], [], "<<énumération>>")
    klass(1090, 30, 270, "TaskStatus", ["TODO", "IN_PROGRESS", "BLOCKED", "DONE"], ["+ progressPercent() : int"], "<<énumération>>")

    ux, uy, uw, uh = klass(480, 30, 300, "Utilisateur",
                           ["- id : int", "- name : string", "- job_title : string", "- email : string",
                            "- password : string", "- role : UserRole", "- team_id : int"],
                           ["+ isAdmin() : bool"])
    ucx, ubottom = ux + uw / 2, uy + uh

    ax, ay, aw, ah = klass(40, 330, 290, "Administrateur", ["{role = ADMIN}"],
                           ["+ createUser()", "+ createTeam()", "+ createProject()"])
    ex, ey, ew, eh = klass(480, 330, 300, "Employé", ["{role = MEMBER ou TEAM_LEAD}"],
                           ["+ isTeamLead() : bool", "+ belongsToTeam(équipe) : bool", "+ managedTeamIds() : int[]"])

    tri_base = ubottom + 22
    d.polygon([(ucx, ubottom), (ucx - 13, tri_base), (ucx + 13, tri_base)], outline=BLACK, fill=WHITE, width=2)
    bar_y = tri_base + 28
    path([(ucx, tri_base), (ucx, ey)])
    path([(ax + aw / 2, ay), (ax + aw / 2, bar_y), (ucx, bar_y)])

    px, py, pw, ph = klass(1060, 330, 300, "Projet", ["- id : int", "- name : string", "- description : text"], [])
    tx, ty, tw, th = klass(1040, 600, 320, "Tâche",
                           ["- id : int", "- title : string", "- description : text", "- status : TaskStatus",
                            "- progress_done : text", "- progress_next : text", "- blocker_note : text",
                            "- completed_at : datetime"],
                           [])
    qx, qy, qw, qh = klass(40, 640, 290, "Équipe", ["- id : int", "- name : string"], [])
    vx, vy, vw, vh = klass(560, 650, 330, "Avancement",
                           ["- id : int", "- status : TaskStatus", "- progress_done : text  (réalisé)",
                            "- progress_next : text  (à venir)", "- blocker_note : text", "- created_at : datetime"],
                           [])

    e_bottom = ey + eh
    chef_x, chef_y = qx + 70, 560
    path([(ex + 30, e_bottom), (ex + 30, chef_y), (chef_x, chef_y), (chef_x, qy)])
    label(ex + 38, e_bottom + 6, "0..1")
    label(chef_x + 8, qy - 26, "0..*")
    label((chef_x + ex + 30) / 2 - 18, chef_y - 24, "chef", True)
    member_x, member_y = qx + 200, 600
    path([(ex + 100, e_bottom), (ex + 100, member_y), (member_x, member_y), (member_x, qy)])
    label(ex + 108, e_bottom + 6, "*")
    label(member_x + 8, qy - 26, "1..*")
    label((member_x + ex + 100) / 2 - 40, member_y - 24, "membre de", True)

    link_y = ey + 60
    path([(ex + ew, link_y), (px, link_y)])
    label(ex + ew + 8, link_y - 24, "*")
    label(px - 30, link_y - 24, "*")
    label((ex + ew + px) / 2 - 45, link_y - 26, "/participe à", True)

    pcx = px + pw / 2 + 20
    dtop = py + ph
    diamond = [(pcx, dtop), (pcx - 10, dtop + 16), (pcx, dtop + 32), (pcx + 10, dtop + 16)]
    d.polygon(diamond, fill=BLACK, outline=BLACK)
    path([(pcx, dtop + 32), (pcx, ty)])
    label(pcx + 16, dtop + 6, "1")
    label(pcx + 16, ty - 26, "0..*")
    label(pcx - 92, (dtop + ty) / 2 - 8, "contient", True)

    a_start = (ex + ew - 40, e_bottom)
    a_end = (tx, ty + 70)
    path([a_start, a_end])
    label(a_start[0] + 10, a_start[1] + 6, "1")
    label(a_end[0] - 40, a_end[1] + 6, "0..*")
    mid = ((a_start[0] + a_end[0]) / 2, (a_start[1] + a_end[1]) / 2)
    label(mid[0] + 14, mid[1] - 34, "est assignée à", True)
    dashed_line(d, mid, (vx + vw / 2 + 60, vy))

    bottom_y = 920
    q_cx = qx + qw / 2
    right_x = 1395
    path([(q_cx, qy + qh), (q_cx, bottom_y), (right_x, bottom_y), (right_x, py + 50), (px + pw, py + 50)])
    label(q_cx + 8, qy + qh + 6, "*")
    label(px + pw + 6, py + 24, "*")
    label(640, bottom_y - 26, "travaille sur", True)

    save(im, "diag-classes.png")


def flutter_chain() -> None:
    im, d = new(980, 220)
    box(d, 30, 70, 200, 80, ["Flutter", "Riverpod, GoRouter"])
    box(d, 280, 70, 200, 80, ["HTTP JSON", "Dio + Bearer"])
    box(d, 530, 70, 200, 80, ["Laravel", "respond()"])
    d.ellipse((780, 70, 950, 150), fill=GRAY, outline=BLACK, width=2)
    center_text(d, 865, 110, "MySQL", fnt(16, True))
    arrow(d, (230, 110), (280, 110))
    arrow(d, (480, 110), (530, 110))
    arrow(d, (730, 110), (780, 110))
    save(im, "diag-flutter-chain.png")


PHP_KW = {
    "abstract",
    "class",
    "function",
    "if",
    "return",
    "public",
    "protected",
    "private",
    "use",
    "namespace",
    "new",
    "true",
    "false",
    "null",
    "match",
    "default",
    "static",
    "enum",
    "fn",
    "bool",
    "int",
    "string",
    "array",
    "self",
}
DART_KW = {
    "class",
    "final",
    "const",
    "return",
    "async",
    "await",
    "required",
    "if",
    "true",
    "false",
    "null",
    "this",
    "String",
    "Future",
}
CODE_MONO = "/System/Library/Fonts/Menlo.ttc"
VS_BG = (255, 255, 255)
VS_BAR = (243, 243, 243)
VS_LINE = (228, 228, 228)
VS_NUM = (35, 120, 147)
VS_TAB = (255, 255, 255)
VS_KW = (0, 0, 255)
VS_STR = (163, 21, 21)
VS_CMT = (0, 128, 0)
VS_TYPE = (38, 127, 153)
VS_FN = (121, 94, 38)


def _mono(size: int, bold: bool = False) -> ImageFont.FreeTypeFont:
    try:
        return ImageFont.truetype(CODE_MONO, size, index=1 if bold else 0)
    except OSError:
        return fnt(size, bold)


def code_shot(path: Path, start: int, end: int, out: str, lang: str) -> None:
    lines = path.read_text(encoding="utf-8").splitlines()[start - 1 : end]
    font, num_font = _mono(14), _mono(13)
    title_font = fnt(12)
    pad, lh = 16, 22
    title_h, tab_h = 28, 32
    status_h = 22
    rel = path.relative_to(ROOT)
    width = 980
    height = title_h + tab_h + pad * 2 + lh * len(lines) + status_h
    im, d = new(width, height)
    d.rectangle((0, 0, width, height), fill=VS_BG)
    d.rectangle((0, 0, width, title_h), fill=VS_BAR)
    for i, color in enumerate(((255, 95, 86), (255, 189, 46), (39, 201, 63))):
        d.ellipse((10 + i * 18, 8, 22 + i * 18, 20), fill=color)
    d.text((72, 7), f"{path.name} — Briefly — Visual Studio Code", font=title_font, fill=(80, 80, 80))
    d.rectangle((0, title_h, width, title_h + tab_h), fill=VS_BAR)
    tab_w = int(d.textlength(path.name, title_font)) + 28
    d.rectangle((8, title_h + 4, 8 + tab_w, title_h + tab_h), fill=VS_TAB)
    d.line((8, title_h + tab_h - 1, 8 + tab_w, title_h + tab_h - 1), fill=(0, 122, 204), width=2)
    d.text((18, title_h + 10), path.name, font=title_font, fill=BLACK)
    d.text((20 + tab_w, title_h + 10), str(rel), font=title_font, fill=(120, 120, 120))
    kws = PHP_KW if lang == "php" else DART_KW
    y = title_h + tab_h + pad
    for i, line in enumerate(lines, start=start):
        d.text((12, y), f"{i:>3d}", font=num_font, fill=VS_NUM)
        x = 56
        stripped = line.strip()
        if stripped.startswith(("//", "/*", "*", "#[")):
            d.text((x, y), line, font=font, fill=VS_CMT)
        else:
            parts = re.split(r"(\s+|['\"][^'\"]*['\"]|::|->|\(|\)|,)", line)
            for part in parts:
                color = BLACK
                token = part.strip()
                if part.startswith(("'", '"')):
                    color = VS_STR
                elif token in kws:
                    color = VS_KW
                elif token[:1].isupper() and token.isalnum():
                    color = VS_TYPE
                d.text((x, y), part, font=font, fill=color)
                x += d.textlength(part, font=font)
        y += lh
    d.rectangle((0, height - status_h, width, height), fill=(0, 122, 204))
    d.text((12, height - 17), f"{lang.upper()}  Ln {start}, Col 1  Visual Studio Code", font=fnt(11), fill=WHITE)
    d.rectangle((0, 0, width - 1, height - 1), outline=VS_LINE, width=1)
    save(im, out)


def main() -> None:
    CAP.mkdir(parents=True, exist_ok=True)
    crop_user_uc()
    scrum()
    uc_admin()
    uc_lead()
    uc_member()
    seq_auth()
    seq_forgot()
    arch_physical()
    arch_unified()
    arch_layers()
    seq_create_user()
    seq_create_task()
    seq_progress()
    activity()
    class_diag()
    flutter_chain()
    code_shot(ROOT / "app/Http/Controllers/Controller.php", 24, 43, "code-respond.png", "php")
    code_shot(ROOT / "app/Services/TaskService.php", 76, 93, "code-record-progress.png", "php")
    code_shot(ROOT / "mobile/lib/core/api_client.dart", 41, 52, "code-flutter-login.png", "dart")
    code_shot(ROOT / "app/Policies/TaskPolicy.php", 25, 29, "code-task-policy.png", "php")
    code_shot(ROOT / "app/Models/User.php", 19, 33, "code-user-hashed.png", "php")
    code_shot(ROOT / "app/Http/Controllers/LoginController.php", 34, 43, "code-sanctum-token.png", "php")
    code_shot(ROOT / "app/Http/Middleware/VerifyCsrfToken.php", 14, 25, "code-csrf.png", "php")
    code_shot(ROOT / "app/Enums/TaskStatus.php", 42, 50, "code-progress-percent.png", "php")
    code_shot(ROOT / "app/Http/Requests/StoreUserRequest.php", 20, 32, "code-store-user.png", "php")
    code_shot(ROOT / "app/Http/Requests/UpdateTaskProgressRequest.php", 19, 29, "code-blocker-rule.png", "php")


if __name__ == "__main__":
    main()
