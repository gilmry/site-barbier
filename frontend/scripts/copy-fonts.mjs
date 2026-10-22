// Copie les polices de la maquette (licence OFL, paquets @fontsource) dans www/assets/fonts/ :
// le site ne charge aucune police depuis un tiers (ADR-007). Sous-ensemble latin (FR, NL, EN).
import { copyFileSync, mkdirSync, existsSync } from 'node:fs';
import { dirname, resolve } from 'node:path';
import { fileURLToPath } from 'node:url';

const HERE = dirname(fileURLToPath(import.meta.url));
const OUT = resolve(HERE, '../../www/assets/fonts');
const NM = resolve(HERE, '../node_modules/@fontsource');
const files = [
  ['unifrakturmaguntia', 'unifrakturmaguntia-latin-400-normal.woff2', 'unifrakturmaguntia-latin-400.woff2'],
  ['cormorant-garamond', 'cormorant-garamond-latin-500-normal.woff2', 'cormorant-garamond-latin-500.woff2'],
  ['cormorant-garamond', 'cormorant-garamond-latin-600-normal.woff2', 'cormorant-garamond-latin-600.woff2'],
  ['cormorant-garamond', 'cormorant-garamond-latin-500-italic.woff2', 'cormorant-garamond-latin-500-italic.woff2'],
  ['cinzel', 'cinzel-latin-600-normal.woff2', 'cinzel-latin-600.woff2'],
];
mkdirSync(OUT, { recursive: true });
for (const [pkg, src, dest] of files) {
  const from = resolve(NM, pkg, 'files', src);
  if (!existsSync(from)) throw new Error(`police introuvable : ${from}`);
  copyFileSync(from, resolve(OUT, dest));
}
copyFileSync(resolve(NM, 'cinzel', 'LICENSE'), resolve(OUT, 'OFL-LICENSE.txt'));
console.log(`polices copiées : ${files.length} → www/assets/fonts/`);
