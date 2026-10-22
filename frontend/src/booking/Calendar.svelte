<script>
  // Calendrier des 60 prochains jours : un mois à la fois, jours sans créneau grisés et inactifs.
  import { api } from '../shared/api';
  let { lang, t, serviceId, barberId, selected, onpick } = $props();

  const today = new Date();
  const iso = (d) => d.toISOString().slice(0, 10);
  const utc = (y, m, d) => new Date(Date.UTC(y, m, d, 12));
  const start = utc(today.getFullYear(), today.getMonth(), today.getDate());
  const end = new Date(start.getTime() + 60 * 86400000);
  let month = $state(utc(start.getUTCFullYear(), start.getUTCMonth(), 1));
  let open = $state(new Set());
  let loading = $state(true);

  async function load() {
    loading = true;
    try {
      const r = await api.listBookableDays({ service: serviceId, barber: barberId, from: iso(start), to: iso(end) });
      open = new Set(r.days);
    } finally {
      loading = false;
    }
  }
  load();

  const fmtMonth = $derived(new Intl.DateTimeFormat({ fr: 'fr-BE', nl: 'nl-BE', en: 'en-GB' }[lang], { month: 'long', year: 'numeric', timeZone: 'UTC' }).format(month));
  const weekdays = $derived(Array.from({ length: 7 }, (_, i) => new Intl.DateTimeFormat({ fr: 'fr-BE', nl: 'nl-BE', en: 'en-GB' }[lang], { weekday: 'short', timeZone: 'UTC' }).format(utc(2026, 10, 2 + i))));
  const cells = $derived.by(() => {
    const y = month.getUTCFullYear();
    const m = month.getUTCMonth();
    const first = (utc(y, m, 1).getUTCDay() + 6) % 7; // lundi = 0
    const count = new Date(Date.UTC(y, m + 1, 0)).getUTCDate();
    return [...Array(first).fill(null), ...Array.from({ length: count }, (_, i) => iso(utc(y, m, i + 1)))];
  });
  const canPrev = $derived(month > utc(start.getUTCFullYear(), start.getUTCMonth(), 1));
  const canNext = $derived(utc(month.getUTCFullYear(), month.getUTCMonth() + 1, 1) <= end);
  const noDays = $derived(!loading && open.size === 0);
  const shift = (n) => (month = utc(month.getUTCFullYear(), month.getUTCMonth() + n, 1));
</script>

{#if noDays}
  <p class="hint">{t('noDays')}</p>
{:else}
  <div class="calendar" aria-busy={loading}>
    <div class="cal-head">
      <button type="button" onclick={() => shift(-1)} disabled={!canPrev} aria-label={t('prev')}>‹</button>
      <strong>{fmtMonth}</strong>
      <button type="button" onclick={() => shift(1)} disabled={!canNext} aria-label={t('next')}>›</button>
    </div>
    <div class="grid" role="grid">
      {#each weekdays as w}<span class="wd" role="columnheader">{w}</span>{/each}
      {#each cells as d}
        {#if d === null}<span></span>
        {:else}
          <button type="button" class="day" class:selected={d === selected} disabled={!open.has(d)} onclick={() => onpick(d)} aria-label={d}>{Number(d.slice(8))}</button>
        {/if}
      {/each}
    </div>
  </div>
{/if}
