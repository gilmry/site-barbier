#!/usr/bin/env bash
# Bibliothèque commune des gates (sourcée). Noms propres au projet : ne jamais réutiliser les images
# ou conteneurs du kit (todo-kit-php = PHP 8.3, todo-mysql = MySQL 8.0).
BARBIER_ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
BARBIER_PHP_IMAGE="${BARBIER_PHP_IMAGE:-site-barbier-php:8.4}"
# Base : MySQL (offre OVH Perso, docs/hebergement.md). Version paramétrable : la CI rejoue
# l'intégration sur 8.0 et 8.4 tant que la version exacte d'OVH n'est pas confirmée.
BARBIER_DB_VERSION="${BARBIER_DB_VERSION:-8.4}"
BARBIER_DB_IMAGE="mysql:${BARBIER_DB_VERSION}"
BARBIER_DB_CONTAINER="${BARBIER_DB_CONTAINER:-barbier-mysql-${BARBIER_DB_VERSION}}"
case "$BARBIER_DB_VERSION" in 8.0) _port=13309 ;; *) _port=13308 ;; esac
BARBIER_DB_PORT="${BARBIER_DB_PORT:-$_port}"
BARBIER_DB_ROOT_PASSWORD="root"   # base de dev éphémère, locale uniquement
# Clé et code d'installation de DEV/TEST uniquement (jamais en production : config.php du serveur).
BARBIER_TEST_APP_KEY="Y2xlLWRlLWRldi1ldC10ZXN0LXVuaXF1ZW1lbnQtMzI="
BARBIER_TEST_INSTALL_CODE="installation-de-test"

ensure_docker() {
  command -v docker >/dev/null 2>&1 && docker info >/dev/null 2>&1 && return 0
  echo "[harnais] Docker est requis et ne répond pas. Installe ou démarre Docker, puis relance." >&2
  exit 2
}

# Construit l'image PHP si absente (ou toujours avec --force). L'empreinte du Dockerfile est
# inscrite en label : un Dockerfile modifié reconstruit l'image, jamais d'image périmée silencieuse.
ensure_php_image() {
  ensure_docker
  local sum; sum="$(sha256sum "$BARBIER_ROOT/docker/php.Dockerfile" | cut -c1-16)"
  local have; have="$(docker image inspect -f '{{index .Config.Labels "barbier.dockerfile"}}' "$BARBIER_PHP_IMAGE" 2>/dev/null || true)"
  if [ "${1:-}" != "--force" ] && [ "$have" = "$sum" ]; then return 0; fi
  echo "[harnais] build $BARBIER_PHP_IMAGE (php:8.4-cli + pdo_mysql, gd, intl)…" >&2
  docker build -q --label "barbier.dockerfile=$sum" -t "$BARBIER_PHP_IMAGE" \
    -f "$BARBIER_ROOT/docker/php.Dockerfile" "$BARBIER_ROOT/docker" >/dev/null
}

# Dépendances (app/vendor, verrouillées par composer.lock) : installées à froid si absentes, outils de dev
# compris (SBOM, PHPStan). Le paquet de déploiement, lui, réinstalle sans les outils de dev.
ensure_vendor() {
  [ -f "$BARBIER_ROOT/app/vendor/autoload.php" ] && [ -d "$BARBIER_ROOT/app/vendor/phpstan" ] && return 0
  echo "[harnais] composer install (dépendances verrouillées)…" >&2
  docker run --rm -v "$BARBIER_ROOT":/app -w /app -u "$(id -u):$(id -g)" -e COMPOSER_HOME=/tmp/composer \
    "$BARBIER_PHP_IMAGE" composer install --no-interaction --no-progress >/dev/null 2>&1
}

# MySQL éphémère (image publique officielle), prête à recevoir des connexions.
ensure_db() {
  ensure_docker
  if ! docker ps --filter "name=^${BARBIER_DB_CONTAINER}$" --format '{{.Names}}' | grep -qx "$BARBIER_DB_CONTAINER"; then
    docker rm -f "$BARBIER_DB_CONTAINER" >/dev/null 2>&1 || true
    docker run -d --name "$BARBIER_DB_CONTAINER" -e MYSQL_ROOT_PASSWORD="$BARBIER_DB_ROOT_PASSWORD" \
      -e MYSQL_DATABASE=barbier -p "127.0.0.1:${BARBIER_DB_PORT}:3306" "$BARBIER_DB_IMAGE" \
      --character-set-server=utf8mb4 --collation-server=utf8mb4_unicode_ci >/dev/null
  fi
  # Sonde en TCP : pendant l'initialisation, un serveur temporaire répond déjà sur le socket local.
  local i
  for i in $(seq 1 120); do
    if docker exec "$BARBIER_DB_CONTAINER" mysql --protocol=TCP -h127.0.0.1 -uroot -p"$BARBIER_DB_ROOT_PASSWORD" \
         -e 'SELECT 1' >/dev/null 2>&1; then
      return 0
    fi
    sleep 1
  done
  echo "[harnais] MySQL $BARBIER_DB_VERSION n'a pas démarré à temps (conteneur $BARBIER_DB_CONTAINER)." >&2
  return 1
}

