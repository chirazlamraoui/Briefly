#!/usr/bin/env python3
"""Open Visual Studio Code in light mode and capture live editor screenshots."""

from __future__ import annotations

import json
import subprocess
import time
from pathlib import Path

CAP = Path("/Users/chiraz/Projects/Briefly/docs/rapport/captures")
ROOT = Path("/Users/chiraz/Projects/Briefly")
CODE = Path("/Users/chiraz/Downloads/Visual Studio Code.app/Contents/Resources/app/bin/code")
SETTINGS = Path("/Users/chiraz/Library/Application Support/Code/User/settings.json")
SHOTS = [
    ("app/Http/Controllers/Controller.php", 24, "code-respond.png"),
    ("app/Services/TaskService.php", 76, "code-record-progress.png"),
    ("app/Policies/TaskPolicy.php", 25, "code-task-policy.png"),
    ("mobile/lib/core/api_client.dart", 41, "code-flutter-login.png"),
    ("app/Enums/TaskStatus.php", 42, "code-progress-percent.png"),
    ("app/Http/Requests/StoreUserRequest.php", 20, "code-store-user.png"),
    ("app/Http/Requests/UpdateTaskProgressRequest.php", 19, "code-blocker-rule.png"),
    ("app/Models/User.php", 19, "code-user-hashed.png"),
    ("app/Http/Controllers/LoginController.php", 30, "code-sanctum-token.png"),
    ("app/Http/Middleware/VerifyCsrfToken.php", 14, "code-csrf.png"),
    ("app/Http/Middleware/EnsureAdmin.php", 9, "code-ensure-admin.png"),
    ("app/Policies/ProjectPolicy.php", 15, "code-project-policy.png"),
    ("app/Models/User.php", 58, "code-user-roles.png"),
    ("app/Services/TaskService.php", 95, "code-ensure-assignee.png"),
    ("mobile/lib/models/models.dart", 236, "code-flutter-blocker.png"),
]
OVERRIDE = {
    "workbench.colorTheme": "Default Light Modern",
    "window.autoDetectColorScheme": False,
    "editor.fontSize": 16,
    "editor.minimap.enabled": False,
    "workbench.activityBar.location": "hidden",
}


def code(*args: str) -> None:
    subprocess.run([str(CODE), *args], check=False)


def window_id() -> int | None:
    script = """
tell application "System Events"
    tell process "Code"
        set frontmost to true
        return id of window 1
    end tell
end tell
"""
    out = subprocess.run(["osascript", "-e", script], capture_output=True, text=True)
    if out.returncode != 0:
        return None
    try:
        return int(out.stdout.strip())
    except ValueError:
        return None


def quartz_window() -> int | None:
    try:
        import Quartz
    except ImportError:
        return None
    wins = Quartz.CGWindowListCopyWindowInfo(Quartz.kCGWindowListOptionOnScreenOnly, Quartz.kCGNullWindowID)
    best = None
    best_area = 0
    for w in wins:
        if w.get("kCGWindowOwnerName") != "Code":
            continue
        b = w.get("kCGWindowBounds") or {}
        area = int(b.get("Width", 0) * b.get("Height", 0))
        if area > best_area and int(b.get("Width", 0)) > 400:
            best_area = area
            best = int(w["kCGWindowNumber"])
    return best


def keystroke(keys: str) -> None:
    subprocess.run(["osascript", "-e", f'tell application "System Events" to keystroke "{keys}" using command down'], check=False)


def capture(dest: Path) -> None:
    wid = quartz_window() or window_id()
    dest.parent.mkdir(parents=True, exist_ok=True)
    if wid:
        subprocess.run(["screencapture", "-l", str(wid), "-o", "-x", str(dest)], check=True)
    else:
        subprocess.run(["screencapture", "-w", "-x", str(dest)], check=True)


def main() -> None:
    original = SETTINGS.read_text(encoding="utf-8")
    data = json.loads(original)
    data.update(OVERRIDE)
    SETTINGS.write_text(json.dumps(data, indent=4) + "\n", encoding="utf-8")
    try:
        code("-n", str(ROOT))
        time.sleep(4)
        subprocess.run(["osascript", "-e", 'tell application "Visual Studio Code" to activate'], check=False)
        time.sleep(1)
        subprocess.run(
            [
                "osascript",
                "-e",
                'tell application "System Events" to tell process "Code" to set position of window 1 to {40, 40}',
                "-e",
                'tell application "System Events" to tell process "Code" to set size of window 1 to {1180, 760}',
            ],
            check=False,
        )
        keystroke("b")
        time.sleep(0.4)
        keystroke("j")
        time.sleep(0.6)
        for rel, line, name in SHOTS:
            path = ROOT / rel
            code("-g", f"{path}:{line}")
            time.sleep(1.4)
            dest = CAP / name
            capture(dest)
            print(name, dest.stat().st_size if dest.exists() else 0)
    finally:
        SETTINGS.write_text(original, encoding="utf-8")


if __name__ == "__main__":
    main()
