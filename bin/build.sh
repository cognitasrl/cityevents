#!/usr/bin/env bash
set -euo pipefail

PLUGIN_SLUG="cityevents-widget"
ROOT_DIR="$(cd "$(dirname "$0")/.." && pwd)"
DIST_DIR="${ROOT_DIR}/dist"
TMP_DIR="${DIST_DIR}/${PLUGIN_SLUG}"

echo "📦 Build ZIP per plugin: ${PLUGIN_SLUG}"

# 1. Pulizia cartella dist
rm -rf "${DIST_DIR}"
mkdir -p "${TMP_DIR}"

# 2. Installazione composer senza dev
echo "➡️  Composer install..."
composer install --no-dev --optimize-autoloader --working-dir="${ROOT_DIR}"

# 3. Copia file escludendo quelli elencati in .distignore
echo "➡️  Copia file..."
rsync -av \
  --exclude-from="${ROOT_DIR}/.distignore" \
  "${ROOT_DIR}/" "${TMP_DIR}/"

# 4. Creazione archivio ZIP
echo "➡️  Creazione archivio..."
cd "${DIST_DIR}"
ZIP_NAME="${PLUGIN_SLUG}.zip"
zip -rq "${ZIP_NAME}" "${PLUGIN_SLUG}"

echo "✅ Creato: ${DIST_DIR}/${ZIP_NAME}"
