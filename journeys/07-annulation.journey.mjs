// Parcours P2 « annulation » (FR-025, FR-030) : le client ouvre le lien reçu, confirme, le créneau est libéré
// et le salon est prévenu par e-mail.
import { lastMail } from './lib/mailbox.mjs';
import { CLIENT } from './05-reservation.journey.mjs';

export default {
  id: 'annulation',
  title: "Le client annule par le lien reçu ; le salon est prévenu",
  lang: 'nl',
  requires: ['reservation'],
  viewports: ['desktop'],
  steps: [
    {
      id: 'ouvrir-lien',
      description: "Il ouvre le lien d'annulation de son e-mail : le récapitulatif s'affiche, rien n'est encore annulé.",
      action: async (page, ctx) => {
        const mail = await lastMail(CLIENT.email, 'Je afspraak');
        ctx.state.link = mail.Text.match(/https?:\/\/\S+\/nl\/annuleren\/[0-9a-f]{64}/)[0].replace(/^https?:\/\/[^/]+/, ctx.base);
        await page.goto(ctx.state.link);
      },
      check: async (page) => { await page.getByRole('button', { name: 'Ja, afspraak annuleren' }).waitFor(); },
      snapshot: 'annulation-confirmation',
      mask: ['.recap'],
    },
    {
      id: 'confirmer',
      description: "Il confirme : le rendez-vous est annulé.",
      action: async (page) => { await page.getByRole('button', { name: 'Ja, afspraak annuleren' }).click(); },
      check: async (page) => { await page.getByText('Je afspraak is geannuleerd').waitFor(); },
    },
    {
      id: 'salon-prevenu',
      description: "Le salon reçoit l'e-mail d'annulation ; rouvrir le lien indique « déjà annulé ».",
      action: async (page, ctx) => {
        ctx.state.salonMail = await lastMail('contact@lecorbeau.be', 'Réservation annulée');
        await page.goto(ctx.state.link);
      },
      check: async (page, ctx) => {
        ctx.expect(ctx.state.salonMail.Text.includes(CLIENT.name), "nom du client absent de l'e-mail du salon");
        await page.getByText('Deze afspraak is al geannuleerd.').waitFor();
      },
    },
  ],
};
