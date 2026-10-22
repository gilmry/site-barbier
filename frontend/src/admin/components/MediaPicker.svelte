<script>
  // Choix d'une image déjà envoyée (rubrique Images) pour une section ou un barbier.
  import { api } from '../../shared/api';
  let { value = $bindable(null), label = 'Image' } = $props();
  let media = $state([]);
  api.listMedia().then((r) => (media = r.media)).catch(() => {});
</script>

<label>
  {label}
  <select value={value ?? ''} onchange={(e) => (value = e.currentTarget.value || null)}>
    <option value="">Aucune</option>
    {#each media as m}<option value={m.id}>{m.alt?.fr || m.src.split('/').pop()} ({m.width}×{m.height})</option>{/each}
  </select>
</label>
{#if value}
  {@const m = media.find((x) => x.id === value)}
  {#if m}<img class="thumb" src={m.src} alt="" width="160" />{/if}
{/if}

<style>.thumb { margin-top: 8px; max-width: 160px; height: auto; border: 1px solid var(--fer); }</style>
