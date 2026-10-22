<script>
  // Planning du jour (FR-026) : une colonne par barbier ; annulation par le salon avec message au client.
  import { api } from '../../shared/api';
  import { messageOf } from '../components/Errors.js';

  const todayIso = new Intl.DateTimeFormat('en-CA', { timeZone: 'Europe/Brussels' }).format(new Date());
  let date = $state(todayIso);
  let bookings = $state([]);
  let barbers = $state([]);
  let showCancelled = $state(false);
  let cancelling = $state(null);
  let note = $state('');
  let error = $state('');
  let status = $state('');

  async function load() {
    error = '';
    try {
      bookings = (await api.listBookings({ date })).bookings;
      if (barbers.length === 0) barbers = (await api.listBarbers()).barbers.filter((b) => b.active);
    } catch (e) { error = messageOf(e, 'Planning indisponible.'); }
  }
  $effect(() => { date; load(); });

  const visible = $derived(bookings.filter((b) => showCancelled || b.state === 'confirmed'));
  const columns = $derived(barbers.map((b) => ({ ...b, items: visible.filter((x) => x.barberId === b.id) })).filter((c) => c.items.length > 0 || barbers.length <= 4));
  const shift = (n) => { const d = new Date(`${date}T12:00:00Z`); d.setUTCDate(d.getUTCDate() + n); date = d.toISOString().slice(0, 10); };

  async function confirmCancel() {
    try {
      await api.cancelBookingBySalon(cancelling.id, note ? { message: note } : {});
      status = `Réservation de ${cancelling.clientName} annulée : le client est prévenu par e-mail.`;
      cancelling = null;
      note = '';
      await load();
    } catch (e) { error = messageOf(e); }
  }
</script>

<h1>Planning</h1>
<div class="actions">
  <button class="ghost small" onclick={() => shift(-1)} aria-label="Jour précédent">‹</button>
  <input type="date" bind:value={date} aria-label="Jour" style="width:auto" />
  <button class="ghost small" onclick={() => shift(1)} aria-label="Jour suivant">›</button>
  <button class="ghost small" onclick={() => (date = todayIso)}>Aujourd’hui</button>
  <label class="check"><input type="checkbox" bind:checked={showCancelled} /> Afficher les annulées</label>
</div>
{#if error}<p class="alert" role="alert">{error}</p>{/if}
{#if status}<p class="note" role="status">{status}</p>{/if}
{#if cancelling}
  <div class="panel" role="dialog" aria-label="Annuler la réservation">
    <h2>Annuler {cancelling.time} — {cancelling.clientName}</h2>
    <label>Message au client (facultatif)<textarea bind:value={note} maxlength="500" rows="3"></textarea></label>
    <div class="actions"><button class="danger" onclick={confirmCancel}>Annuler la réservation</button><button class="ghost" onclick={() => (cancelling = null)}>Garder</button></div>
  </div>
{/if}
{#if visible.length === 0}
  <p class="note">Aucune réservation ce jour-là.</p>
{:else}
  <div class="planning">
    {#each columns as col}
      <section class="col">
        <h2>{col.firstName}</h2>
        {#each col.items as b}
          <article class="slot" class:cancelled={b.state === 'cancelled'}>
            <strong>{b.time}–{b.endTime}</strong> {b.serviceName}
            <div>{b.clientName} · <a href="tel:{b.clientPhone}">{b.clientPhone}</a></div>
            <div class="hint">{b.clientEmail} · {b.lang.toUpperCase()}{b.state === 'cancelled' ? ` · annulée (${b.cancelledBy === 'client' ? 'client' : 'salon'})` : ''}</div>
            {#if b.state === 'confirmed'}<button class="ghost small" onclick={() => (cancelling = b)}>Annuler</button>{/if}
          </article>
        {/each}
      </section>
    {/each}
  </div>
{/if}
