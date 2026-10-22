#!/usr/bin/env bash
# Gate `integration` — MariaDB 10.11 réelle, base de test recréée à chaque passage. Exit 0 = 🟢.
set -euo pipefail
source "$(dirname "${BASH_SOURCE[0]}")/lib.sh"
ensure_php_image
ensure_mariadb
reset_database barbier_test
DB_NAME=barbier_test php_run "$BARBIER_PHP_IMAGE" php harness/integration/migrations.php
