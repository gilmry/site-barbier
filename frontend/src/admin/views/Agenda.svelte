<script>
  // Agenda (FR-020, FR-021) : disponibilités hebdomadaires par barbier ; absences ; fermetures du salon.
  import { api } from '../../shared/api';
  import { messageOf } from '../components/Errors.js';

  const names = ['Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi', 'Dimanche'];
  let barbers = $state([]);
  let barberId = $state(null);
  let ranges = $state([]);
  let custom = $state(false);
  let absences = $state([]);
  let form = $state({ barberId: '', startsAt: '', endsAt: '' });
  let error = $state('');
  let status = $state('');

  async function init() {
    barbers = (await api.listBarbers()).barbers.filter((b) => b.active);
    barberId = barbers[0]?.id ?? null;
    await Promise.all([loadRanges(), loadAbsences()]);
  }
  async function loadRanges() {
    if (!barberId) return;
    ranges = (await api.getAvailability(barberId)).ranges;
    custom = ranges.length > 0;
  }
  async function loadAbsences() { absences = (await api.listAbsences()).absences; }
  init();

  async function saveRanges() {
    error = status = '';
    try {
      ranges = (await api.setAvailability(barberId, { ranges: custom ? ranges : [] })).ranges;
      status = custom ? 'Disponibilités enregistrées.' : 'Ce barbier suit les horaires du salon.';
    } catch (e) { error = messageOf(e); }
  }

  async function addAbsence(event) {
    event.preventDefault();
    error = status = '';
    try {
      await api.addAbsence({ barberId: form.barberId || null, startsAt: form.startsAt, endsAt: form.endsAt });
      status = form.barberId ? 'Absence enregistrée : ses créneaux sont retirés.' : 'Fermeture enregistrée : elle est annoncée sur le site.';
      form = { barberId: '', startsAt: '', endsAt: '' };
      await loadAbsences();
    } catch (e) { error = messageOf(e); }
  }

  async function removeAbsence(id) {
    try { await api.deleteAbsence(id); await loadAbsences(); } catch (e) { error = messageOf(e); }
  }
</script>

<h1>Agenda</h1>
{#if error}<p class="alert" role="alert">{error}</p>{/if}
{#if status}<p class="note" role="status">{status}</p>{/if}

<section class="panel">
  <h2>Disponibilités</h2>
  <label>Barbier
    <select bind:value={barberId} onchange={loadRanges}>{#each barbers as b}<option value={b.id}>{b.firstName}</option>{/each}</select>
  </label>
  <label class="check"><input type="checkbox" bind:checked={custom} /> Horaires propres (sinon : horaires du salon)</label>
  {#if custom}
    <table class="table">
      <tbody>
        {#each ranges as r, i}
          <tr>
            <td><select bind:value={r.weekday} aria-label="Jour">{#each names as n, j}<option value={j + 1}>{n}</option>{/each}</select></td>
            <td><input type="time" step="300" bind:value={r.starts} aria-label="Début" /></td>
            <td><input type="time" step="300" bind:value={r.ends} aria-label="Fin" /></td>
            <td><button class="ghost small" onclick={() => (ranges = ranges.filter((_, k) => k !== i))}>Retirer</button></td>
          </tr>
        {/each}
      </tbody>
    </table>
    <button class="ghost small" onclick={() => (ranges = [...ranges, { weekday: 2, starts: '10:00', ends: '19:00' }])}>Ajouter une plage</button>
  {/if}
  <div class="actions"><button onclick={saveRanges}>Enregistrer les disponibilités</button></div>
</section>

<section class="panel">
  <h2>Absences et fermetures</h2>
  <form onsubmit={addAbsence} novalidate class="grid2">
    <label>Qui ?
      <select bind:value={form.barberId}><option value="">Tout le salon (fermeture)</option>{#each barbers as b}<option value={b.id}>{b.firstName}</option>{/each}</select>
    </label>
    <span></span>
    <label>Du<input type="datetime-local" step="900" bind:value={form.startsAt} required /></label>
    <label>Au<input type="datetime-local" step="900" bind:value={form.endsAt} required /></label>
    <div class="actions"><button type="submit">Enregistrer</button></div>
  </form>
  <table class="table">
    <tbody>
      {#each absences as a}
        <tr>
          <td>{a.barberName ?? 'Salon fermé'}</td><td>{a.startsLocal.replace('T', ' ')} → {a.endsLocal.replace('T', ' ')}</td>
          <td><button class="ghost small" onclick={() => removeAbsence(a.id)}>Retirer</button></td>
        </tr>
      {:else}
        <tr><td class="hint">Aucune absence à venir.</td></tr>
      {/each}
    </tbody>
  </table>
</section>
