// run.mjs — un parcours de référence, trois lectures (skills/documentation-vivante.md) :
//   MODE=e2e    rejoue à la vitesse, vérifie chaque étape                       → gate bloquant
//   MODE=visual capture les étapes « snapshot » et les compare aux goldens     → gate bloquant
//               (VISUAL_MODE=capture pour (ré)écrire les goldens après un changement voulu)
//   MODE=demo   rejoue en cadence (1 action, 1 seconde), captures + vidéo,     → rapport non bloquant
//               assemble la vitrine harness/demo/vitrine/index.html
// Les parcours viennent EXCLUSIVEMENT de journeys/*.journey.mjs (invariant J1 : pas de copie ici).
import { chromium } from 'playwright';
import { PNG } from 'pngjs';
import pixelmatch from 'pixelmatch';
import { readdirSync, readFileSync, writeFileSync, mkdirSync, existsSync, rmSync, renameSync } from 'node:fs';
import { dirname, resolve, join } from 'node:path';
import { fileURLToPath, pathToFileURL } from 'node:url';

const HERE = dirname(fileURLToPath(import.meta.url));
const ROOT = resolve(HERE, '../..');
const JOURNEYS_DIR = resolve(ROOT, 'journeys');
const MODE = process.env.MODE ?? 'e2e';
const BASE = process.env.BASE_URL ?? 'http://127.0.0.1:18081';
const ONLY = process.env.JOURNEY ?? '';
const CADENCE_MS = 1000; // une action, une seconde : un pas lisible par l'œil
const TOLERANCE = Number(process.env.VISUAL_TOLERANCE ?? '0.005'); // 0,5 % de pixels
const GOLDENS = resolve(ROOT, 'harness/visual/goldens');
const RESULTS = resolve(ROOT, 'harness/visual/test-results');
const VITRINE = resolve(ROOT, 'harness/demo/vitrine');
const VIEWPORTS = { desktop: { width: 1280, height: 900 }, mobile: { width: 390, height: 844 } };

async function loadJourneys() {
  const files = readdirSync(JOURNEYS_DIR).filter((f) => f.endsWith('.journey.mjs')).sort();
  const all = [];
  for (const f of files) all.push((await import(pathToFileURL(join(JOURNEYS_DIR, f)).href)).default);
  if (!ONLY) return all;
  // Un parcours filtré emmène les parcours dont il dépend (`requires`), dans l'ordre des fichiers.
  const wanted = new Set([ONLY]);
  let grew = true;
  while (grew) {
    grew = false;
    for (const j of all) if (wanted.has(j.id)) for (const r of j.requires ?? []) if (!wanted.has(r)) { wanted.add(r); grew = true; }
  }
  return all.filter((j) => wanted.has(j.id));
}

const SHARED = {}; // état partagé entre parcours d'une même exécution (ex. secret TOTP créé à l'installation)

function makeCtx(journey) {
  return {
    base: BASE,
    lang: journey.lang ?? 'fr',
    state: {},
    shared: SHARED,
    expect(cond, message) {
      if (!cond) throw new Error(message);
    },
  };
}

async function stabilise(page) {
  await page.evaluate(() => document.activeElement instanceof HTMLElement && document.activeElement.blur());
  await page.addStyleTag({ content: '*,*::before,*::after{animation:none!important;transition:none!important;caret-color:transparent!important}' }).catch(() => {});
  await page.waitForTimeout(150);
}

function compare(name, shotPath) {
  const shot = PNG.sync.read(readFileSync(shotPath));
  const goldenPath = resolve(GOLDENS, `${name}.png`);
  if (!existsSync(goldenPath) && process.env.VISUAL_MODE !== 'capture') {
    console.error(`    ✗ ${name} : golden absent — le capturer consciemment : VISUAL_MODE=capture bash harness/run-visual.sh`);
    return false;
  }
  if (process.env.VISUAL_MODE === 'capture') {
    writeFileSync(goldenPath, PNG.sync.write(shot));
    console.log(`    ⬇ golden écrit : ${name}.png (${shot.width}×${shot.height})`);
    return true;
  }
  const golden = PNG.sync.read(readFileSync(goldenPath));
  if (golden.width !== shot.width || golden.height !== shot.height) {
    console.error(`    ✗ ${name} : dimensions ${shot.width}×${shot.height} ≠ golden ${golden.width}×${golden.height}`);
    return false;
  }
  const diff = new PNG({ width: shot.width, height: shot.height });
  const changed = pixelmatch(golden.data, shot.data, diff.data, shot.width, shot.height, { threshold: 0.1 });
  const ratio = changed / (shot.width * shot.height);
  if (ratio > TOLERANCE) {
    writeFileSync(resolve(RESULTS, `${name}.diff.png`), PNG.sync.write(diff));
    console.error(`    ✗ ${name} : ${(ratio * 100).toFixed(3)} % de pixels changés (> ${(TOLERANCE * 100).toFixed(1)} %)`);
    return false;
  }
  console.log(`    ✓ ${name} : ${(ratio * 100).toFixed(3)} % (≤ ${(TOLERANCE * 100).toFixed(1)} %)`);
  return true;
}

