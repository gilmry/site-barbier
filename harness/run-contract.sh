#!/usr/bin/env bash
# Gate `contrat` — (1) parité routes ↔ spec et objets fermés (PHP) ; (2) client TypeScript généré à
# jour vs la spec (Node). Exit 0 = 🟢. Régénérer le client : (cd frontend && npm run gen:api)
set -euo pipefail
source "$(dirname "${BASH_SOURCE[0]}")/lib.sh"
ensure_php_image
ensure_vendor
docker run --rm -v "$BARBIER_ROOT":/app -w /app "$BARBIER_PHP_IMAGE" php harness/contract/check.php
docker run --rm -v "$BARBIER_ROOT":/app -w /app/frontend node:22-bookworm-slim bash -c '
  API_TS_OUT=/tmp/api.check.ts node scripts/gen-api.mjs >/dev/null
  if diff -q /tmp/api.check.ts src/generated/api.ts >/dev/null; then
    echo "contrat (client): 🟢 api.ts à jour vs la spec"
  else
    echo "contrat (client): 🔴 api.ts désynchronisé — lancer : (cd frontend && npm run gen:api)"; exit 1
  fi'
