<script>
  // L'équipe (FR-009) : prénom, spécialité, bio, photo, prestations réalisées, actif/inactif.
  import { api } from '../../shared/api';
  import TranslatedField from '../components/TranslatedField.svelte';
  import MediaPicker from '../components/MediaPicker.svelte';
  import { messageOf, fieldsOf } from '../components/Errors.js';

  let barbers = $state([]);
  let services = $state([]);
  let editing = $state(null);
  let error = $state('');
  let errors = $state({});

  async function load() {
    barbers = (await api.listBarbers()).barbers;
    services = (await api.getCatalog()).categories.flatMap((c) => c.services);
  }
  load();

  function edit(b) {
    error = '';
    errors = {};
    editing = b
      ? { id: b.id, firstName: b.firstName, specialty: { ...b.specialty }, bio: { ...b.bio }, photoMediaId: b.photoMediaId, active: b.active, serviceIds: [...b.serviceIds] }
      : { id: null, firstName: '', specialty: {}, bio: {}, photoMediaId: null, active: true, serviceIds: [] };
  }

  async function save(event) {
    event.preventDefault();
    error = '';
    errors = {};
    const { id, ...input } = editing;
    try {
      if (id) await api.updateBarber(id, input);
      else await api.createBarber(input);
      editing = null;
      await load();
    } catch (e) {
      error = messageOf(e);
      errors = fieldsOf(e);
    }
  }

  function toggle(sid) {
    editing.serviceIds = editing.serviceIds.includes(sid) ? editing.serviceIds.filter((x) => x !== sid) : [...editing.serviceIds, sid];
  }
</script>

<h1>L’équipe</h1>
{#if editing}
  <form class="panel" onsubmit={save} novalidate>
    <h2>{editing.id ? `Modifier ${editing.firstName}` : 'Nouveau barbier'}</h2>
    {#if error}<p class="alert" role="alert">{error}</p>{/if}
    <label>Prénom<input bind:value={editing.firstName} maxlength="60" required /></label>
    {#if errors.firstName}<p class="err">{errors.firstName}</p>{/if}
    <TranslatedField id="b-spec" label="Spécialité" max={60} bind:value={editing.specialty} error={errors['specialty.fr'] ?? ''} />
    <TranslatedField id="b-bio" label="Présentation" max={400} multiline={true} required={false} bind:value={editing.bio} />
    <MediaPicker bind:value={editing.photoMediaId} label="Photo (sinon, l’initiale du prénom)" />
    <fieldset class="services"><legend>Prestations réalisées</legend>
      {#each services as s}<label class="check"><input type="checkbox" checked={editing.serviceIds.includes(s.id)} onchange={() => toggle(s.id)} /> {s.name.fr}</label>{/each}
    </fieldset>
    <label class="check"><input type="checkbox" bind:checked={editing.active} /> Actif (présenté sur le site et réservable)</label>
    <div class="actions"><button type="submit">Enregistrer</button><button type="button" class="ghost" onclick={() => (editing = null)}>Annuler</button></div>
  </form>
{:else}
  {#if error}<p class="alert" role="alert">{error}</p>{/if}
  <div class="cards">
    {#each barbers as b}
      <article class="panel">
        <h2>{b.firstName}</h2>
        <p class="hint">{b.specialty.fr} · {b.active ? 'Actif' : 'Inactif'} · {b.serviceIds.length} prestation(s)</p>
        <button class="ghost small" onclick={() => edit(b)}>Modifier</button>
      </article>
    {/each}
  </div>
  <button class="ghost" onclick={() => edit(null)}>Ajouter un barbier</button>
{/if}
