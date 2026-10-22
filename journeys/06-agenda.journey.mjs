// Parcours P4 « agenda » (FR-021, Story 3.11) : le gérant ne peut pas poser d'absence sur un jour réservé ;
// une absence sur un jour libre retire bien les créneaux du barbier.
import { totpCode } from './lib/totp.mjs';
import { GERANT } from './01-installation.journey.mjs';

export default {
  id: 'agenda',
  title: "Le gérant pose une absence : refusée sur un jour réservé, appliquée sinon",
  lang: 'fr',
  requires: ['installation', 'reservation'],
  viewports: ['desktop'],
  steps: [
    {
      id: 'connexion',
      description: 'Il se connecte et ouvre « Agenda ».',
      action: async (page, ctx) => {
        await page.goto(`${ctx.base}/admin`);
        await page.getByLabel('Adresse e-mail').fill(GERANT.email);
        await page.getByLabel('Mot de passe').fill(GERANT.password);
        await page.getByRole('button', { name: 'Continuer' }).click();
        await page.getByLabel('Code à 6 chiffres').fill(await totpCode(ctx.shared.totpSecret, ctx.shared.totpState));
        await page.getByRole('button', { name: 'Valider' }).click();
        await page.getByRole('button', { name: 'Agenda', exact: true }).click();
      },
      check: async (page) => { await page.getByRole('heading', { name: 'Absences et fermetures' }).waitFor(); },
      snapshot: 'agenda-ecran',
      mask: ['.table'],
    },
    {
      id: 'absence-refusee',
      description: "Il tente de poser une absence pour Samir le jour où un client est réservé : c'est refusé, avec la liste.",
      action: async (page, ctx) => {
        const day = ctx.shared.booking.day;
        await page.getByLabel('Qui ?').selectOption({ label: 'Samir' });
        await page.getByLabel('Du', { exact: true }).fill(`${day}T00:00`);
        await page.getByLabel('Au', { exact: true }).fill(`${day}T23:45`);
        await page.getByRole('button', { name: 'Enregistrer', exact: true }).click();
      },
      check: async (page, ctx) => {
        const alert = page.getByRole('alert');
        await alert.waitFor();
        ctx.expect((await alert.innerText()).includes('Des réservations existent'), 'refus attendu');
      },
    },
    {
      id: 'absence-acceptee',
      description: "Il pose une absence pour Léa ce même jour : enregistrée, ses créneaux disparaissent.",
      action: async (page, ctx) => {
        const day = ctx.shared.booking.day;
        await page.getByLabel('Qui ?').selectOption({ label: 'Léa' });
        await page.getByLabel('Du', { exact: true }).fill(`${day}T00:00`);
        await page.getByLabel('Au', { exact: true }).fill(`${day}T23:45`);
        await page.getByRole('button', { name: 'Enregistrer', exact: true }).click();
      },
      check: async (page, ctx) => {
        await page.getByRole('status').filter({ hasText: 'Absence enregistrée' }).waitFor();
        const lea = 'b0000000-0000-4000-8000-000000000003';
        const res = await page.request.get(`${ctx.base}/api/v1/availability/slots?service=50000000-0000-4000-8000-000000000001&barber=${lea}&date=${ctx.shared.booking.day}`);
        ctx.expect((await res.json()).slots.length === 0, 'Léa a encore des créneaux ce jour-là');
      },
    },
  ],
};
