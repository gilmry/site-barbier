#!/usr/bin/env bash
# Gate `front` — les îlots committés (www/islands/) correspondent à leurs sources (frontend/) : on recompile
# dans un dossier temporaire et on compare. Exit 0 = 🟢. Corriger : bash harness/build-front.sh
set -euo pipefail
source "$(dirname "${BASH_SOURCE[0]}")/lib.sh"
ensure_docker
docker run --rm -v "$BARBIER_ROOT":/app -w /app/frontend -u "$(id -u):$(id -g)" -e HOME=/tmp node:22-bookworm-slim bash -c '
  set -e
  [ -d node_modules ] && [ node_modules/.package-lock.json -nt package-lock.json ] || npm ci --no-audit --no-fund >/dev/null
  ISLANDS_OUT=/tmp/islands npx vite build --logLevel error >/dev/null
  if diff -r /tmp/islands ../www/islands >/dev/null; then echo "front: 🟢 îlots compilés à jour vs sources"
  else echo "front: 🔴 www/islands/ ne correspond pas aux sources — lancer : bash harness/build-front.sh"; diff -rq /tmp/islands ../www/islands | head; exit 1; fi'
