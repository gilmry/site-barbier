#!/usr/bin/env bash
# Construit dist/site-barbier-<version>.zip (+ .sha256) — reproductible : même commit = même empreinte.
# Les îlots compilés (www/islands/) doivent être à jour (bash harness/build-front.sh, à partir des stories à îlots).
set -euo pipefail
source "$(dirname "${BASH_SOURCE[0]}")/lib.sh"
ensure_php_image
VERSION="${PACKAGE_VERSION:-$(git -C "$BARBIER_ROOT" describe --always --dirty 2>/dev/null || echo dev)}"
mkdir -p "$BARBIER_ROOT/dist"
STAGING="$(mktemp -d "$BARBIER_ROOT/dist/.staging.XXXXXX")"
trap 'rm -rf "$STAGING"' EXIT
cp "$BARBIER_ROOT/composer.json" "$BARBIER_ROOT/composer.lock" "$STAGING/"
REL="${STAGING#"$BARBIER_ROOT"/}"
docker run --rm -v "$BARBIER_ROOT":/app -w "/app/$REL" -u "$(id -u):$(id -g)" -e COMPOSER_HOME=/tmp/composer "$BARBIER_PHP_IMAGE" \
  composer install --no-dev --no-interaction --no-progress --classmap-authoritative >/dev/null 2>&1
OUT="dist/site-barbier-$VERSION.zip"
docker run --rm -v "$BARBIER_ROOT":/app -w /app -u "$(id -u):$(id -g)" "$BARBIER_PHP_IMAGE" \
  php harness/package/build.php "$REL" "$OUT" "$VERSION"
(cd "$BARBIER_ROOT/dist" && sha256sum "$(basename "$OUT")" > "$(basename "$OUT").sha256")
echo "$BARBIER_ROOT/$OUT"
