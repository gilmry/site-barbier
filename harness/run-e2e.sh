#!/usr/bin/env bash
# Gate `e2e` (navigateur) — rejoue chaque parcours de journeys/ à la vitesse (bloquant).
# Un seul parcours : JOURNEY=<id> bash harness/run-e2e.sh
set -euo pipefail
source "$(dirname "${BASH_SOURCE[0]}")/lib.sh"
ensure_php_image
ensure_vendor
ensure_browser_image
ensure_db
start_web 18081 barbier_e2e
trap stop_web EXIT
run_journeys e2e 18081
