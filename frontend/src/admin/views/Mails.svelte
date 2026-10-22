<script>
  // Journal des e-mails (FR-032) : destinataires masqués, relance des envois en échec.
  import { api } from '../../shared/api';
  import { messageOf } from '../components/Errors.js';
  let entries = $state([]);
  let failed = $state(0);
  let error = $state('');
  let status = $state('');
  const kinds = { salon_booked: 'Salon — nouvelle réservation', salon_cancelled: 'Salon — annulation client', client_confirmed: 'Client — confirmation', client_cancelled: 'Client — annulation salon' };

  async function load() { const r = await api.getMailLog(); entries = r.entries; failed = r.failed; }
  load();

  async function retry(ids) {
    error = status = '';
    try {
      const r = await api.retryMails({ ids });
      const ok = r.results.filter((x) => x.state === 'sent').length;
      status = `${ok} envoi(s) réussi(s) sur ${r.results.length}.`;
      await load();
    } catch (e) { error = messageOf(e); }
  }
</script>

<h1>E-mails</h1>
{#if error}<p class="alert" role="alert">{error}</p>{/if}
{#if status}<p class="note" role="status">{status}</p>{/if}
{#if failed > 0}
  <p class="alert">{failed} envoi(s) en échec. <button class="small" onclick={() => retry(entries.filter((e) => e.state === 'failed').map((e) => e.id))}>Tout relancer</button></p>
{/if}
<table class="table">
  <thead><tr><th>Date (UTC)</th><th>Type</th><th>Destinataire</th><th>État</th><th></th></tr></thead>
  <tbody>
    {#each entries as e}
      <tr>
        <td>{e.createdAt}</td><td>{kinds[e.kind] ?? e.kind}</td><td>{e.recipient}</td>
        <td>{e.state === 'sent' ? 'Envoyé' : `Échec (${e.attempts} essai·s)`}</td>
        <td>{#if e.state === 'failed'}<button class="ghost small" onclick={() => retry([e.id])}>Relancer</button>{/if}</td>
      </tr>
    {:else}
      <tr><td class="hint">Aucun envoi pour l’instant.</td></tr>
    {/each}
  </tbody>
</table>
