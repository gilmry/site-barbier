#!/usr/bin/env bash
# CI locale — mêmes commandes qu'en CI (DRY), du plancher vers le sommet. Exit 0 = 🟢.
# Gates du socle (Story 0.1). Ajoutés par les stories suivantes : contrat (0.2), e2e navigateur,
# visuel et doc vivante (0.3), hébergement simulé (0.4), secrets/SBOM/SCA (0.5).
set -euo pipefail
ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
run() { echo ""; echo "▶ $*"; "$@"; }
run bash "$ROOT/harness/run-verify.sh"
run bash "$ROOT/harness/run-phpunit.sh"
run bash "$ROOT/harness/run-integration.sh"
run bash "$ROOT/harness/e2e-smoke.sh"
echo ""; echo "CI: 🟢 tous les gates bloquants du socle sont verts."
