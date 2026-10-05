#!/usr/bin/env bash
# Spielt den lokalen Stand auf den Vorschau-Slot flash.profimedien.com.
# Aufruf: deploy/deploy-flash.sh [--seed] [--test]
#   --seed  Datenbank komplett neu aufbauen (migrate:fresh --seed), nur Testsystem!
#   --test  danach die Tests auf dem Server laufen lassen
set -euo pipefail

cd "$(dirname "$0")/.."

SSH_KEY="$HOME/.ssh/allinkl_preview"
REMOTE="ssh-w021a780@v1036236.kasserver.com"
ROOT="/www/htdocs/w021a780"
DIR="$ROOT/flash"
PHP="php84"

SEED=0; TEST=0
for a in "$@"; do
  case "$a" in
    --seed) SEED=1 ;;
    --test) TEST=1 ;;
    *) echo "Unbekannte Option: $a"; exit 1 ;;
  esac
done

echo "1/4 Frontend bauen"
if [ -f package.json ]; then
  [ -d node_modules ] || npm install --no-fund --no-audit
  npm run build
fi

echo "2/4 Dateien übertragen"
rsync -az --delete -e "ssh -i $SSH_KEY" \
  --exclude .git --exclude node_modules --exclude vendor \
  --exclude .env --exclude '.env.*' --exclude deploy/.deploy.env \
  --exclude storage --exclude public/storage --exclude public/hot \
  --exclude public/css/filament --exclude public/js/filament --exclude public/fonts/filament \
  ./ "$REMOTE:$DIR/"

echo "3/4 Composer, Migrationen, Cache"
if [ "$SEED" = 1 ]; then MIGRATE="migrate:fresh --seed --force"; else MIGRATE="migrate --force"; fi
ssh -i "$SSH_KEY" "$REMOTE" "cd $DIR && COMPOSER_MEMORY_LIMIT=-1 $PHP /usr/bin/composer install --no-interaction --quiet \
  && $PHP artisan $MIGRATE && $PHP artisan optimize:clear >/dev/null"

echo "4/4 Vorschau-Dashboard aktualisieren"
STAMP="$(date +%Y-%m-%dT%H:%M:%S%z | sed -E 's/([0-9]{2})([0-9]{2})$/\1:\2/')"
ssh -i "$SSH_KEY" "$REMOTE" "cat > $ROOT/dashboard/data/flash.json" <<JSON
{
  "slot": "flash",
  "client": "medi Gesundheitsgruppe · Interaktionsportal",
  "subdomain": "flash.profimedien.com",
  "webroot": "flash/public",
  "deployed_at": "$STAMP",
  "note": "Laravel-Prototyp MIP (Angebot 1689)"
}
JSON

if [ "$TEST" = 1 ]; then
  ssh -i "$SSH_KEY" "$REMOTE" "cd $DIR && $PHP artisan test --compact"
fi

echo "Fertig: https://flash.profimedien.com"
