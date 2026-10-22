<script>
  // Horaires du salon et coordonnées (FR-010).
  import { api } from '../../shared/api';
  import TranslatedField from '../components/TranslatedField.svelte';
  import { messageOf, fieldsOf } from '../components/Errors.js';

  const names = ['Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi', 'Dimanche'];
  let days = $state([]);
  let salon = $state(null);
  let hoursMsg = $state('');
  let hoursErr = $state('');
  let salonMsg = $state('');
  let salonErr = $state('');
  let errors = $state({});

  api.getOpeningHours().then((r) => (days = r.days.map((d) => ({ ...d, closed: d.opens === null, opens: d.opens ?? '10:00', closes: d.closes ?? '19:00' }))));
  api.getSalon().then((s) => (salon = { ...s, address: { ...s.address }, access: { ...s.access } }));

  async function saveHours(event) {
    event.preventDefault();
    hoursMsg = hoursErr = '';
    try {
      await api.updateOpeningHours({ days: days.map((d) => ({ weekday: d.weekday, opens: d.closed ? null : d.opens, closes: d.closed ? null : d.closes })) });
      hoursMsg = 'Horaires enregistrés.';
    } catch (e) {
      hoursErr = messageOf(e);
    }
  }

  async function saveSalon(event) {
    event.preventDefault();
    salonMsg = salonErr = '';
    errors = {};
    try {
      await api.updateSalon(salon);
      salonMsg = 'Coordonnées enregistrées.';
    } catch (e) {
      salonErr = messageOf(e);
      errors = fieldsOf(e);
    }
  }
</script>

<h1>Horaires et coordonnées</h1>
<form class="panel" onsubmit={saveHours} novalidate>
  <h2>Horaires du salon</h2>
  {#if hoursErr}<p class="alert" role="alert">{hoursErr}</p>{/if}
  {#if hoursMsg}<p class="note" role="status">{hoursMsg}</p>{/if}
  <table class="table">
    <tbody>
      {#each days as d, i}
        <tr>
          <th scope="row">{names[i]}</th>
          <td><label class="check"><input type="checkbox" bind:checked={d.closed} /> Fermé</label></td>
          <td><input type="time" aria-label="{names[i]} ouverture" bind:value={d.opens} disabled={d.closed} step="300" /></td>
          <td><input type="time" aria-label="{names[i]} fermeture" bind:value={d.closes} disabled={d.closed} step="300" /></td>
        </tr>
      {/each}
    </tbody>
  </table>
  <div class="actions"><button type="submit">Enregistrer les horaires</button></div>
</form>

{#if salon}
  <form class="panel" onsubmit={saveSalon} novalidate>
    <h2>Coordonnées</h2>
    {#if salonErr}<p class="alert" role="alert">{salonErr}</p>{/if}
    {#if salonMsg}<p class="note" role="status">{salonMsg}</p>{/if}
    <label>Nom du salon<input bind:value={salon.name} maxlength="80" /></label>
    <label>Téléphone<input type="tel" bind:value={salon.phone} maxlength="30" /></label>
    {#if errors.phone}<p class="err">{errors.phone}</p>{/if}
    <label>E-mail du salon (reçoit les réservations)<input type="email" bind:value={salon.email} maxlength="190" /></label>
    {#if errors.email}<p class="err">{errors.email}</p>{/if}
    <TranslatedField id="s-addr" label="Adresse" max={300} multiline={true} bind:value={salon.address} />
    <TranslatedField id="s-access" label="Accès" max={300} required={false} bind:value={salon.access} />
    <div class="actions"><button type="submit">Enregistrer les coordonnées</button></div>
  </form>
{/if}
