#!/usr/bin/env bash
# Compile les îlots (Svelte 5 + Vite) dans www/islands/ — sources : frontend/. Régénère d'abord le client.
set -euo pipefail
source "$(dirname "${BASH_SOURCE[0]}")/lib.sh"
ensure_docker
docker run --rm -v "$BARBIER_ROOT":/app -w /app/frontend -u "$(id -u):$(id -g)" -e HOME=/tmp node:22-bookworm-slim bash -c '
  set -e
  [ -d node_modules ] && [ node_modules/.package-lock.json -nt package-lock.json ] || npm ci --no-audit --no-fund >/dev/null
  npm run build --silent'
