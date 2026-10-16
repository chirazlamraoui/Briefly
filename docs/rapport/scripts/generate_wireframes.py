#!/usr/bin/env python3
"""Draw low-fidelity Briefly wireframes as SVG (importable in Figma) and render them to PNG."""

from __future__ import annotations

import shutil
import subprocess
import tempfile
from html import escape
from pathlib import Path

from PIL import Image

RAPPORT = Path("/Users/chiraz/Projects/Briefly/docs/rapport")
SVG_DIR = RAPPORT / "wireframes"
CAP = RAPPORT / "captures"
CHROME = "/Applications/Google Chrome.app/Contents/MacOS/Google Chrome"

INK = "#333333"
TEXT = "#555555"
LINE = "#9A9A9A"
FILL = "#F2F2F2"
BAR = "#D4D4D4"
WHITE = "#FFFFFF"
FONT = "Arial, Helvetica, sans-serif"

WEB_W, WEB_H = 1280, 800
MOBILE_W, MOBILE_H = 390, 844


class Frame:
    def __init__(self, w: int, h: int) -> None:
        self.w, self.h = w, h
        self.parts: list[str] = [f'<rect x="0" y="0" width="{w}" height="{h}" fill="{WHITE}"/>']

    def rect(self, x, y, w, h, fill=WHITE, stroke=LINE, rx=6, width=1.5, dash=False) -> None:
        dash_attr = ' stroke-dasharray="6 4"' if dash else ""
        self.parts.append(
            f'<rect x="{x}" y="{y}" width="{w}" height="{h}" rx="{rx}" fill="{fill}" '
            f'stroke="{stroke}" stroke-width="{width}"{dash_attr}/>'
        )

    def line(self, x1, y1, x2, y2, stroke=LINE, width=1.5) -> None:
        self.parts.append(f'<line x1="{x1}" y1="{y1}" x2="{x2}" y2="{y2}" stroke="{stroke}" stroke-width="{width}"/>')

    def circle(self, cx, cy, r, stroke=LINE, width=1.5, fill="none") -> None:
        self.parts.append(f'<circle cx="{cx}" cy="{cy}" r="{r}" fill="{fill}" stroke="{stroke}" stroke-width="{width}"/>')

    def text(self, x, y, value, size=14, bold=False, fill=INK, anchor="start", underline=False) -> None:
        weight = "bold" if bold else "normal"
        deco = ' text-decoration="underline"' if underline else ""
        self.parts.append(
            f'<text x="{x}" y="{y}" font-family="{FONT}" font-size="{size}" font-weight="{weight}" '
            f'fill="{fill}" text-anchor="{anchor}"{deco}>{escape(value)}</text>'
        )

    def bar(self, x, y, w, h=10) -> None:
        self.parts.append(f'<rect x="{x}" y="{y}" width="{w}" height="{h}" rx="{h / 2}" fill="{BAR}"/>')

    def placeholder(self, x, y, w, h) -> None:
        self.rect(x, y, w, h, fill=FILL, rx=4)
        self.line(x, y, x + w, y + h)
        self.line(x + w, y, x, y + h)

    def button(self, x, y, w, h, label, dark=True, size=14) -> None:
        self.rect(x, y, w, h, fill=INK if dark else WHITE, stroke=INK, rx=6)
        self.text(x + w / 2, y + h / 2 + size * 0.35, label, size, bold=dark, fill=WHITE if dark else INK, anchor="middle")

    def field(self, x, y, w, label, h=40, hint=None, select=False) -> int:
        self.text(x, y, label, 13, bold=True)
        self.rect(x, y + 8, w, h, rx=5)
        if hint:
            self.text(x + 12, y + 8 + h / 2 + 5, hint, 13, fill=LINE)
        if select:
            ax, ay = x + w - 22, y + 8 + h / 2 - 3
            self.parts.append(f'<path d="M{ax} {ay} l6 6 l6 -6" fill="none" stroke="{INK}" stroke-width="1.5"/>')
        return y + 8 + h + 26

    def pill(self, x, y, label, size=12) -> None:
        w = len(label) * size * 0.6 + 22
        self.rect(x, y, w, size + 10, fill=FILL, stroke=LINE, rx=(size + 10) / 2)
        self.text(x + w / 2, y + size + 2, label, size, fill=INK, anchor="middle")

    def progress(self, x, y, w, ratio, h=8) -> None:
        self.rect(x, y, w, h, fill=FILL, stroke="none", rx=h / 2, width=0)
        self.rect(x, y, w * ratio, h, fill=LINE, stroke="none", rx=h / 2, width=0)

    def card(self, x, y, w, h, title=None, action=None) -> int:
        self.rect(x, y, w, h, rx=10)
        if title is None:
            return y
        self.text(x + 16, y + 28, title, 14, bold=True)
        if action:
            self.button(x + w - 90, y + 12, 76, 26, action, dark=False, size=12)
        self.line(x, y + 44, x + w, y + 44)
        return y + 44

    def svg(self) -> str:
        body = "\n  ".join(self.parts)
        return (
            f'<svg xmlns="http://www.w3.org/2000/svg" width="{self.w}" height="{self.h}" '
            f'viewBox="0 0 {self.w} {self.h}">\n  {body}\n</svg>\n'
        )


