#!/usr/bin/env bash
# Gate `integration` — MySQL réelle, base de test recréée à chaque passage, rejoué sur chaque version
# de BARBIER_DB_VERSIONS (défaut « 8.4 8.0 », tant que la version d'OVH n'est pas confirmée). Exit 0 = 🟢.
set -euo pipefail
ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
for v in ${BARBIER_DB_VERSIONS:-8.4 8.0}; do
  echo "— MySQL $v"
  BARBIER_DB_VERSION="$v" bash -c '
    set -euo pipefail
    source "'"$ROOT"'/harness/lib.sh"
    ensure_php_image
    ensure_db
    reset_database barbier_test
    DB_NAME=barbier_test php_run "$BARBIER_PHP_IMAGE" php harness/integration/migrations.php'
done
