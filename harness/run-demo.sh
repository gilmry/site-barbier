#!/usr/bin/env bash
# Doc vivante — rejoue les parcours en cadence (1 action/1 s), captures + vidéo → harness/demo/vitrine/ (NON bloquant).
# Un seul parcours : JOURNEY=<id> bash harness/run-demo.sh
set -euo pipefail
source "$(dirname "${BASH_SOURCE[0]}")/lib.sh"
ensure_php_image
ensure_vendor
ensure_browser_image
ensure_db
ensure_mailpit
start_web 18083 barbier_demo
trap stop_web EXIT
set +e
run_journeys demo 18083
exit 0   # preuve de valeur : jamais bloquante
