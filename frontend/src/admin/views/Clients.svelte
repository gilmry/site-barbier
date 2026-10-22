<script>
  // Données des clients (FR-012) : retrouver les réservations d'une adresse, les exporter ou les effacer.
  import { api, ApiError } from '../../shared/api';
  import { messageOf } from '../components/Errors.js';
  let email = $state('');
  let found = $state(null);
  let error = $state('');
  let status = $state('');
  let needConfirm = $state(false);

  async function search(event) {
    event.preventDefault();
    error = status = '';
    needConfirm = false;
    try { found = (await api.privacySearch({ email })).bookings; } catch (e) { error = messageOf(e); found = null; }
  }

  async function exportData() {
    try {
      const data = await api.privacyExport({ email });
      const blob = new Blob([JSON.stringify(data, null, 2)], { type: 'application/json' });
      const a = document.createElement('a');
      a.href = URL.createObjectURL(blob);
      a.download = 'donnees-client.json';
      a.click();
      URL.revokeObjectURL(a.href);
    } catch (e) { error = messageOf(e); }
  }

  async function erase(confirmWithoutEmail) {
    if (!confirmWithoutEmail && !confirm('Effacer définitivement toutes les réservations de cette adresse ? Les rendez-vous à venir seront annulés et le client prévenu.')) return;
    error = status = '';
    try {
      const r = await api.privacyErase({ email, confirmWithoutEmail });
      status = `${r.erased} réservation(s) effacée(s). Client prévenu : ${r.notified}.`;
      found = null;
      needConfirm = false;
    } catch (e) {
      if (e instanceof ApiError && e.status === 409) { needConfirm = true; }
      error = messageOf(e);
    }
  }
</script>

<h1>Données des clients</h1>
<p class="hint">Pour répondre à une demande d’accès ou d’effacement (RGPD), sous un mois.</p>
<form onsubmit={search} novalidate class="panel">
  <label>Adresse e-mail du client<input type="email" bind:value={email} required /></label>
  <div class="actions"><button type="submit">Rechercher</button></div>
</form>
{#if error}<p class="alert" role="alert">{error}</p>{/if}
{#if status}<p class="note" role="status">{status}</p>{/if}
{#if found}
  {#if found.length === 0}
    <p class="note">Aucune réservation pour cette adresse.</p>
  {:else}
    <table class="table">
      <tbody>{#each found as b}<tr><td>{b.startsAt}</td><td>{b.barberName}</td><td>{b.clientName}</td><td>{b.state === 'confirmed' ? 'Confirmée' : 'Annulée'}</td></tr>{/each}</tbody>
    </table>
    <div class="actions">
      <button class="ghost" onclick={exportData}>Exporter (JSON)</button>
      <button class="danger ghost" onclick={() => erase(false)}>Effacer ces données</button>
      {#if needConfirm}<button class="danger" onclick={() => erase(true)}>J’ai prévenu le client par téléphone : effacer</button>{/if}
    </div>
  {/if}
{/if}