def web_shell(f: Frame, nav: list[str], active: str, kicker: str, title: str, user: str, user_sub: str) -> None:
    f.rect(0, 0, 230, WEB_H, fill=FILL, stroke="none", rx=0, width=0)
    f.line(230, 0, 230, WEB_H)
    f.placeholder(20, 20, 30, 30)
    f.text(62, 41, "Briefly", 18, bold=True)
    y = 80
    for item in nav:
        if item == active:
            f.rect(12, y, 206, 36, fill=WHITE, stroke=INK, rx=6)
        f.rect(26, y + 11, 14, 14, fill="none", rx=3)
        f.text(52, y + 23, item, 14, bold=item == active)
        y += 44
    f.line(0, WEB_H - 120, 230, WEB_H - 120)
    f.circle(36, WEB_H - 86, 15)
    f.text(60, WEB_H - 90, user, 14, bold=True)
    f.text(60, WEB_H - 72, user_sub, 12, fill=TEXT)
    f.button(16, WEB_H - 50, 198, 30, "Déconnexion", dark=False, size=13)

    f.text(260, 34, kicker, 13, fill=TEXT)
    f.text(260, 58, title, 22, bold=True)
    f.rect(WEB_W - 130, 18, 104, 32, rx=16)
    f.text(WEB_W - 106, 39, "FR", 12, bold=True)
    f.text(WEB_W - 78, 39, "EN", 12)
    f.circle(WEB_W - 44, 34, 7)
    f.line(230, 76, WEB_W, 76)


def web_login() -> Frame:
    f = Frame(WEB_W, WEB_H)
    f.rect(0, 0, WEB_W, WEB_H, fill=FILL, stroke="none", rx=0, width=0)
    f.rect(WEB_W - 110, 18, 84, 32, rx=16)
    f.text(WEB_W - 88, 39, "FR", 12, bold=True)
    f.text(WEB_W - 56, 39, "EN", 12)
    x, y, w = 450, 190, 380
    f.rect(x, y, w, 420, rx=12)
    f.placeholder(x + w / 2 - 22, y + 28, 44, 44)
    f.text(x + w / 2, y + 108, "Briefly", 24, bold=True, anchor="middle")
    f.text(x + w / 2, y + 132, "Suivi de l’avancement des tâches pour les équipes", 13, fill=TEXT, anchor="middle")
    fy = f.field(x + 24, y + 172, w - 48, "E-mail", hint="nom@entreprise.fr")
    fy = f.field(x + 24, fy, w - 48, "Mot de passe", hint="••••••••")
    f.rect(x + 24, fy - 12, 14, 14, rx=3)
    f.text(x + 46, fy, "Se souvenir de moi", 13)
    f.text(x + w - 24, fy, "Mot de passe oublié ?", 13, anchor="end", underline=True)
    f.button(x + 24, fy + 22, w - 48, 42, "Se connecter")
    return f


