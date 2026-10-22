// Parcours « connexion » (FR-040, FR-041) : mot de passe puis code de l'appli ; déconnexion.
import { totpCode } from './lib/totp.mjs';
import { GERANT } from './01-installation.journey.mjs';

export default {
  id: 'connexion',
  title: "Le gérant se connecte avec son mot de passe et le code de son appli",
  lang: 'fr',
  requires: ['installation'],
  steps: [
    {
      id: 'ouvrir-admin',
      description: "Il ouvre l'administration : le site lui demande de se connecter.",
      action: async (page, ctx) => { await page.goto(`${ctx.base}/admin`); },
      check: async (page) => { await page.getByRole('heading', { name: 'Administration' }).waitFor(); },
      snapshot: 'connexion-formulaire',
    },
    {
      id: 'mauvais-mot-de-passe',
      description: "Une faute de frappe dans le mot de passe : un message clair, sans rien révéler.",
      action: async (page) => {
        await page.getByLabel('Adresse e-mail').fill(GERANT.email);
        await page.getByLabel('Mot de passe').fill('faute-de-frappe-123');
        await page.getByRole('button', { name: 'Continuer' }).click();
      },
      check: async (page, ctx) => {
        await page.getByRole('alert').waitFor();
        ctx.expect((await page.getByRole('alert').innerText()).includes('Identifiants ou code incorrects'), 'message attendu absent');
      },
    },
    {
      id: 'mot-de-passe',
      description: "Il saisit le bon mot de passe : le site demande le code de son appli.",
      action: async (page) => {
        await page.getByLabel('Mot de passe').fill(GERANT.password);
        await page.getByRole('button', { name: 'Continuer' }).click();
      },
      check: async (page) => { await page.getByRole('heading', { name: 'Code de vérification' }).waitFor(); },
      snapshot: 'connexion-code',
    },
    {
      id: 'code-appli',
      description: "Il saisit le code à 6 chiffres : il est connecté.",
      action: async (page, ctx) => {
        await page.getByLabel('Code à 6 chiffres').fill(await totpCode(ctx.shared.totpSecret, ctx.shared.totpState));
        await page.getByRole('button', { name: 'Valider' }).click();
      },
      check: async (page) => { await page.getByRole('heading', { name: 'Tableau de bord' }).waitFor(); },
      snapshot: 'connexion-tableau-de-bord',
    },
    {
      id: 'deconnexion',
      description: "Il se déconnecte : l'administration redemande ses identifiants.",
      action: async (page) => { await page.getByRole('button', { name: 'Se déconnecter' }).click(); },
      check: async (page) => { await page.getByRole('heading', { name: 'Administration' }).waitFor(); },
    },
  ],
};
