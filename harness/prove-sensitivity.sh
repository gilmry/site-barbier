#!/usr/bin/env bash
# Preuve de sensibilité : chaque gate doit passer au ROUGE sur une dérive artificielle.
# Travaille sur une copie jetable du dépôt : le dépôt réel n'est jamais modifié. Exit 0 = toutes les
# dérives ont été détectées.
set -uo pipefail
SRC="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
fail=0
probe() { # libellé, commande de dérive (dans la copie), gate à lancer
  local tmp; tmp="$(mktemp -d)"
  (cd "$SRC" && git ls-files -co --exclude-standard -z | xargs -0 -I{} cp --parents {} "$tmp")
  (cd "$tmp" && eval "$2") >/dev/null 2>&1
  if (cd "$tmp" && bash "harness/$3") >/dev/null 2>&1; then
    echo "  ✗ $1 : le gate $3 est resté VERT (il ne bloque pas)"; fail=$((fail+1))
  else
    echo "  ✓ $1 : $3 passe au rouge"
  fi
  rm -rf "$tmp"
}
probe "G2 migration sans down"     "echo 'CREATE TABLE x (id CHAR(1));' > app/migrations/0099_x.up.sql" run-verify.sh
probe "G1 config non ignorée"      "sed -i '/config.php/d' .gitignore"                                   run-verify.sh
probe "H1 PDO dans le domaine"     "mkdir -p app/src/Domain && echo '<?php new PDO(\"x\");' > app/src/Domain/Bad.php" run-verify.sh
probe "L1 PHP dans la racine web"  "echo '<?php' > www/info.php"                                         run-verify.sh
probe "unit : routeur cassé"       "sed -i 's/(?P<\$1>\[^\/\]+)/(?P<\$1>.+)/' app/src/Http/Router.php"   run-phpunit.sh
probe "integration : down incomplet" "printf 'CREATE TABLE t (id CHAR(1));\n' > app/migrations/0002_t.up.sql && printf 'SELECT 1;\n' > app/migrations/0002_t.down.sql" run-integration.sh
probe "e2e : santé cassée"          "sed -i \"s#'/api/v1/health'#'/api/v1/sante'#\" app/src/Kernel.php"   e2e-smoke.sh
[ "$fail" -eq 0 ] && echo -e "\nsensibilité: 🟢 chaque gate bloque sa dérive" || { echo -e "\nsensibilité: 🔴 $fail gate(s) décoratif(s)"; exit 1; }