def web_dashboard() -> Frame:
    f = Frame(WEB_W, WEB_H)
    web_shell(f, ["Tableau de bord", "Tâches", "Profil"], "Tableau de bord", "Vue d’ensemble", "Tableau de bord",
              "Nom du membre", "Nombre d’équipes")
    f.text(260, 104, "Bon retour, [prénom nom]", 15)
    f.bar(260, 116, 220)
    f.button(WEB_W - 110, 92, 84, 30, "Tâches", size=13)

    top = f.card(260, 146, 994, 170, "Mon taux d’accomplissement")
    f.circle(330, top + 62, 44, stroke=BAR, width=12)
    f.parts.append(
        f'<path d="M330 {top + 18} A44 44 0 0 1 374 {top + 62}" fill="none" stroke="{INK}" stroke-width="12"/>'
    )
    f.text(330, top + 68, "25 %", 18, bold=True, anchor="middle")
    f.text(400, top + 54, "N tâches terminées sur M", 15, bold=True)
    f.bar(400, top + 68, 300)

    top = f.card(260, 336, 994, 440, "Mes tâches", "Tout voir")
    y = top + 18
    for ratio, status in ((1.0, "Réalisé"), (0.6, "En cours"), (0.3, "Bloqué"), (0.0, "À faire")):
        f.text(276, y + 14, "Titre de la tâche", 15, bold=True)
        f.bar(276, y + 24, 160)
        f.pill(1150, y, status)
        f.text(276, y + 58, "Avancement", 12, fill=TEXT)
        f.text(1238, y + 58, f"{int(ratio * 100)} %", 12, bold=True, anchor="end")
        f.progress(276, y + 66, 962, ratio)
        y += 94
        if y < top + 380:
            f.line(260, y - 12, 1254, y - 12)
    return f


def web_task_detail() -> Frame:
    f = Frame(WEB_W, WEB_H)
    web_shell(f, ["Tableau de bord", "Tâches", "Profil"], "Tâches", "Détail de la tâche", "[Titre de la tâche]",
              "Nom du membre", "Nombre d’équipes")
    f.pill(260, 92, "En cours")
    f.text(360, 108, "Nom du projet", 13, fill=TEXT)
    f.button(WEB_W - 160, 90, 134, 30, "Retour aux tâches", dark=False, size=13)

    top = f.card(260, 140, 560, 380, "Détail de la tâche")
    y = top + 30
    for label in ("Équipe", "Projet", "Assigné à", "À faire", "Déjà fait", "En cours"):
        f.text(276, y, label, 14, bold=True)
        f.bar(420, y - 10, 260)
        y += 36

    top = f.card(840, 140, 414, 500, "Mettre à jour l’avancement")
    fy = f.field(856, top + 30, 382, "Statut de la tâche", hint="En cours", select=True)
    fy = f.field(856, fy, 382, "Déjà fait", h=64)
    fy = f.field(856, fy, 382, "En cours", h=64)
    fy = f.field(856, fy, 382, "Note de blocage (statut Bloqué)", h=52)
    f.button(856, fy - 8, 200, 38, "Enregistrer l’avancement", size=13)

    top = f.card(260, 540, 560, 236, "Historique des tâches")
    y = top + 20
    for _ in range(3):
        f.circle(284, y + 14, 8)
        f.bar(304, y + 4, 200)
        f.bar(304, y + 20, 340, 8)
        f.pill(720, y, "Statut")
        y += 56
    return f


def web_team_tasks() -> Frame:
    f = Frame(WEB_W, WEB_H)
    web_shell(f, ["Tableau de bord", "Tâches", "Tâches de l’équipe", "Projets", "Profil"], "Tâches de l’équipe",
              "Suivi de l’équipe", "Tâches de l’équipe", "Nom du chef", "Nombre d’équipes")
    x = 260
    for label in ("Total des tâches", "En cours", "Bloqué", "Terminées cette semaine"):
        f.rect(x, 96, 236, 96, rx=10)
        f.text(x + 118, 140, "N", 30, bold=True, anchor="middle")
        f.text(x + 118, 172, label, 13, fill=TEXT, anchor="middle")
        x += 252
    x = 260
    for i, chip in enumerate(("Toutes", "Bloqué", "En cours", "À faire", "Réalisé")):
        w = len(chip) * 8 + 24
        f.button(x, 212, w, 28, chip, dark=i == 0, size=12)
        x += w + 8

    f.rect(260, 256, 994, 520, rx=10)
    columns = (("Équipe", 276), ("Membre", 380), ("Tâche", 490), ("Projet", 670), ("Statut", 790),
               ("Déjà fait", 890), ("Blocage", 1060), ("Terminé", 1180))
    for name, cx in columns:
        f.text(cx, 284, name.upper(), 11, bold=True, fill=TEXT)
    f.line(260, 298, 1254, 298)
    y = 298
    statuses = ("Bloqué", "Bloqué", "En cours", "En cours", "À faire", "À faire", "Réalisé", "Réalisé")
    for status in statuses:
        f.bar(276, y + 22, 80)
        f.bar(380, y + 22, 90)
        f.bar(490, y + 16, 160)
        f.bar(490, y + 32, 110, 8)
        f.bar(670, y + 22, 100)
        f.pill(790, y + 14, status, 11)
        f.bar(890, y + 22, 150)
        f.bar(1060, y + 22, 90 if status == "Bloqué" else 20)
        f.bar(1180, y + 22, 50 if status == "Réalisé" else 20)
        y += 59
        f.line(260, y, 1254, y, stroke=BAR)
    return f


