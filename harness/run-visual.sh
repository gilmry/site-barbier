#!/usr/bin/env bash
# Gate `visuel` — goldens par parcours et par taille d'écran, tolérance 0,5 % (bloquant). VISUAL_MODE=capture pour (ré)écrire les goldens après un changement de rendu voulu.
# Un seul parcours : JOURNEY=<id> bash harness/run-visual.sh
set -euo pipefail
source "$(dirname "${BASH_SOURCE[0]}")/lib.sh"
ensure_php_image
ensure_vendor
ensure_browser_image
ensure_db
start_web 18082 barbier_visual
trap stop_web EXIT
run_journeys visual 18082 -e VISUAL_MODE="${VISUAL_MODE:-compare}"
