#!/usr/bin/env bash
# Commande unique de développement : image PHP 8.4, MySQL 8, migrations, serveur local.
# Idempotent : relancer ne change rien au dépôt. Port : FOYER_PORT (défaut 8080).
set -euo pipefail
source "$(dirname "${BASH_SOURCE[0]}")/harness/lib.sh"
PORT="${FOYER_PORT:-8080}"
ensure_php_image
ensure_db
php_run "$BARBIER_PHP_IMAGE" php app/bin/migrate.php up
if [ "${1:-}" = "--no-serve" ]; then echo "✓ prêt (sans serveur)"; exit 0; fi
if (exec 3<>"/dev/tcp/127.0.0.1/$PORT") 2>/dev/null; then
  echo "Le port $PORT est occupé. Relance avec un autre port : FOYER_PORT=8081 ./go" >&2; exit 3
fi
echo "▶ http://127.0.0.1:$PORT  (Ctrl+C pour arrêter)"
exec docker run --rm -it --network host -v "$BARBIER_ROOT":/app -w /app \
  -e DB_HOST=127.0.0.1 -e DB_PORT="$BARBIER_DB_PORT" -e DB_USER=root -e DB_PASSWORD="$BARBIER_DB_ROOT_PASSWORD" \
  -e DB_NAME=barbier "$BARBIER_PHP_IMAGE" php -S "127.0.0.1:$PORT" -t www harness/dev-router.php
