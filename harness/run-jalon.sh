#!/usr/bin/env bash
# Exigence de jalon (rapports, NON bloquants — skills/gates.md, second registre). Triés par le PO en
# gate review au seuil CyFun Essential. Sorties dans dist/reports/ ; le résumé va au registre (ligne jalon).
#  SCA      composer audit · npm audit
#  SAST     moteur Semgrep (règles p/php, p/secrets) en SARIF — substitut compatible d'Opengrep tant
#           qu'aucune image publique n'existe (ADR-outillage §1 : le format SARIF est imposé, pas l'outil)
#  qualité  PHPStan niveau 8
set -uo pipefail
source "$(dirname "${BASH_SOURCE[0]}")/lib.sh"
ensure_php_image
ensure_vendor
R="$BARBIER_ROOT/dist/reports"; mkdir -p "$R"
docker run --rm -v "$BARBIER_ROOT":/app -w /app -u "$(id -u):$(id -g)" -e COMPOSER_HOME=/tmp/composer "$BARBIER_PHP_IMAGE" \
  composer audit --no-dev --format=json > "$R/composer-audit.json" 2>/dev/null
docker run --rm -v "$BARBIER_ROOT":/app -w /app/frontend -u "$(id -u):$(id -g)" -e HOME=/tmp node:22-bookworm-slim \
  npm audit --package-lock-only --json > "$R/npm-audit.json" 2>/dev/null
docker run --rm -v "$BARBIER_ROOT":/src -w /src semgrep/semgrep:1.99.0 semgrep scan --metrics=off --quiet \
  --config p/php --config p/secrets --sarif --output /src/dist/reports/sast.sarif app/src www >/dev/null 2>&1
docker run --rm -v "$BARBIER_ROOT":/app -w /app -u "$(id -u):$(id -g)" "$BARBIER_PHP_IMAGE" \
  php app/vendor/bin/phpstan analyse -c phpstan.neon --error-format=json --no-progress > "$R/phpstan.json" 2>/dev/null
python3 - "$R" <<'PY'
import json, sys, os
r = sys.argv[1]
def load(n):
    try: return json.load(open(os.path.join(r, n)))
    except Exception: return None
ca = load("composer-audit.json"); na = load("npm-audit.json"); sa = load("sast.sarif"); ps = load("phpstan.json")
adv = (ca or {}).get("advisories", {}) if ca is not None else None
comp = "?" if adv is None else sum(len(v) for v in (adv.values() if isinstance(adv, dict) else [adv]))
npm = (na or {}).get("metadata", {}).get("vulnerabilities", {}).get("total", "?") if na is not None else "?"
sast = sum(len(run.get("results", [])) for run in (sa or {}).get("runs", [])) if sa is not None else "?"
stan = (ps or {}).get("totals", {}).get("file_errors", "?") if ps is not None else "?"
print(f"  SCA composer : {comp} avis · SCA npm : {npm} vulnérabilités · SAST : {sast} findings · PHPStan 8 : {stan} erreurs")
print(f"JALON composer={comp} npm={npm} sast={sast} phpstan={stan}")
PY
echo -e "\njalon: rapports dans dist/reports/ (non bloquant, trié en gate review)"
exit 0
