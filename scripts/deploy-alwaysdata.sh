#!/usr/bin/env bash
# Publish the Laravel application to the alwaysdata account.
# The server .env, storage and bootstrap/cache are left in place.
set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
HOST="${ALWAYSDATA_HOST:-ssh-lamraoui.alwaysdata.net}"
USER_NAME="${ALWAYSDATA_USER:-lamraoui}"
REMOTE_DIR="${ALWAYSDATA_REMOTE:-www/briefly}"
KEY_FILE="${ALWAYSDATA_SSH_KEY_FILE:-$HOME/.ssh/alwaysdata_lamraoui}"
KNOWN_HOSTS="${ALWAYSDATA_KNOWN_HOSTS:-$ROOT/scripts/alwaysdata_known_hosts}"
HEALTH_URL="${ALWAYSDATA_HEALTH_URL:-https://lamraoui.alwaysdata.net}"

if [[ ! -f "$KEY_FILE" ]]; then
  echo "Missing SSH key: $KEY_FILE" >&2
  exit 1
fi

if [[ ! -f "$KNOWN_HOSTS" ]]; then
  echo "Missing known hosts: $KNOWN_HOSTS" >&2
  exit 1
fi

STAGE="$(mktemp -d)"
cleanup() {
  rm -rf "$STAGE"
}
trap cleanup EXIT

echo "Staging production tree"
rsync -a \
  --exclude '.git/' \
  --exclude '.github/' \
  --exclude '.cursor/' \
  --exclude '.claude/' \
  --exclude '.ai/' \
  --exclude '.codex/' \
  --exclude '.idea/' \
  --exclude '.nova/' \
  --exclude '.vscode/' \
  --exclude '.zed/' \
  --exclude 'mobile/' \
  --exclude 'docs/' \
  --exclude 'node_modules/' \
  --exclude 'vendor/' \
  --exclude 'tests/' \
  --exclude '.env' \
  --exclude '.env.*' \
  --exclude 'auth.json' \
  --exclude 'storage/' \
  --exclude 'bootstrap/cache/' \
  --exclude 'public/hot' \
  --exclude 'public/storage' \
  --exclude '.phpunit.result.cache' \
  --exclude '.phpunit.cache/' \
  --exclude '.DS_Store' \
  "$ROOT/" "$STAGE/"

echo "Installing production Composer dependencies"
(
  cd "$STAGE"
  composer install --no-dev --optimize-autoloader --no-interaction --no-progress --prefer-dist --no-scripts
)

RSYNC_RSH="ssh -i ${KEY_FILE} -o IdentitiesOnly=yes -o StrictHostKeyChecking=yes -o UserKnownHostsFile=${KNOWN_HOSTS}"
export RSYNC_RSH

REMOTE="${USER_NAME}@${HOST}"
ENV_BEFORE="$(ssh -i "$KEY_FILE" -o IdentitiesOnly=yes -o StrictHostKeyChecking=yes -o UserKnownHostsFile="$KNOWN_HOSTS" "$REMOTE" "sha256sum \"\$HOME/${REMOTE_DIR}/.env\"")"

echo "Uploading to ${REMOTE}:${REMOTE_DIR}/"
rsync -az --delete \
  --exclude '.env' \
  --exclude '.env.*' \
  --exclude 'storage/' \
  --exclude 'bootstrap/cache/' \
  --exclude 'public/storage' \
  -e "$RSYNC_RSH" \
  "$STAGE/" "${REMOTE}:${REMOTE_DIR}/"

ENV_AFTER="$(ssh -i "$KEY_FILE" -o IdentitiesOnly=yes -o StrictHostKeyChecking=yes -o UserKnownHostsFile="$KNOWN_HOSTS" "$REMOTE" "sha256sum \"\$HOME/${REMOTE_DIR}/.env\"")"
if [[ "$ENV_BEFORE" != "$ENV_AFTER" ]]; then
  echo "Remote .env changed during upload. Stopping before migrate." >&2
  exit 1
fi
echo "Remote .env unchanged"

echo "Running migrations and caches"
ssh -i "$KEY_FILE" -o IdentitiesOnly=yes -o StrictHostKeyChecking=yes -o UserKnownHostsFile="$KNOWN_HOSTS" "$REMOTE" "REMOTE_DIR='$REMOTE_DIR' bash -s" << 'REMOTE'
set -euo pipefail
cd "$HOME/$REMOTE_DIR"
test -f .env
grep -q '^APP_KEY=base64:' .env
php artisan down --retry=15 || true
cleanup() {
  php artisan up || true
}
trap cleanup EXIT
php artisan optimize:clear
php artisan package:discover --ansi
php artisan migrate --force --no-ansi
if [[ ! -e public/storage ]]; then
  php artisan storage:link
fi
php artisan optimize
php artisan up
trap - EXIT
REMOTE

echo "Checking ${HEALTH_URL}"
up="$(curl -fsS -o /dev/null -w '%{http_code}' "${HEALTH_URL}/up")"
login="$(curl -fsS -o /dev/null -w '%{http_code}' "${HEALTH_URL}/login")"
echo "GET /up -> ${up}"
echo "GET /login -> ${login}"
[[ "$up" == "200" ]]
[[ "$login" == "200" ]]
echo "Deployed"
