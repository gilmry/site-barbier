#!/usr/bin/env bash
# Gate `verify` / `plancher` (structurel) — via Docker, PHP 8.4. Exit 0 = 🟢.
set -euo pipefail
source "$(dirname "${BASH_SOURCE[0]}")/lib.sh"
ensure_php_image
ensure_vendor
# Lint de syntaxe PHP 8.4 sur tout le code du projet, puis les invariants.
docker run --rm -v "$BARBIER_ROOT":/app -w /app "$BARBIER_PHP_IMAGE" bash -c '
  set -e
  find app www harness tests -name "*.php" -print0 | xargs -0 -n1 php -l >/dev/null
  php harness/verify.php'