# (Re)crée une base vide sur le serveur de dev.
reset_database() {
  docker exec "$BARBIER_DB_CONTAINER" mysql --protocol=TCP -h127.0.0.1 -uroot -p"$BARBIER_DB_ROOT_PASSWORD" \
    -e "DROP DATABASE IF EXISTS \`$1\`; CREATE DATABASE \`$1\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;" 2>/dev/null
}

# Exécute PHP dans l'image de travail, dépôt monté, réseau de l'hôte (accès à MySQL sur 127.0.0.1).
php_run() {
  docker run --rm --network host -v "$BARBIER_ROOT":/app -w /app -u "$(id -u):$(id -g)" \
    -e DB_HOST=127.0.0.1 -e DB_PORT="$BARBIER_DB_PORT" -e DB_USER=root -e DB_PASSWORD="$BARBIER_DB_ROOT_PASSWORD" \
    -e DB_NAME="${DB_NAME:-barbier}" -e TZ=UTC -e BARBIER_ENV=test -e APP_KEY="$BARBIER_TEST_APP_KEY" \
    -e INSTALL_CODE="$BARBIER_TEST_INSTALL_CODE" "$@"
}

BARBIER_BROWSER_IMAGE="${BARBIER_BROWSER_IMAGE:-site-barbier-browser:pw1.49}"

# Image navigateur (Chromium Playwright) — reconstruite si son Dockerfile ou son package.json change.
ensure_browser_image() {
  ensure_docker
  local sum; sum="$(cat "$BARBIER_ROOT/harness/browser/Dockerfile" "$BARBIER_ROOT/harness/browser/package.json" | sha256sum | cut -c1-16)"
  local have; have="$(docker image inspect -f '{{index .Config.Labels "barbier.browser"}}' "$BARBIER_BROWSER_IMAGE" 2>/dev/null || true)"
  [ "$have" = "$sum" ] && return 0
  echo "[harnais] build $BARBIER_BROWSER_IMAGE (node:22-bookworm + Chromium)…" >&2
  docker build -q --label "barbier.browser=$sum" -t "$BARBIER_BROWSER_IMAGE" "$BARBIER_ROOT/harness/browser" >/dev/null
}

# Serveur web de test sur une base neuve migrée (et semée si app/bin/seed.php existe).
# Usage : start_web <port> <base> ; le conteneur s'arrête avec stop_web.
start_web() {
  local port="$1" db="$2"
  BARBIER_WEB_CONTAINER="barbier-web-$port"
  reset_database "$db"
  DB_NAME="$db" php_run "$BARBIER_PHP_IMAGE" php app/bin/migrate.php up >/dev/null
  [ -f "$BARBIER_ROOT/app/bin/seed.php" ] && DB_NAME="$db" php_run "$BARBIER_PHP_IMAGE" php app/bin/seed.php >/dev/null
  docker rm -f "$BARBIER_WEB_CONTAINER" >/dev/null 2>&1 || true
  docker run -d --rm --name "$BARBIER_WEB_CONTAINER" --network host -v "$BARBIER_ROOT":/app -w /app -u "$(id -u):$(id -g)" \
    -e DB_HOST=127.0.0.1 -e DB_PORT="$BARBIER_DB_PORT" -e DB_USER=root -e DB_PASSWORD="$BARBIER_DB_ROOT_PASSWORD" \
    -e DB_NAME="$db" -e BARBIER_ENV=test -e APP_KEY="$BARBIER_TEST_APP_KEY" -e INSTALL_CODE="$BARBIER_TEST_INSTALL_CODE" \
    ${BARBIER_WEB_ENV:-} "$BARBIER_PHP_IMAGE" \
    php -S "127.0.0.1:$port" -t www harness/dev-router.php >/dev/null
  local i
  for i in $(seq 1 60); do curl -s -o /dev/null "http://127.0.0.1:$port/api/v1/health" && return 0; sleep 0.25; done
  echo "[harnais] le serveur web de test ne répond pas (port $port)." >&2; return 1
}
stop_web() { docker rm -f "${BARBIER_WEB_CONTAINER:-none}" >/dev/null 2>&1 || true; }

# Lance le moteur des parcours dans l'image navigateur. Usage : run_journeys <mode> <port> [env…]
run_journeys() {
  local mode="$1" port="$2"; shift 2
  docker run --rm --network host -v "$BARBIER_ROOT":/app -w /app -u "$(id -u):$(id -g)" -e HOME=/tmp \
    -e MODE="$mode" -e BASE_URL="http://127.0.0.1:$port" -e JOURNEY="${JOURNEY:-}" "$@" \
    "$BARBIER_BROWSER_IMAGE" node harness/browser/run.mjs
}
