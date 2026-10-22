# Image navigateur — base PUBLIQUE node:22-bookworm + Chromium (Playwright). Sert aux trois harnais
# du parcours de référence (e2e, visuel, doc vivante). bookworm : `playwright install --with-deps` fonctionne.
# Les modules sont à la racine (/node_modules) : la résolution ESM les trouve depuis le dépôt monté en /app.
FROM node:22-bookworm
ENV PLAYWRIGHT_BROWSERS_PATH=/ms-playwright DEBIAN_FRONTEND=noninteractive
WORKDIR /opt/harness
COPY package.json ./
RUN npm install --no-audit --no-fund >/dev/null 2>&1 && npx playwright install --with-deps chromium >/dev/null \
 && mv node_modules /node_modules
