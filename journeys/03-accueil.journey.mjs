// Parcours « accueil » (FR-001 à FR-005) : un visiteur découvre le salon et passe en néerlandais.
export default {
  id: 'accueil',
  title: 'Un visiteur découvre le salon, puis lit le site en néerlandais',
  lang: 'fr',
  steps: [
    {
      id: 'arrivee',
      description: "Il ouvre l'adresse du site : il arrive sur la version française.",
      action: async (page, ctx) => { await page.goto(`${ctx.base}/`); },
      check: async (page, ctx) => {
        ctx.expect(page.url().endsWith('/fr/'), `redirection vers /fr/ attendue, reçu ${page.url()}`);
        ctx.expect(await page.getByRole('heading', { level: 1, name: 'Le Corbeau' }).isVisible(), 'titre du salon absent');
      },
      snapshot: 'accueil-fr',
      mask: ['.hours'],
    },
    {
      id: 'carte',
      description: 'Il consulte la carte : chaque prestation a son prix et sa durée.',
      action: async (page) => { await page.locator('#tarifs').scrollIntoViewIfNeeded(); },
      check: async (page, ctx) => {
        const carte = await page.locator('#tarifs').innerText();
        for (const t of ['Coupe classique', 'Le Rituel du Corbeau', '100 MIN']) ctx.expect(carte.toUpperCase().includes(t.toUpperCase()), `absent de la carte : ${t}`);
      },
    },
    {
      id: 'equipe-horaires',
      description: "Il découvre les trois barbiers, les horaires et l'adresse.",
      action: async (page) => { await page.locator('#horaires').scrollIntoViewIfNeeded(); },
      check: async (page, ctx) => {
        ctx.expect((await page.locator('#equipe .barbier').count()) === 3, 'trois barbiers attendus');
        ctx.expect((await page.locator('.hours tr').count()) === 7, 'sept jours attendus');
        ctx.expect((await page.locator('.hours tr.today').count()) === 1, 'le jour courant doit être signalé');
      },
    },
    {
      id: 'neerlandais',
      description: 'Il choisit NL : tout le site passe en néerlandais, prix au format belge.',
      action: async (page) => { await page.getByRole('link', { name: 'NL', exact: true }).first().click(); },
      check: async (page, ctx) => {
        await page.waitForURL(/\/nl\/$/);
        const body = await page.locator('body').innerText();
        for (const t of ['Een atelier, geen salon', '€ 28', 'Dinsdag']) ctx.expect(body.includes(t), `absent en NL : ${t}`);
      },
      snapshot: 'accueil-nl',
      mask: ['.hours'],
    },
    {
      id: 'confidentialite',
      description: 'Il ouvre la politique de confidentialité, dans sa langue.',
      action: async (page) => { await page.getByRole('link', { name: 'Privacy' }).click(); },
      check: async (page, ctx) => {
        await page.getByRole('heading', { name: 'Privacybeleid' }).waitFor();
        ctx.expect((await page.locator('main').innerText()).includes('6 maanden'), 'durée de conservation absente');
      },
    },
  ],
};
