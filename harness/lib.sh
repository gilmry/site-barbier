#!/usr/bin/env bash
# Bibliothèque commune des gates (sourcée). Noms propres au projet : ne jamais réutiliser les images
# ou conteneurs du kit (todo-kit-php = PHP 8.3, todo-mysql = MySQL 8.0).
BARBIER_ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
BARBIER_PHP_IMAGE="${BARBIER_PHP_IMAGE:-site-barbier-php:8.4}"
BARBIER_DB_IMAGE="mariadb:10.11"
BARBIER_DB_CONTAINER="${BARBIER_DB_CONTAINER:-barbier-mariadb}"
BARBIER_DB_PORT="${BARBIER_DB_PORT:-13307}"
BARBIER_DB_ROOT_PASSWORD="root"   # base de dev éphémère, locale uniquement

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

# MariaDB 10.11 éphémère (image publique), prête à recevoir des connexions.
ensure_mariadb() {
  ensure_docker
  if ! docker ps --filter "name=^${BARBIER_DB_CONTAINER}$" --format '{{.Names}}' | grep -qx "$BARBIER_DB_CONTAINER"; then
    docker rm -f "$BARBIER_DB_CONTAINER" >/dev/null 2>&1 || true
    docker run -d --name "$BARBIER_DB_CONTAINER" -e MARIADB_ROOT_PASSWORD="$BARBIER_DB_ROOT_PASSWORD" \
      -e MARIADB_DATABASE=barbier -p "127.0.0.1:${BARBIER_DB_PORT}:3306" "$BARBIER_DB_IMAGE" \
      --character-set-server=utf8mb4 --collation-server=utf8mb4_unicode_ci >/dev/null
  fi
  local i
  for i in $(seq 1 90); do
    if docker exec "$BARBIER_DB_CONTAINER" mariadb -uroot -p"$BARBIER_DB_ROOT_PASSWORD" -e 'SELECT 1' >/dev/null 2>&1; then
      return 0
    fi
    sleep 1
  done
  echo "[harnais] MariaDB n'a pas démarré à temps (conteneur $BARBIER_DB_CONTAINER)." >&2
  return 1
}

# (Re)crée une base vide sur le serveur de dev.
reset_database() {
  docker exec "$BARBIER_DB_CONTAINER" mariadb -uroot -p"$BARBIER_DB_ROOT_PASSWORD" \
    -e "DROP DATABASE IF EXISTS \`$1\`; CREATE DATABASE \`$1\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
}

# Exécute PHP dans l'image de travail, dépôt monté, réseau de l'hôte (accès à MariaDB sur 127.0.0.1).
php_run() {
  docker run --rm --network host -v "$BARBIER_ROOT":/app -w /app -u "$(id -u):$(id -g)" \
    -e DB_HOST=127.0.0.1 -e DB_PORT="$BARBIER_DB_PORT" -e DB_USER=root -e DB_PASSWORD="$BARBIER_DB_ROOT_PASSWORD" \
    -e DB_NAME="${DB_NAME:-barbier}" -e TZ=UTC "$@"
}
