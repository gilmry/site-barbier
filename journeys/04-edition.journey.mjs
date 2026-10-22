// Parcours P3 « édition » (FR-006 à FR-008, FR-040) : le gérant change un prix et la photo du bandeau
// d'accueil ; le site public les affiche aussitôt. Parcours filmé « critique » (défaut du PRD §9).
import { fileURLToPath } from 'node:url';
import { totpCode } from './lib/totp.mjs';
import { GERANT } from './01-installation.journey.mjs';

const PHOTO = fileURLToPath(new URL('./fixtures/fauteuil.jpg', import.meta.url));

export default {
  id: 'edition',
  title: 'Le gérant change un prix et la photo du bandeau, visibles aussitôt sur le site',
  lang: 'fr',
  requires: ['installation'],
  viewports: ['desktop'],
  steps: [
    {
      id: 'connexion',
      description: "Il se connecte : mot de passe, puis code de son appli.",
      action: async (page, ctx) => {
        await page.goto(`${ctx.base}/admin`);
        await page.getByLabel('Adresse e-mail').fill(GERANT.email);
        await page.getByLabel('Mot de passe').fill(GERANT.password);
        await page.getByRole('button', { name: 'Continuer' }).click();
        await page.getByLabel('Code à 6 chiffres').fill(await totpCode(ctx.shared.totpSecret, ctx.shared.totpState));
        await page.getByRole('button', { name: 'Valider' }).click();
      },
      check: async (page) => { await page.getByRole('heading', { name: 'Tableau de bord' }).waitFor(); },
    },
    {
      id: 'ouvrir-carte',
      description: 'Il ouvre « La carte » et choisit la coupe classique.',
      action: async (page) => {
        await page.getByRole('button', { name: 'La carte' }).click();
        await page.getByRole('button', { name: 'Modifier Coupe classique' }).click();
      },
      check: async (page) => { await page.getByRole('heading', { name: 'Modifier la prestation' }).waitFor(); },
      snapshot: 'edition-prestation',
    },
    {
      id: 'nouveau-prix',
      description: 'Il passe le prix de 28 € à 30 € et enregistre.',
      action: async (page) => {
        await page.getByLabel('Prix TTC (€)').fill('30');
        await page.getByRole('button', { name: 'Enregistrer' }).click();
      },
      check: async (page) => { await page.getByRole('status').filter({ hasText: 'Carte enregistrée' }).waitFor(); },
    },
    {
      id: 'envoyer-photo',
      description: 'Dans « Images », il envoie une photo du salon et la décrit.',
      action: async (page) => {
        await page.getByRole('button', { name: 'Images' }).click();
        await page.getByLabel(/Envoyer une image/).setInputFiles(PHOTO);
        await page.getByRole('tab', { name: /FR/ }).waitFor();
        await page.getByLabel(/Description de l’image.*\(FR\)/).fill('Le fauteuil de cuir');
        await page.getByRole('button', { name: 'Enregistrer' }).click();
      },
      check: async (page) => { await page.getByText('Le fauteuil de cuir').first().waitFor(); },
    },
    {
      id: 'photo-bandeau',
      description: "Dans « Contenu du site », il choisit cette photo pour le bandeau d'accueil.",
      action: async (page) => {
        await page.getByRole('button', { name: 'Contenu du site' }).click();
        await page.getByRole('button', { name: /Bandeau d’accueil/ }).click();
        const select = page.getByLabel('Image de la section');
        await select.locator('option', { hasText: 'Le fauteuil de cuir' }).first().waitFor({ state: 'attached' });
        const value = await select.locator('option', { hasText: 'Le fauteuil de cuir' }).first().getAttribute('value');
        await select.selectOption(value);
        await page.getByRole('button', { name: 'Enregistrer' }).click();
      },
      check: async (page) => { await page.getByRole('status').filter({ hasText: 'Enregistré' }).waitFor(); },
    },
    {
      id: 'voir-le-site',
      description: 'Il ouvre le site : le nouveau prix et la photo sont déjà en ligne.',
      action: async (page, ctx) => { await page.goto(`${ctx.base}/fr/`); },
      check: async (page, ctx) => {
        ctx.expect((await page.locator('#tarifs').innerText()).includes('30 €'), 'nouveau prix absent');
        ctx.expect(await page.locator('.hero-image').isVisible(), 'photo du bandeau absente');
      },
      snapshot: 'edition-site-mis-a-jour',
      mask: ['.hours'],
    },
  ],
};
