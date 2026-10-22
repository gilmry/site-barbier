#!/usr/bin/env bash
# Construit l'image de travail à partir de bases publiques. Idempotent.
set -euo pipefail
ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
source "$ROOT/harness/lib.sh"
ensure_php_image --force
echo "✓ image prête : $BARBIER_PHP_IMAGE"
