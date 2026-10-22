<script>
  // Champ traduit FR/NL/EN (FR obligatoire) : un onglet par langue, pastille « à traduire » si vide.
  let { label, value = $bindable({}), max = 2000, multiline = false, required = true, error = '', id } = $props();
  const langs = [['fr', 'FR'], ['nl', 'NL'], ['en', 'EN']];
  let current = $state('fr');
  const missing = $derived(langs.filter(([code]) => !(value?.[code] ?? '').trim()).map(([code]) => code));
</script>

<fieldset class="tf">
  <legend>{label}{#if required}<span aria-hidden="true"> *</span>{/if}</legend>
  <div class="tabs" role="tablist" aria-label="Langue de « {label} »">
    {#each langs as [code, name]}
      <button type="button" role="tab" aria-selected={current === code} onclick={() => (current = code)} class:missing={missing.includes(code)}>
        {name}{#if missing.includes(code)}<span class="badge">à traduire</span>{/if}
      </button>
    {/each}
  </div>
  {#each langs as [code]}
    {#if current === code}
      {#if multiline}
        <textarea id="{id}-{code}" aria-label="{label} ({code.toUpperCase()})" maxlength={max} rows="6"
          value={value?.[code] ?? ''} oninput={(e) => (value = { ...value, [code]: e.currentTarget.value })}></textarea>
      {:else}
        <input id="{id}-{code}" aria-label="{label} ({code.toUpperCase()})" maxlength={max}
          value={value?.[code] ?? ''} oninput={(e) => (value = { ...value, [code]: e.currentTarget.value })} />
      {/if}
    {/if}
  {/each}
  {#if error}<p class="err" role="alert">{error}</p>{/if}
</fieldset>

<style>
  .tf { border: 0; padding: 0; margin: 16px 0 0; }
  legend { color: var(--argent); padding: 0; margin-bottom: 6px; }
  .tabs { display: flex; gap: 4px; margin-bottom: -1px; }
  .tabs button { background: var(--charbon); color: var(--argent-clair); border: 1px solid var(--fer); border-bottom: 0; padding: 6px 12px; font-weight: 500; }
  .tabs button[aria-selected='true'] { background: var(--noir); color: var(--os); }
  .badge { margin-left: 6px; font-size: 11px; color: var(--warn); }
</style>
