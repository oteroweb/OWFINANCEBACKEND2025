#!/usr/bin/env bash
set -euo pipefail

DEPLOY_PATH="${1:?Ruta requerida, p.ej. /home/finanzas2/owfinance}"
PHP_BIN="${2:-php}"

RELEASES_PATH="$DEPLOY_PATH/releases"
CURRENT_PATH="$DEPLOY_PATH/current"
SHARED_PATH="$DEPLOY_PATH/shared"
TIMESTAMP="$(date +%Y%m%d%H%M%S)"
NEW_RELEASE="$RELEASES_PATH/$TIMESTAMP"

# 1) crear release y volcar artefactos (rsync ya subió el contenido a releases/_upload)
mkdir -p "$NEW_RELEASE"

# mover el paquete subido por CI a la carpeta del release
rsync -a "$RELEASES_PATH/_upload/" "$NEW_RELEASE/"
rm -rf "$RELEASES_PATH/_upload"

# 2) enlazar .env y storage
if [ ! -d "$SHARED_PATH/storage" ]; then
  mkdir -p "$SHARED_PATH/storage"
fi
ln -sfn "$SHARED_PATH/.env" "$NEW_RELEASE/.env"
rm -rf "$NEW_RELEASE/storage"
ln -sfn "$SHARED_PATH/storage" "$NEW_RELEASE/storage"

# 3) optimizaciones laravel (usa vendor subido por CI)
cd "$NEW_RELEASE"
$PHP_BIN artisan key:generate --force || true
$PHP_BIN artisan storage:link || true
$PHP_BIN artisan config:cache
$PHP_BIN artisan route:cache || true
$PHP_BIN artisan view:cache || true
$PHP_BIN artisan migrate --force || true

# 4) permisos (lo que permita el hosting)
chmod -R ug+rwX storage bootstrap/cache || true

# 5) activar release
ln -sfn "$NEW_RELEASE" "$CURRENT_PATH"

echo "Deploy OK -> $NEW_RELEASE"
