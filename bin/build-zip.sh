#!/usr/bin/env bash
set -euo pipefail

SLUG="nimble-links"
ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
DIST_DIR="$ROOT_DIR/dist"
STAGE_DIR="$DIST_DIR/$SLUG"
ZIP_PATH="$DIST_DIR/$SLUG.zip"

cd "$ROOT_DIR"

echo "==> Cleaning dist/"
rm -rf "$DIST_DIR"
mkdir -p "$STAGE_DIR"

echo "==> Building JS assets"
npm run build

echo "==> Installing composer dependencies (production only)"
composer install --no-dev --optimize-autoloader --quiet

echo "==> Staging plugin files"
rsync -a \
  --exclude='.git' \
  --exclude='.github' \
  --exclude='.gitignore' \
  --exclude='.claude' \
  --exclude='.idea' \
  --exclude='.vscode' \
  --exclude='.phpunit.result.cache' \
  --exclude='node_modules' \
  --exclude='tests' \
  --exclude='js/sidebar' \
  --exclude='dist' \
  --exclude='bin' \
  --exclude='assets' \
  --exclude='README.md' \
  --exclude='composer.lock' \
  --exclude='package.json' \
  --exclude='package-lock.json' \
  --exclude='phpunit.xml' \
  --exclude='patchwork.json' \
  --exclude='.DS_Store' \
  ./ "$STAGE_DIR/"

echo "==> Creating zip"
cd "$DIST_DIR"
zip -rq "$SLUG.zip" "$SLUG"
cd "$ROOT_DIR"

echo "==> Restoring composer dev dependencies"
composer install --quiet

echo ""
echo "Done: $ZIP_PATH"
ls -lh "$ZIP_PATH"
