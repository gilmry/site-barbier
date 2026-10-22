#!/usr/bin/env bash
# Preuve de sensibilité : chaque gate doit passer au ROUGE sur une dérive artificielle.
# Travaille sur une copie jetable du dépôt : le dépôt réel n'est jamais modifié.
# Exit 0 = toutes les dérives ont été détectées. Une sonde = une fonction drift_* + le gate attendu.
set -uo pipefail
SRC="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"

# --- Dérives (exécutées dans la copie) -------------------------------------------------------------
drift_g2()   { echo 'CREATE TABLE x (id CHAR(1));' > app/migrations/0099_x.up.sql; }
drift_g1()   { sed -i '/config.php/d' .gitignore; }
drift_h1()   { mkdir -p app/src/Domain && echo '<?php new PDO("x");' > app/src/Domain/Bad.php; }
drift_l1()   { echo '<?php' > www/info.php; }
drift_c1()   { rm frontend/src/generated/api.ts; }
drift_f1()   { mkdir -p frontend/src/x && echo 'fetch("/x")' > frontend/src/x/a.js; }
drift_unit() { python3 - <<'PY'
p='app/src/Http/Router.php'; s=open(p).read(); open(p,'w').write(s.replace('[^/]+)', '.+)'))
PY
}
drift_k1()   { python3 - <<'PY'
p='app/src/Kernel.php'; s=open(p).read()
s=s.replace("$router->api('GET', '/api/v1/health'", "$router->api('GET', '/api/v1/secret', fn () => Response::json([]));\n        $router->api('GET', '/api/v1/health'", 1)
open(p,'w').write(s)
PY
}
drift_k3()   { sed -i '0,/"additionalProperties": false/s//"additionalProperties": true/' openapi/site-barbier.openapi.json; }
drift_client() { echo '// retouche à la main' >> frontend/src/generated/api.ts; }
drift_down() { printf 'CREATE TABLE t (id CHAR(1));\n' > app/migrations/0099_t.up.sql; printf 'SELECT 1;\n' > app/migrations/0099_t.down.sql; }
drift_resp() { python3 - <<'PY'
p='app/src/Kernel.php'; s=open(p).read()
open(p,'w').write(s.replace("['status' => 'ok', 'database' => 'ok']", "['status' => 'ok', 'database' => 'ok', 'version' => 'x']", 1))
PY
}
drift_j1()   { echo "await page.goto('/x')" > harness/copie.mjs; }
drift_journey() { sed -i "s/body.status === 'ok'/body.status === 'parfait'/" journeys/socle.journey.mjs; }
drift_golden() { python3 - <<'PY'
import glob, struct, zlib
# remplace un golden par une image unie de même taille : la comparaison doit échouer
f=sorted(glob.glob('harness/visual/goldens/*.png'))[0]
d=open(f,'rb').read(); w,h=struct.unpack('>II',d[16:24])
raw=b''.join(b'\x00'+b'\xff\x00\x00'*w for _ in range(h))
chunk=lambda t,c: struct.pack('>I',len(c))+t+c+struct.pack('>I',zlib.crc32(t+c)&0xffffffff)
open(f,'wb').write(b'\x89PNG\r\n\x1a\n'+chunk(b'IHDR',struct.pack('>IIBBBBB',w,h,8,2,0,0,0))+chunk(b'IDAT',zlib.compress(raw))+chunk(b'IEND',b''))
PY
}
drift_uploads() { python3 - <<'PY'
p='www/uploads/.htaccess'; s=open(p).read()
open(p,'w').write(s.replace('Require all denied', 'Require all granted').replace('RemoveHandler', '# RemoveHandler').replace('php_flag engine off', ''))
PY
}
drift_repro() { sed -i 's/$epoch = mktime(0, 0, 0, 1, 1, 2020);/$epoch = time() + random_int(1, 999);/' harness/package/build.php; }
drift_secret() { printf '<?php\n$k = "AKIA%s";\n' "$(printf 'Q%.0s' {1..16})" > app/src/cle.php; }
drift_e2e()  { sed -i "s#'/api/v1/health'#'/api/v1/sante'#" app/src/Kernel.php; }

# --- Moteur ----------------------------------------------------------------------------------------
fail=0
probe() { # libellé, fonction de dérive, gate attendu au rouge
  local tmp before after; tmp="$(mktemp -d)"
  (cd "$SRC" && git ls-files -co --exclude-standard -z | xargs -0 -I{} cp --parents {} "$tmp")
  [ -d "$SRC/app/vendor" ] && cp -r "$SRC/app/vendor" "$tmp/app/vendor"
  before="$(cd "$tmp" && find . -type f -print0 | sort -z | xargs -0 md5sum 2>/dev/null | md5sum)"
  (cd "$tmp" && "$2") >/dev/null 2>&1
  after="$(cd "$tmp" && find . -type f -print0 | sort -z | xargs -0 md5sum 2>/dev/null | md5sum)"
  if [ "$before" = "$after" ]; then
    echo "  ✗ $1 : la dérive n'a rien modifié (sonde défectueuse)"; fail=$((fail+1))
  elif (cd "$tmp" && bash "harness/$3") >/dev/null 2>&1; then
    echo "  ✗ $1 : le gate $3 est resté VERT (il ne bloque pas)"; fail=$((fail+1))
  else
    echo "  ✓ $1 : $3 passe au rouge"
  fi
  rm -rf "$tmp"
}

probe "G2 migration sans down"     drift_g2     run-verify.sh
probe "G1 config non ignorée"      drift_g1     run-verify.sh
probe "H1 PDO dans le domaine"     drift_h1     run-verify.sh
probe "L1 PHP dans la racine web"  drift_l1     run-verify.sh
probe "C1 client généré absent"    drift_c1     run-verify.sh
probe "F1 fetch à la main"         drift_f1     run-verify.sh
probe "unit : routeur cassé"       drift_unit   run-phpunit.sh
probe "K1 route hors spec"         drift_k1     run-contract.sh
probe "K3 schéma objet ouvert"     drift_k3     run-contract.sh
probe "client édité à la main"     drift_client run-contract.sh
probe "integration : down incomplet" drift_down run-integration.sh
probe "integration : réponse hors contrat" drift_resp run-integration.sh
probe "e2e : santé cassée"         drift_e2e    e2e-smoke.sh
probe "hébergement : uploads exécutables" drift_uploads run-hosting.sh
probe "hébergement : paquet non reproductible" drift_repro run-hosting.sh
probe "plancher : secret commité"   drift_secret run-plancher.sh
probe "J1 parcours recopié"        drift_j1     run-verify.sh
probe "e2e navigateur : attente fausse" drift_journey run-e2e.sh
probe "visuel : rendu différent du golden" drift_golden run-visual.sh

[ "$fail" -eq 0 ] && echo -e "\nsensibilité: 🟢 chaque gate bloque sa dérive" || { echo -e "\nsensibilité: 🔴 $fail sonde(s) en échec"; exit 1; }
