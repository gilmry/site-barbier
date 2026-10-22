// Parcours P1 « réservation » (FR-022, FR-023, FR-031) : un visiteur néerlandophone réserve en ligne une coupe
// aux ciseaux chez Samir et reçoit sa confirmation en néerlandais. Parcours filmé « critique » (PRD §9).
import { lastMail } from './lib/mailbox.mjs';

export const CLIENT = { name: 'Jan Peeters', phone: '+32 470 12 34 56', email: 'jan.peeters@example.be' };

export default {
  id: 'reservation',
  title: 'Un visiteur réserve en ligne et reçoit sa confirmation dans sa langue',
  lang: 'nl',
  steps: [
    {
      id: 'ouvrir',
      description: "Sur le site en néerlandais, il clique « Online reserveren ».",
      action: async (page, ctx) => {
        await page.goto(`${ctx.base}/nl/`);
        await page.getByRole('link', { name: 'Online reserveren' }).click();
      },
      check: async (page) => { await page.getByRole('heading', { name: 'Kies je dienst' }).waitFor(); },
      snapshot: 'reservation-prestation',
    },
    {
      id: 'prestation',
      description: 'Il choisit « Knippen met de schaar » (60 min, € 34).',
      action: async (page) => { await page.getByRole('button', { name: /Knippen met de schaar/ }).click(); },
      check: async (page) => { await page.getByRole('heading', { name: 'Bij wie?' }).waitFor(); },
      snapshot: 'reservation-barbier',
    },
    {
      id: 'barbier',
      description: 'Il choisit Samir.',
      action: async (page) => { await page.getByRole('button', { name: /Samir/ }).click(); },
      check: async (page) => { await page.getByRole('heading', { name: 'Welke dag?' }).waitFor(); },
    },
    {
      id: 'jour',
      description: 'Il choisit le premier jour où Samir a un créneau libre (les jours complets sont grisés).',
      action: async (page, ctx) => {
        const day = page.locator('.calendar .day:not([disabled])').first();
        await day.waitFor();
        ctx.state.day = await day.getAttribute('aria-label');
        await day.click();
      },
      check: async (page) => { await page.getByRole('heading', { name: /Hoe laat\?/ }).waitFor(); },
    },
    {
      id: 'heure',
      description: 'Il choisit la dernière heure proposée ce jour-là.',
      action: async (page, ctx) => {
        const time = page.locator('.times .time').last();
        ctx.state.time = (await time.innerText()).trim();
        await time.click();
      },
      check: async (page) => { await page.getByRole('heading', { name: 'Je gegevens' }).waitFor(); },
    },
    {
      id: 'coordonnees',
      description: 'Il donne son nom, son téléphone et son e-mail, puis confirme.',
      action: async (page) => {
        await page.getByLabel('Naam').fill(CLIENT.name);
        await page.getByLabel('Telefoon').fill(CLIENT.phone);
        await page.getByLabel('E-mail (voor de bevestiging)').fill(CLIENT.email);
        await page.getByRole('button', { name: 'Reservatie bevestigen' }).click();
      },
      check: async (page, ctx) => {
        await page.getByRole('heading', { name: 'Gereserveerd!' }).waitFor();
        const recap = await page.locator('.done .recap').innerText();
        ctx.expect(recap.includes('Samir') && recap.includes(ctx.state.time), `récapitulatif inattendu : ${recap}`);
        ctx.shared.booking = { day: ctx.state.day, time: ctx.state.time };
      },
      snapshot: 'reservation-confirmee',
      mask: ['.recap'],
    },
    {
      id: 'e-mail',
      description: 'Il reçoit la confirmation en néerlandais, avec le lien pour annuler et le fichier calendrier.',
      action: async (page, ctx) => { ctx.state.mail = await lastMail(CLIENT.email, 'Je afspraak'); },
      check: async (page, ctx) => {
        ctx.expect(/\/nl\/annuleren\/[0-9a-f]{64}/.test(ctx.state.mail.Text), "lien d'annulation absent");
        ctx.expect((ctx.state.mail.Attachments ?? []).length === 1, 'fichier .ics absent');
      },
    },
    {
      id: 'creneau-pris',
      description: "Le créneau réservé n'est plus proposé à personne.",
      action: async (page, ctx) => {
        await page.getByRole('button', { name: 'Nieuwe reservatie' }).click();
        await page.getByRole('button', { name: /Knippen met de schaar/ }).click();
        await page.getByRole('button', { name: /Samir/ }).click();
        await page.locator(`.calendar .day[aria-label="${ctx.state.day}"]`).click().catch(() => {});
      },
      check: async (page, ctx) => {
        const times = await page.locator('.times .time').allInnerTexts().catch(() => []);
        ctx.expect(!times.map((t) => t.trim()).includes(ctx.state.time), `${ctx.state.time} encore proposé`);
      },
    },
  ],
};
