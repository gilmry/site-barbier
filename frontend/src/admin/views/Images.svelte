<script>
  // Images (FR-007) : envoi (JPEG, PNG, WebP ; 8 Mo au plus), texte alternatif traduit, suppression si inutilisée.
  import { api } from '../../shared/api';
  import TranslatedField from '../components/TranslatedField.svelte';
  import { messageOf } from '../components/Errors.js';

  let media = $state([]);
  let error = $state('');
  let status = $state('');
  let busy = $state(false);
  let editing = $state(null);

  async function load() { media = (await api.listMedia()).media; }
  load();

  async function upload(event) {
    const file = event.currentTarget.files?.[0];
    if (!file) return;
    error = status = '';
    busy = true;
    const form = new FormData();
    form.append('file', file);
    try {
      const m = await api.uploadMedia(form);
      status = m.warning ?? 'Image enregistrée.';
      await load();
      editing = { id: m.id, alt: {} };
    } catch (e) {
      error = messageOf(e, 'Envoi refusé.');
    } finally {
      busy = false;
      event.currentTarget.value = '';
    }
  }

  async function saveAlt(event) {
    event.preventDefault();
    try { await api.updateMedia(editing.id, { alt: editing.alt }); editing = null; await load(); } catch (e) { error = messageOf(e); }
  }

  async function remove(id) {
    try { await api.deleteMedia(id); await load(); } catch (e) { error = messageOf(e); }
  }
</script>

<h1>Images</h1>
{#if error}<p class="alert" role="alert">{error}</p>{/if}
{#if status}<p class="note" role="status">{status}</p>{/if}
<label class="upload">Envoyer une image (JPEG, PNG ou WebP, 8 Mo au plus)
  <input type="file" accept="image/jpeg,image/png,image/webp" onchange={upload} disabled={busy} />
</label>
{#if editing}
  <form class="panel" onsubmit={saveAlt} novalidate>
    <TranslatedField id="alt" label="Description de l’image (lue par les lecteurs d’écran)" max={200} required={false} bind:value={editing.alt} />
    <div class="actions"><button type="submit">Enregistrer</button><button type="button" class="ghost" onclick={() => (editing = null)}>Plus tard</button></div>
  </form>
{/if}
<div class="gallery">
  {#each media as m}
    <figure class="panel">
      <img src={m.src} alt={m.alt?.fr ?? ''} width="240" />
      <figcaption>{m.alt?.fr || 'Sans description'} · {m.width}×{m.height}</figcaption>
      <div class="actions">
        <button class="ghost small" onclick={() => (editing = { id: m.id, alt: { ...m.alt } })}>Description</button>
        <button class="ghost small danger" onclick={() => remove(m.id)}>Supprimer</button>
      </div>
    </figure>
  {/each}
</div>
