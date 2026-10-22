// Boîte de capture des e-mails des tests (Mailpit) : le parcours lit ce que le client recevrait vraiment.
const API = process.env.MAILPIT_API ?? 'http://127.0.0.1:18025';

/** Dernier e-mail reçu par `to` dont le sujet contient `subject` (attend jusqu'à 10 s). */
export async function lastMail(to, subject) {
  for (let i = 0; i < 40; i++) {
    const list = (await (await fetch(`${API}/api/v1/messages`)).json()).messages ?? [];
    const m = list.find((x) => x.To?.some((t) => t.Address === to) && x.Subject.includes(subject));
    if (m) return { ...(await (await fetch(`${API}/api/v1/message/${m.ID}`)).json()), Subject: m.Subject };
    await new Promise((r) => setTimeout(r, 250));
  }
  throw new Error(`aucun e-mail « ${subject} » pour ${to}`);
}