def web_admin_dashboard() -> Frame:
    f = Frame(WEB_W, WEB_H)
    web_shell(f, ["Vue d’ensemble", "Projets", "Équipes", "Utilisateurs", "Profil"], "Vue d’ensemble",
              "Administration", "Vue d’ensemble", "Administrateur", "Administrateur")
    x = 260
    for label in ("Équipes", "Projets", "Tâches", "Taux d’accomplissement"):
        f.rect(x, 96, 236, 120, rx=10)
        f.placeholder(x + 16, 112, 30, 30)
        f.text(x + 16, 178, "N", 28, bold=True)
        f.text(x + 16, 200, label, 13, fill=TEXT)
        x += 252

    for cx, title, rows in ((260, "Taux d’accomplissement par projet", 3), (760, "Taux d’accomplissement par équipe", 5)):
        top = f.card(cx, 236, 494, 316, title)
        y = top + 26
        for i in range(rows):
            f.text(cx + 16, y, "Nom", 14, bold=True)
            f.bar(cx + 60, y - 10, 140)
            f.text(cx + 478, y, "N %", 12, anchor="end")
            f.progress(cx + 16, y + 10, 462, 0.15 + 0.1 * (rows - i) / rows)
            y += 50

    top = f.card(260, 572, 994, 204, "Répartition des tâches")
    x = 276
    for label in ("À faire", "En cours", "Bloqué", "Réalisé"):
        f.rect(x, top + 20, 230, 110, fill=WHITE, rx=8)
        f.text(x + 16, top + 50, label, 14)
        f.text(x + 16, top + 92, "N", 26, bold=True)
        x += 242
    return f


def mobile_shell(f: Frame, title: str, active: str | None) -> int:
    f.rect(0, 0, MOBILE_W, MOBILE_H, fill=FILL, stroke="none", rx=0, width=0)
    f.rect(0, 0, MOBILE_W, 110, fill=WHITE, stroke="none", rx=0, width=0)
    f.text(20, 86, title, 22, bold=True)
    if active is not None:
        f.rect(0, MOBILE_H - 84, MOBILE_W, 84, fill=WHITE, stroke="none", rx=0, width=0)
        f.line(0, MOBILE_H - 84, MOBILE_W, MOBILE_H - 84)
        for i, tab in enumerate(("Accueil", "Tâches", "Profil")):
            cx = 65 + i * 130
            if tab == active:
                f.rect(cx - 26, MOBILE_H - 72, 52, 28, fill=FILL, stroke=INK, rx=14)
            f.rect(cx - 9, MOBILE_H - 67, 18, 18, fill="none", rx=4)
            f.text(cx, MOBILE_H - 24, tab, 12, bold=tab == active, anchor="middle")
    return 128


def mobile_login() -> Frame:
    f = Frame(MOBILE_W, MOBILE_H)
    f.rect(0, 0, MOBILE_W, MOBILE_H, fill=FILL, stroke="none", rx=0, width=0)
    f.placeholder(MOBILE_W / 2 - 28, 150, 56, 56)
    f.text(MOBILE_W / 2, 250, "Briefly", 28, bold=True, anchor="middle")
    f.text(MOBILE_W / 2, 276, "Suivi de l’avancement des tâches", 14, fill=TEXT, anchor="middle")
    f.rect(20, 310, 350, 300, rx=14)
    fy = f.field(40, 350, 310, "E-mail", h=46, hint="nom@entreprise.fr")
    fy = f.field(40, fy, 310, "Mot de passe", h=46, hint="••••••••")
    f.text(350, fy, "Mot de passe oublié ?", 13, anchor="end", underline=True)
    f.button(40, fy + 24, 310, 48, "Se connecter", size=15)
    return f


