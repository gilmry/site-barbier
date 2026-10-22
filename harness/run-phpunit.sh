#!/usr/bin/env bash
# Gates `unit` + `bdd` (domaine + application, sans base) — via Docker, PHP 8.4. Exit 0 = 🟢.
# Un seul test : bash harness/run-phpunit.sh --filter RouterTest
set -euo pipefail
source "$(dirname "${BASH_SOURCE[0]}")/lib.sh"
ensure_php_image
ensure_vendor
exec docker run --rm -v "$BARBIER_ROOT":/app -w /app -u "$(id -u):$(id -g)" -e TZ=UTC "$BARBIER_PHP_IMAGE" \
  php harness/.phpunit.phar -c harness/phpunit.xml --exclude-testsuite Integration "$@"