async function runJourney(browser, journey, viewportName) {
  const viewport = VIEWPORTS[viewportName];
  const videoDir = resolve(VITRINE, 'videos', `${journey.id}-${viewportName}`);
  const context = await browser.newContext({
    viewport,
    locale: journey.lang === 'nl' ? 'nl-BE' : journey.lang === 'en' ? 'en-GB' : 'fr-BE',
    timezoneId: 'Europe/Brussels',
    reducedMotion: MODE === 'demo' ? 'no-preference' : 'reduce',
    ...(MODE === 'demo' ? { recordVideo: { dir: videoDir, size: viewport } } : {}),
  });
  const page = await context.newPage();
  const ctx = makeCtx(journey);
  const shots = [];
  let ok = true;
  console.log(`  ▶ ${journey.id} [${viewportName}] — ${journey.title}`);
  for (const [i, step] of journey.steps.entries()) {
    try {
      if (step.viewports && !step.viewports.includes(viewportName)) continue;
      await step.action(page, ctx);
      if (step.check) await step.check(page, ctx);
      if (MODE === 'visual' && step.snapshot) {
        await stabilise(page);
        const name = `${step.snapshot}-${viewportName}`;
        const p = resolve(RESULTS, `${name}.png`);
        const mask = (step.mask ?? []).map((sel) => page.locator(sel));
        await page.screenshot({ path: p, fullPage: true, animations: 'disabled', caret: 'hide', mask, maskColor: '#ff00ff' });
        ok = compare(name, p) && ok;
      }
      if (MODE === 'demo') {
        await page.waitForTimeout(CADENCE_MS);
        const file = `${journey.id}-${viewportName}-${String(i + 1).padStart(2, '0')}-${step.id}.png`;
        await page.screenshot({ path: resolve(VITRINE, 'captures', file) });
        shots.push({ file, description: step.description });
      }
      if (MODE === 'e2e') console.log(`    ✓ ${step.id} — ${step.description}`);
    } catch (e) {
      ok = false;
      console.error(`    ✗ ${step.id} — ${step.description}\n      ${String(e.message ?? e).split('\n')[0]}`);
      if (MODE !== 'demo') break;
    }
  }
  const video = page.video();
  await context.close();
  let videoFile = null;
  if (MODE === 'demo' && video) {
    const src = await video.path();
    videoFile = `videos/${journey.id}-${viewportName}.webm`;
    renameSync(src, resolve(VITRINE, videoFile));
    rmSync(videoDir, { recursive: true, force: true });
  }
  return { ok, shots, videoFile, viewport: viewportName };
}

function escapeHtml(s) {
  return String(s).replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
}

function writeVitrine(results) {
  const sections = results.map(({ journey, runs }) => `
  <section>
    <h2>${escapeHtml(journey.title)} <small>${escapeHtml(journey.id)} · ${escapeHtml(journey.lang ?? 'fr')}</small></h2>
    ${runs.map((r) => `
    <h3>${r.viewport} ${r.ok ? '' : '<span class="ko">parcours incomplet</span>'}</h3>
    ${r.videoFile ? `<video controls preload="metadata" src="${r.videoFile}"></video>` : ''}
    <ol class="steps">${r.shots.map((s) => `<li><figure><img loading="lazy" src="captures/${s.file}" alt=""><figcaption>${escapeHtml(s.description)}</figcaption></figure></li>`).join('')}</ol>`).join('')}
  </section>`).join('');
  const html = `<!doctype html><html lang="fr"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Vitrine — site-barbier</title>
<style>body{font:16px/1.5 system-ui,sans-serif;margin:0 auto;max-width:1200px;padding:16px;background:#0a0a0b;color:#f1f0ec}
h1{font-weight:600}h2{border-top:1px solid #4a4b50;padding-top:24px}small{color:#a9abb0;font-weight:400}
video{width:100%;max-width:900px;border:1px solid #4a4b50}.steps{display:grid;grid-template-columns:repeat(auto-fill,minmax(260px,1fr));gap:16px;padding:0;list-style:none}
figure{margin:0}img{width:100%;border:1px solid #4a4b50}figcaption{color:#d4d5d8;font-size:14px}.ko{color:#ff8a80;font-size:14px}</style></head>
<body><h1>Vitrine — preuve de valeur</h1><p>Parcours de référence rejoués en cadence (une action par seconde), générés depuis <code>journeys/</code>.</p>${sections}</body></html>`;
  writeFileSync(resolve(VITRINE, 'index.html'), html);
}

async function main() {
  mkdirSync(GOLDENS, { recursive: true });
  mkdirSync(RESULTS, { recursive: true });
  if (MODE === 'demo') {
    rmSync(VITRINE, { recursive: true, force: true });
    mkdirSync(resolve(VITRINE, 'captures'), { recursive: true });
    mkdirSync(resolve(VITRINE, 'videos'), { recursive: true });
  }
  const journeys = await loadJourneys();
  if (journeys.length === 0) throw new Error('aucun parcours dans journeys/');
  const browser = await chromium.launch({ args: ['--no-sandbox', '--disable-dev-shm-usage'] });
  const results = [];
  let ok = true;
  for (const journey of journeys) {
    const runs = [];
    for (const vp of journey.viewports ?? ['desktop', 'mobile']) {
      const r = await runJourney(browser, journey, vp);
      runs.push(r);
      ok = r.ok && ok;
    }
    results.push({ journey, runs });
  }
  await browser.close();
  if (MODE === 'demo') {
    writeVitrine(results);
    console.log(`\ndoc vivante: ${ok ? '🟢' : '🟠'} → harness/demo/vitrine/index.html (non bloquant)`);
    process.exit(0);
  }
  const label = MODE === 'visual' ? `visuel (${process.env.VISUAL_MODE ?? 'compare'})` : 'e2e (navigateur)';
  console.log(`\n${label}: ${ok ? '🟢' : '🔴'} ${journeys.length} parcours`);
  process.exit(ok ? 0 : 1);
}

main().catch((e) => {
  console.error(e);
  process.exit(1);
});
