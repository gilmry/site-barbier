#!/usr/bin/env bash
# Gate `plancher` (sécurité, bloquant — skills/gates.md §Le plancher) — exit 0 = 🟢.
#  secrets      Gitleaks sur l'arbre de travail ET l'historique git (aucun réseau)
#  SBOM         CycloneDX JSON des dépendances PHP (sans dev) et npm (îlots) → dist/sbom/
#  migrations   réversibles : couvert par `verify` (G2) et `integration` (up → down → up)
#  conteneur    N/A — le projet ne livre aucune image (paquet zip)
set -euo pipefail
source "$(dirname "${BASH_SOURCE[0]}")/lib.sh"
ensure_php_image
ensure_vendor
GITLEAKS="zricethezav/gitleaks:v8.21.2"
mkdir -p "$BARBIER_ROOT/dist/sbom"

docker run --rm -v "$BARBIER_ROOT":/repo -w /repo "$GITLEAKS" dir /repo --no-banner --redact \
  --config /repo/.gitleaks.toml >/dev/null 2>&1 \
  && echo "  ✓ secrets : aucun dans l'arbre de travail" \
  || { echo "  ✗ secrets détectés dans l'arbre de travail :"; docker run --rm -v "$BARBIER_ROOT":/repo -w /repo "$GITLEAKS" dir /repo --no-banner --redact --config /repo/.gitleaks.toml 2>&1 | tail -20; exit 1; }
if [ -d "$BARBIER_ROOT/.git" ]; then
  docker run --rm -v "$BARBIER_ROOT":/repo -w /repo "$GITLEAKS" git /repo --no-banner --redact \
    --config /repo/.gitleaks.toml >/dev/null 2>&1 \
    && echo "  ✓ secrets : aucun dans l'historique git" \
    || { echo "  ✗ secrets détectés dans l'historique git (les retirer de l'historique, puis les révoquer)"; exit 1; }
fi

docker run --rm -v "$BARBIER_ROOT":/app -w /app -u "$(id -u):$(id -g)" -e COMPOSER_HOME=/tmp/composer "$BARBIER_PHP_IMAGE" \
  composer CycloneDX:make-sbom --output-format=JSON --output-file=dist/sbom/php.cdx.json --omit=dev --spec-version=1.6 >/dev/null 2>&1
docker run --rm -v "$BARBIER_ROOT":/app -w /app/frontend -u "$(id -u):$(id -g)" -e HOME=/tmp node:22-bookworm-slim \
  npx --yes @cyclonedx/cyclonedx-npm@1.19.3 --package-lock-only --spec-version 1.6 --output-format JSON \
  --output-file ../dist/sbom/npm.cdx.json >/dev/null 2>&1
python3 - "$BARBIER_ROOT/dist/sbom" <<'PY'
import json, sys, os
ok = True
for name in ("php.cdx.json", "npm.cdx.json"):
    path = os.path.join(sys.argv[1], name)
    try:
        bom = json.load(open(path))
        assert bom.get("bomFormat") == "CycloneDX" and bom.get("specVersion", "").startswith("1.")
        print(f"  ✓ SBOM {name} : CycloneDX {bom['specVersion']}, {len(bom.get('components', []))} composants")
    except Exception as e:
        print(f"  ✗ SBOM {name} absent ou invalide ({e})"); ok = False
sys.exit(0 if ok else 1)
PY
echo "  · conteneur : N/A (aucune image livrée) · migrations : verify G2 + integration"
echo -e "\nplancher: 🟢"
