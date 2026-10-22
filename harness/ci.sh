#!/usr/bin/env bash
# CI locale — mêmes commandes qu'en CI (DRY), du plancher vers le sommet. Exit 0 = 🟢.
# Gates : plancher sécurité (0.5), socle (0.1), contrat (0.2), e2e navigateur + visuel + doc vivante
# (0.3), hébergement simulé (0.4), rapports de jalon (0.5). Miroir de .github/workflows/ci.yml.
set -euo pipefail
ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
run() { echo ""; echo "▶ $*"; "$@"; }
run bash "$ROOT/harness/run-plancher.sh"
run bash "$ROOT/harness/run-verify.sh"
run bash "$ROOT/harness/run-contract.sh"
run bash "$ROOT/harness/run-phpunit.sh"
run bash "$ROOT/harness/run-integration.sh"
run bash "$ROOT/harness/e2e-smoke.sh"
run bash "$ROOT/harness/run-e2e.sh"
run bash "$ROOT/harness/run-visual.sh"
run bash "$ROOT/harness/run-hosting.sh"
run bash "$ROOT/harness/run-demo.sh"   # preuve de valeur : non bloquante
run bash "$ROOT/harness/run-jalon.sh"  # rapports de jalon : non bloquants
echo ""; echo "CI: 🟢 tous les gates bloquants du socle sont verts."
