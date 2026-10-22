<script>
  // La carte (FR-008) : catégories et prestations, prix en euros (stockés en centimes), durée par 5 min.
  import { api } from '../../shared/api';
  import TranslatedField from '../components/TranslatedField.svelte';
  import { messageOf, fieldsOf } from '../components/Errors.js';

  let categories = $state([]);
  let editing = $state(null); // { id|null, categoryId, name, description, price, durationMin, published, position }
  let newCategory = $state(null);
  let error = $state('');
  let errors = $state({});
  let status = $state('');

  async function load() {
    categories = (await api.getCatalog()).categories;
  }
  load();

  const euros = (cents) => (cents / 100).toFixed(cents % 100 ? 2 : 0).replace('.', ',');

  function edit(service, category) {
    error = status = '';
    errors = {};
    editing = service
      ? { id: service.id, categoryId: service.categoryId, name: { ...service.name }, description: { ...service.description }, price: euros(service.priceCents), durationMin: service.durationMin, published: service.published, position: service.position }
      : { id: null, categoryId: category.id, name: {}, description: {}, price: '', durationMin: 30, published: true, position: undefined };
  }

  async function saveService(event) {
    event.preventDefault();
    error = '';
    errors = {};
    const priceCents = Math.round(Number(String(editing.price).replace(',', '.')) * 100);
    if (!Number.isFinite(priceCents)) { errors = { price_cents: 'Prix invalide.' }; return; }
    const input = { categoryId: editing.categoryId, name: editing.name, description: editing.description, priceCents, durationMin: Number(editing.durationMin), published: editing.published };
    if (editing.position !== undefined) input.position = editing.position;
    try {
      if (editing.id) await api.updateService(editing.id, input);
      else await api.createService(input);
      editing = null;
      status = 'Carte enregistrée.';
      await load();
    } catch (e) {
      error = messageOf(e);
      errors = fieldsOf(e);
    }
  }

  async function removeService(id) {
    if (!confirm('Supprimer définitivement cette prestation ? (Pour la masquer seulement, décochez « Publiée ».)')) return;
    try { await api.deleteService(id); editing = null; await load(); } catch (e) { error = messageOf(e); }
  }

  async function saveCategory(event) {
    event.preventDefault();
    try { await api.createCategory({ name: newCategory }); newCategory = null; await load(); } catch (e) { error = messageOf(e); errors = fieldsOf(e); }
  }

  async function removeCategory(id) {
    try { await api.deleteCategory(id); await load(); } catch (e) { error = messageOf(e); }
  }
</script>

<h1>La carte</h1>
{#if error && !editing}<p class="alert" role="alert">{error}</p>{/if}
{#if status}<p class="note" role="status">{status}</p>{/if}

{#if editing}
  <form class="panel" onsubmit={saveService} novalidate>
    <h2>{editing.id ? 'Modifier la prestation' : 'Nouvelle prestation'}</h2>
    {#if error}<p class="alert" role="alert">{error}</p>{/if}
    <TranslatedField id="svc-name" label="Nom" max={80} bind:value={editing.name} error={errors['name.fr'] ?? ''} />
    <TranslatedField id="svc-desc" label="Description" max={300} required={false} bind:value={editing.description} />
    <div class="grid2">
      <label>Prix TTC (€)<input inputmode="decimal" bind:value={editing.price} required /></label>
      <label>Durée (minutes, par 5)<input type="number" min="5" max="240" step="5" bind:value={editing.durationMin} required /></label>
    </div>
    {#if errors.price_cents}<p class="err">{errors.price_cents}</p>{/if}
    {#if errors.duration_min}<p class="err">{errors.duration_min}</p>{/if}
    <label class="check"><input type="checkbox" bind:checked={editing.published} /> Publiée (visible sur la carte et réservable)</label>
    <div class="actions">
      <button type="submit">Enregistrer</button>
      <button type="button" class="ghost" onclick={() => (editing = null)}>Annuler</button>
      {#if editing.id}<button type="button" class="ghost danger" onclick={() => removeService(editing.id)}>Supprimer</button>{/if}
    </div>
  </form>
{:else}
  {#each categories as c}
    <section class="panel">
      <h2>{c.name.fr}</h2>
      <table class="table">
        <thead><tr><th>Prestation</th><th>Prix</th><th>Durée</th><th>État</th><th></th></tr></thead>
        <tbody>
          {#each c.services as s}
            <tr>
              <td>{s.name.fr}</td><td>{euros(s.priceCents)} €</td><td>{s.durationMin} min</td>
              <td>{s.published ? 'Publiée' : 'Masquée'}</td>
              <td><button class="ghost small" onclick={() => edit(s, c)} aria-label="Modifier {s.name.fr}">Modifier</button></td>
            </tr>
          {/each}
        </tbody>
      </table>
      <div class="actions">
        <button class="ghost" onclick={() => edit(null, c)}>Ajouter une prestation</button>
        {#if c.services.length === 0}<button class="ghost danger" onclick={() => removeCategory(c.id)}>Supprimer la catégorie</button>{/if}
      </div>
    </section>
  {/each}
  {#if newCategory}
    <form class="panel" onsubmit={saveCategory} novalidate>
      <TranslatedField id="cat-name" label="Nom de la catégorie" max={60} bind:value={newCategory} />
      <div class="actions"><button type="submit">Créer</button><button type="button" class="ghost" onclick={() => (newCategory = null)}>Annuler</button></div>
    </form>
  {:else}
    <button class="ghost" onclick={() => (newCategory = {})}>Nouvelle catégorie</button>
  {/if}
{/if}
