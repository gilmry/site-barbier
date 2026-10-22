#!/usr/bin/env bash
# Gate `e2e` (smoke HTTP du socle) — serveur PHP réel + MySQL, à travers le contrôleur frontal.
# Le parcours navigateur (Playwright) arrive avec la Story 0.3. Exit 0 = 🟢.
set -euo pipefail
source "$(dirname "${BASH_SOURCE[0]}")/lib.sh"
ensure_php_image
ensure_vendor
ensure_db
ensure_mailpit
reset_database barbier_e2e
PORT="${E2E_PORT:-18080}"
NAME="barbier-e2e-$$"
DB_NAME=barbier_e2e php_run "$BARBIER_PHP_IMAGE" php app/bin/migrate.php up >/dev/null
docker run -d --rm --name "$NAME" --network host -v "$BARBIER_ROOT":/app -w /app \
  -e DB_HOST=127.0.0.1 -e DB_PORT="$BARBIER_DB_PORT" -e DB_USER=root -e DB_PASSWORD="$BARBIER_DB_ROOT_PASSWORD" \
  -e DB_NAME=barbier_e2e "$BARBIER_PHP_IMAGE" php -S "127.0.0.1:$PORT" -t www harness/dev-router.php >/dev/null
trap 'docker stop "$NAME" >/dev/null 2>&1 || true' EXIT
for i in $(seq 1 40); do curl -s -o /dev/null "http://127.0.0.1:$PORT/" && break; sleep 0.25; done

fail=0
expect() { # méthode chemin statut_attendu motif_attendu libellé
  local out code body
  out="$(curl -s -X "$1" -w $'\n%{http_code}' "http://127.0.0.1:$PORT$2")"
  code="${out##*$'\n'}"; body="${out%$'\n'*}"
  if [ "$code" = "$3" ] && grep -q -- "$4" <<<"$body"; then echo "  ✓ $5 ($code)"; else echo "  ✗ $5 (reçu $code : $body)"; fail=$((fail+1)); fi
}
expect GET  /api/v1/health 200 '"status":"ok"'          "GET /api/v1/health → 200, base joignable"
expect GET  /api/v1/inconnu 404 '"status":404'          "route inconnue → 404 problem+json"
expect POST /api/v1/health 405 '"status":405'           "méthode non autorisée → 405"
expect GET  /index.php/../app/config.example.php 404 'html' "aucun fichier de app/ servi par le web (page 404)"

[ "$fail" -eq 0 ] && echo -e "\ne2e (smoke HTTP): 🟢" || { echo -e "\ne2e (smoke HTTP): 🔴 ($fail échec(s))"; exit 1; }
