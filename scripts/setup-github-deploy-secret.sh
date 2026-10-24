#!/usr/bin/env bash
# One-time setup: store the alwaysdata deploy key in GitHub Actions secrets.
set -euo pipefail

REPO="${GITHUB_REPOSITORY:-chirazlamraoui/Briefly}"
KEY_FILE="${1:-$HOME/.ssh/alwaysdata_lamraoui}"

if ! command -v gh >/dev/null 2>&1; then
  echo "Install GitHub CLI (gh) first." >&2
  exit 1
fi

if ! gh auth status >/dev/null 2>&1; then
  echo "Sign in to GitHub (repo scope is enough for secrets):"
  gh auth login --hostname github.com --git-protocol ssh --web --scopes repo
fi

if [[ ! -f "$KEY_FILE" ]]; then
  echo "Missing key file: $KEY_FILE" >&2
  exit 1
fi

gh secret set ALWAYSDATA_SSH_KEY --repo "$REPO" < "$KEY_FILE"
echo "Secret ALWAYSDATA_SSH_KEY saved on $REPO."
echo "Re-run CI on main:"
echo "  gh workflow run ci.yml --repo $REPO --ref main"
