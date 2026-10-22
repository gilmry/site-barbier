#!/usr/bin/env bash
# Gate `hebergement` (Story 0.4) — le paquet s'installe et tourne sur un hébergement mutualisé simulé.
#  1. paquet reproductible (deux constructions = même empreinte) et sans fichier interdit ;
#  2. déposé dans php:8.4-apache (racine web = www/), il répond : santé 200 via Apache + .htaccess ;
#  3. durcissement : fichiers cachés, PHP hors contrôleur, PHP déposé dans uploads/ → refusés, jamais exécutés ;
#  4. mauvais déploiement (app/ sous la racine web) → app/ refusé (403).
# Exit 0 = 🟢.
set -euo pipefail
source "$(dirname "${BASH_SOURCE[0]}")/lib.sh"
ensure_php_image
ensure_vendor
ensure_db
IMG="site-barbier-hosting:8.4"
sum="$(sha256sum "$BARBIER_ROOT/docker/hosting.Dockerfile" | cut -c1-16)"
[ "$(docker image inspect -f '{{index .Config.Labels "barbier.hosting"}}' "$IMG" 2>/dev/null || true)" = "$sum" ] || \
  docker build -q --label "barbier.hosting=$sum" -t "$IMG" -f "$BARBIER_ROOT/docker/hosting.Dockerfile" "$BARBIER_ROOT/docker" >/dev/null

fail=0
ok()  { echo "  ✓ $1"; }
ko()  { echo "  ✗ $1"; fail=$((fail+1)); }

# 1. Reproductibilité et contenu.
Z1="$(PACKAGE_VERSION=repro bash "$BARBIER_ROOT/harness/package.sh" | tail -1)"; S1="$(sha256sum "$Z1" | cut -d' ' -f1)"
Z2="$(PACKAGE_VERSION=repro bash "$BARBIER_ROOT/harness/package.sh" | tail -1)"; S2="$(sha256sum "$Z2" | cut -d' ' -f1)"
[ "$S1" = "$S2" ] && ok "paquet reproductible (sha256 ${S1:0:12}… identique sur deux constructions)" || ko "paquet non reproductible ($S1 ≠ $S2)"
BAD="$(python3 - "$Z1" <<'PY'
import sys, zipfile, re
bad = [n for n in zipfile.ZipFile(sys.argv[1]).namelist()
       if re.search(r'(^|/)(\.git|tests?|harness|frontend|node_modules|journeys|docs)/', n) and not n.startswith('app/vendor/')
       or re.search(r'(^|/)config\.php$|\.(ts|svelte|md)$', n) and not n.startswith('app/vendor/')]
print('\n'.join(bad))
PY
)"
[ -z "$BAD" ] && ok "aucun fichier interdit (config.php, tests, harnais, sources front, .git)" || ko "fichiers interdits dans le paquet : $BAD"

# 2. Dépôt du paquet comme par SFTP, configuration écrite comme le ferait le PO.
SITE="$(mktemp -d "$BARBIER_ROOT/dist/.site.XXXXXX")"; NET="barbier-hosting-net"
cleanup() { docker rm -f barbier-hosting barbier-hosting-bad >/dev/null 2>&1 || true; docker network disconnect "$NET" "$BARBIER_DB_CONTAINER" >/dev/null 2>&1 || true; docker network rm "$NET" >/dev/null 2>&1 || true; rm -rf "$SITE"; }
trap cleanup EXIT
python3 -c "import zipfile,sys; zipfile.ZipFile(sys.argv[1]).extractall(sys.argv[2])" "$Z1" "$SITE"
reset_database barbier_hosting
cat > "$SITE/app/config.php" <<PHP
<?php return ['db' => ['host' => '$BARBIER_DB_CONTAINER', 'port' => 3306, 'database' => 'barbier_hosting', 'username' => 'root', 'password' => '$BARBIER_DB_ROOT_PASSWORD']];
PHP
echo '<?php echo "EXECUTE-UPLOAD";' > "$SITE/www/uploads/evil.php"
echo '<?php echo "EXECUTE-WWW";' > "$SITE/www/autre.php"
chmod -R a+rX "$SITE"
docker network create "$NET" >/dev/null 2>&1 || true
docker network connect "$NET" "$BARBIER_DB_CONTAINER" >/dev/null 2>&1 || true
docker run -d --rm --name barbier-hosting --network "$NET" -p 127.0.0.1:18090:80 -v "$SITE":/srv/site:ro "$IMG" >/dev/null
docker run -d --rm --name barbier-hosting-bad --network "$NET" -p 127.0.0.1:18091:80 -e SITE_DOCROOT=/srv/site -v "$SITE":/srv/site:ro "$IMG" >/dev/null
for i in $(seq 1 60); do curl -s -o /dev/null http://127.0.0.1:18090/ && curl -s -o /dev/null http://127.0.0.1:18091/ && break; sleep 0.5; done

status() { curl -s -o /dev/null -w '%{http_code}' "$1"; }
body()   { curl -s "$1"; }
[ "$(status http://127.0.0.1:18090/api/v1/health)" = 200 ] && body http://127.0.0.1:18090/api/v1/health | grep -q '"database":"ok"' \
  && ok "santé 200 à travers Apache et .htaccess, base joignable" || ko "santé KO : $(body http://127.0.0.1:18090/api/v1/health)"
curl -sI http://127.0.0.1:18090/api/v1/health | grep -qi '^x-content-type-options: nosniff' && ok "en-têtes de sécurité posés par .htaccess" || ko "en-tête nosniff absent"
[ "$(status http://127.0.0.1:18090/.htaccess)" = 403 ] && ok "fichier caché refusé (403)" || ko "fichier caché servi"
c="$(status http://127.0.0.1:18090/autre.php)"; b="$(body http://127.0.0.1:18090/autre.php)"
[ "$c" = 403 ] && ! grep -q EXECUTE <<<"$b" && ok "PHP hors contrôleur frontal refusé (403)" || ko "PHP hors contrôleur exécuté ou servi ($c)"
c="$(status http://127.0.0.1:18090/uploads/evil.php)"; b="$(body http://127.0.0.1:18090/uploads/evil.php)"
[ "$c" = 403 ] && ! grep -q EXECUTE <<<"$b" && ok "PHP déposé dans uploads/ refusé, jamais exécuté (403)" || ko "uploads/evil.php exécuté ou servi ($c)"
[ "$(status http://127.0.0.1:18091/app/config.php)" = 403 ] && [ "$(status http://127.0.0.1:18091/app/config.example.php)" = 403 ] \
  && ok "mauvais déploiement (app/ sous la racine web) : app/ refusé (403)" || ko "app/ servi quand il est sous la racine web"

[ "$fail" -eq 0 ] && echo -e "\nhebergement (simulé): 🟢" || { echo -e "\nhebergement (simulé): 🔴 ($fail échec(s))"; exit 1; }
