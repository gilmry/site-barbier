#!/usr/bin/env bash
# Commande unique de développement : image PHP 8.4, MySQL 8, migrations, serveur local.
# Idempotent : relancer ne change rien au dépôt. Port : FOYER_PORT (défaut 8080).
set -euo pipefail
source "$(dirname "${BASH_SOURCE[0]}")/harness/lib.sh"
PORT="${FOYER_PORT:-8080}"
git -C "$BARBIER_ROOT" config core.hooksPath .githooks 2>/dev/null || true   # hooks DRY avec la CI
ensure_php_image
ensure_vendor
ensure_db
ensure_mailpit
php_run "$BARBIER_PHP_IMAGE" php app/bin/migrate.php up
if [ "${1:-}" = "--no-serve" ]; then echo "✓ prêt (sans serveur)"; exit 0; fi
if (exec 3<>"/dev/tcp/127.0.0.1/$PORT") 2>/dev/null; then
  echo "Le port $PORT est occupé. Relance avec un autre port : FOYER_PORT=8081 ./go" >&2; exit 3
fi
# Même clé, même code d'installation et même SMTP de capture que les tests (harness/lib.sh, app_env).
app_env barbier dev
echo "▶ Site     http://127.0.0.1:$PORT  (Ctrl+C pour arrêter)"
echo "  Admin    http://127.0.0.1:$PORT/install au premier lancement (code : $BARBIER_TEST_INSTALL_CODE), ensuite /admin"
echo "  E-mails  http://127.0.0.1:18025  (Mailpit : aucun e-mail ne part réellement)"
exec docker run --rm -it --network host -v "$BARBIER_ROOT":/app -w /app \
  "${APP_ENV[@]}" "$BARBIER_PHP_IMAGE" php -S "127.0.0.1:$PORT" -t www harness/dev-router.php
