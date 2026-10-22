<script>
  // Textes des sections (FR-006) et pages légales (FR-011), avec verrou optimiste (409 si périmé).
  import { api } from '../../shared/api';
  import TranslatedField from '../components/TranslatedField.svelte';
  import MediaPicker from '../components/MediaPicker.svelte';
  import { messageOf, fieldsOf } from '../components/Errors.js';

  let sections = $state([]);
  let currentKey = $state('hero');
  let draft = $state(null);
  let status = $state('');
  let error = $state('');
  let errors = $state({});
  const withImage = ['hero', 'maison'];

  async function load() {
    sections = (await api.listSections()).sections;
    select(currentKey);
  }
  function select(key) {
    currentKey = key;
    const s = sections.find((x) => x.key === key);
    draft = s ? { version: s.version, mediaId: s.mediaId, fields: Object.fromEntries(Object.entries(s.fields).map(([k, f]) => [k, { ...f.values }])) } : null;
    status = error = '';
    errors = {};
  }
  load();

  const section = $derived(sections.find((x) => x.key === currentKey));

  async function save(event) {
    event.preventDefault();
    status = error = '';
    errors = {};
    try {
      const updated = await api.updateSection(currentKey, { version: draft.version, fields: draft.fields, mediaId: withImage.includes(currentKey) ? draft.mediaId : null });
      sections = sections.map((s) => (s.key === currentKey ? updated : s));
      draft.version = updated.version;
      status = 'Enregistré : visible tout de suite sur le site.';
    } catch (e) {
      error = messageOf(e);
      errors = fieldsOf(e);
    }
  }
</script>

<h1>Contenu du site</h1>
<div class="split">
  <ul class="list" aria-label="Sections">
    {#each sections as s}
      {@const todo = Object.values(s.fields).some((f) => f.missing.length > 0)}
      <li><button aria-current={s.key === currentKey ? 'true' : undefined} onclick={() => select(s.key)}>{s.label}{#if todo}<span class="badge">à traduire</span>{/if}</button></li>
    {/each}
  </ul>
  {#if section && draft}
    <form onsubmit={save} novalidate>
      <h2>{section.label}</h2>
      {#if error}<p class="alert" role="alert">{error}</p>{/if}
      {#if status}<p class="note" role="status">{status}</p>{/if}
      {#each Object.entries(section.fields) as [name, f]}
        <TranslatedField id="f-{name}" label={f.label} max={f.max} multiline={f.multiline} bind:value={draft.fields[name]} error={errors[`fields.${name}.fr`] ?? errors[`fields.${name}`] ?? ''} />
        {#if f.rich}<p class="hint">Mise en forme : **gras**, *italique*, [texte du lien](https://…) ; une ligne vide entre deux paragraphes.</p>{/if}
      {/each}
      {#if withImage.includes(currentKey)}<MediaPicker bind:value={draft.mediaId} label="Image de la section" />{/if}
      <div class="actions"><button type="submit">Enregistrer</button></div>
    </form>
  {/if}
</div>