def mobile_dashboard() -> Frame:
    f = Frame(MOBILE_W, MOBILE_H)
    y = mobile_shell(f, "Tableau de bord", "Accueil")
    f.rect(20, y, 350, 120, rx=14)
    f.circle(80, y + 60, 26, stroke=BAR, width=8)
    f.parts.append(f'<path d="M80 {y + 34} A26 26 0 0 1 106 {y + 60}" fill="none" stroke="{INK}" stroke-width="8"/>')
    f.text(80, y + 65, "25 %", 13, bold=True, anchor="middle")
    f.text(130, y + 54, "Mon taux de complétion", 16, bold=True)
    f.text(130, y + 78, "N / M", 14, fill=TEXT)
    y += 150
    f.text(24, y, "Mes tâches", 18, bold=True)
    y += 16
    f.rect(20, y, 350, 4 * 78, rx=14)
    for i, status in enumerate(("Terminée", "En cours", "En cours", "À faire")):
        ry = y + i * 78
        f.text(38, ry + 32, "Titre de la tâche", 15, bold=True)
        f.bar(38, ry + 44, 150)
        f.pill(268, ry + 26, status)
        if i:
            f.line(20, ry, 370, ry)
    return f


def mobile_task_detail() -> Frame:
    f = Frame(MOBILE_W, MOBILE_H)
    y = mobile_shell(f, "[Titre de la tâche]", "Tâches")
    f.parts.append(f'<path d="M30 40 l-10 10 l10 10" fill="none" stroke="{INK}" stroke-width="2"/>')
    f.rect(20, y, 350, 150, rx=14)
    f.pill(38, y + 18, "En cours")
    f.bar(38, y + 56, 300)
    f.bar(38, y + 74, 220)
    f.text(38, y + 108, "Nom du projet", 13, fill=TEXT)
    f.text(38, y + 128, "Équipes liées", 13, fill=TEXT)
    y += 180
    f.text(24, y, "Mettre à jour l’avancement", 17, bold=True)
    y += 14
    f.rect(20, y, 350, 400, rx=14)
    fy = f.field(38, y + 26, 314, "Statut", h=44, hint="En cours", select=True)
    fy = f.field(38, fy, 314, "Ce qui a été fait", h=70)
    fy = f.field(38, fy, 314, "Prochaine étape", h=70)
    f.field(38, fy, 314, "Note de blocage", h=50)
    return f


SCREENS = {
    "web-login": web_login,
    "web-dashboard": web_dashboard,
    "web-task-detail": web_task_detail,
    "web-team-tasks": web_team_tasks,
    "web-admin-dashboard": web_admin_dashboard,
    "mobile-login": mobile_login,
    "mobile-dashboard": mobile_dashboard,
    "mobile-task-detail": mobile_task_detail,
}


def render(svg_path: Path, png_path: Path, w: int, h: int, workdir: Path) -> None:
    html = workdir / f"{svg_path.stem}.html"
    html.write_text(
        f'<html><body style="margin:0;background:#fff"><img src="{svg_path.as_uri()}" width="{w}" height="{h}"></body></html>',
        encoding="utf-8",
    )
    subprocess.run(
        [CHROME, "--headless", "--disable-gpu", "--hide-scrollbars", "--force-device-scale-factor=2",
         f"--window-size={w},{h}", f"--screenshot={png_path}", html.as_uri()],
        check=True, capture_output=True,
    )


def mobile_strip(names: list[str], out: str) -> None:
    phones = [Image.open(CAP / f"wf-{name}.png") for name in names]
    gap, pad = 60, 40
    w = sum(p.width for p in phones) + gap * (len(phones) - 1) + 2 * pad
    h = max(p.height for p in phones) + 2 * pad
    strip = Image.new("RGB", (w, h), "white")
    x = pad
    for phone in phones:
        framed = Image.new("RGB", (phone.width + 8, phone.height + 8), (154, 154, 154))
        framed.paste(phone, (4, 4))
        strip.paste(framed, (x - 4, pad - 4))
        x += phone.width + gap
    strip.save(CAP / out, "PNG", optimize=True)
    print(out, strip.size)


def main() -> None:
    SVG_DIR.mkdir(parents=True, exist_ok=True)
    workdir = Path(tempfile.mkdtemp())
    try:
        for name, build in SCREENS.items():
            frame = build()
            svg_path = SVG_DIR / f"{name}.svg"
            svg_path.write_text(frame.svg(), encoding="utf-8")
            render(svg_path, CAP / f"wf-{name}.png", frame.w, frame.h, workdir)
            print(f"wf-{name}.png")
    finally:
        shutil.rmtree(workdir, ignore_errors=True)
    mobile_strip(["mobile-login", "mobile-dashboard", "mobile-task-detail"], "wf-mobile.png")


if __name__ == "__main__":
    main()
