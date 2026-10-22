// Parcours du socle (Story 0.3) : le service répond et dit que sa base est joignable.
// Il sera remplacé, comme parcours principal, par P1 (réserver) et P3 (éditer) ; il reste comme sonde.
export default {
  id: 'socle',
  title: 'Le service répond et sa base est joignable',
  lang: 'fr',
  steps: [
    {
      id: 'ouvrir-sante',
      description: "On ouvre l'adresse de santé du service.",
      action: async (page, ctx) => { await page.goto(`${ctx.base}/api/v1/health`); },
      check: async (page, ctx) => {
        const body = JSON.parse(await page.locator('body').innerText());
        ctx.expect(body.status === 'ok', `status attendu « ok », reçu « ${body.status} »`);
        ctx.expect(body.database === 'ok', 'la base doit être joignable');
      },
      snapshot: 'socle-sante',
    },
  ],
};
