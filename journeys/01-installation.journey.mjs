// Parcours « installation » (FR-042, FR-040 enrôlement) : le gérant installe le site depuis le navigateur,
// sans ligne de commande, puis active son double facteur. Prérequis de tous les parcours d'administration.
import { totpCode } from './lib/totp.mjs';

export const GERANT = { email: 'gerant@lecorbeau.be', password: 'Cèdre-lame-serviette-9' };

export default {
  id: 'installation',
  title: "Le gérant installe le site et active le double facteur",
  lang: 'fr',
  viewports: ['desktop'],
  steps: [
    {
      id: 'ouvrir-installation',
      description: "Il ouvre la page d'installation : l'hébergement est vérifié point par point.",
      action: async (page, ctx) => { await page.goto(`${ctx.base}/install`); },
      check: async (page, ctx) => {
        ctx.expect(await page.getByRole('heading', { name: 'Installation du site' }).isVisible(), "titre d'installation absent");
        ctx.expect((await page.locator('.checks li.ko').count()) === 0, 'un contrôle bloquant est rouge');
      },
      snapshot: 'installation-formulaire',
    },
    {
      id: 'creer-compte',
      description: "Il crée son compte avec son e-mail, un mot de passe solide et le code d'installation.",
      action: async (page) => {
        await page.getByLabel('Adresse e-mail').fill(GERANT.email);
        await page.getByLabel('Mot de passe (12 caractères au moins)').fill(GERANT.password);
        await page.getByLabel('Mot de passe, à nouveau').fill(GERANT.password);
        await page.getByLabel("Code d'installation").fill('installation-de-test');
        await page.getByRole('button', { name: 'Installer et créer le compte' }).click();
      },
      check: async (page, ctx) => {
        await page.getByRole('heading', { name: 'Activer le double facteur' }).waitFor();
        ctx.expect(page.url().endsWith('/admin'), "pas de redirection vers l'administration");
      },
      snapshot: 'installation-activation',
      mask: ['.qr', '[data-testid=totp-secret]'],
    },
    {
      id: 'activer-double-facteur',
      description: "Il scanne le QR code avec son appli et saisit le code à 6 chiffres affiché.",
      action: async (page, ctx) => {
        ctx.shared.totpSecret = (await page.getByTestId('totp-secret').innerText()).trim();
        ctx.shared.totpState = {};
        await page.getByLabel('Code à 6 chiffres').fill(await totpCode(ctx.shared.totpSecret, ctx.shared.totpState));
        await page.getByRole('button', { name: 'Activer' }).click();
      },
      check: async (page, ctx) => {
        await page.getByRole('heading', { name: 'Vos codes de secours' }).waitFor();
        ctx.shared.recoveryCodes = (await page.getByTestId('recovery-codes').innerText()).split('\n').map((c) => c.trim()).filter(Boolean);
        ctx.expect(ctx.shared.recoveryCodes.length === 10, '10 codes de secours attendus');
      },
      snapshot: 'installation-codes',
      mask: ['[data-testid=recovery-codes]'],
    },
    {
      id: 'ranger-codes',
      description: "Il range ses codes de secours à l'abri et arrive sur le tableau de bord.",
      action: async (page) => {
        await page.getByLabel("J’ai mis ces codes à l’abri.").check();
        await page.getByRole('button', { name: 'Continuer' }).click();
      },
      check: async (page, ctx) => {
        await page.getByRole('heading', { name: 'Tableau de bord' }).waitFor();
        ctx.expect(await page.getByText(GERANT.email).isVisible(), "l'e-mail du gérant n'est pas affiché");
      },
    },
    {
      id: 'installation-fermee',
      description: "La page d'installation n'existe plus : personne ne peut réinstaller le site.",
      action: async (page, ctx) => { ctx.state.status = (await page.goto(`${ctx.base}/install`)).status(); },
      check: async (page, ctx) => ctx.expect(ctx.state.status === 404, `404 attendu, reçu ${ctx.state.status}`),
    },
  ],
};
