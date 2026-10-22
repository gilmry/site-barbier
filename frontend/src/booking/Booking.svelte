<script>
  // Parcours de réservation (FR-022 à FR-024, PRD §8) : prestation → barbier → jour → heure → coordonnées → confirmé.
  // Toutes les données passent par le client généré (aucune URL écrite ici, invariant F1).
  import { api, ApiError } from '../shared/api';
  import Calendar from './Calendar.svelte';

  let { lang, phone, strings } = $props();
  const t = (key, params = {}) => Object.entries(params).reduce((s, [k, v]) => s.replaceAll(`:${k}`, v), strings[`booking.${key}`] ?? key);

  let step = $state('service');
  let services = $state([]);
  let barbers = $state([]);
  let slots = $state([]);
  let service = $state(null);
  let barber = $state(null); // { id, firstName } ou { id: 'any' }
  let day = $state(null);
  let slot = $state(null);
  let contact = $state({ name: '', phone: '', email: '', website: '' });
  let errors = $state({});
  let message = $state('');
  let busy = $state(false);
  let done = $state(null);
  let headingEl = $state(null);

  const steps = ['service', 'barber', 'day', 'time', 'contact'];
  const index = $derived(steps.indexOf(step));

  function go(next) {
    step = next;
    message = '';
    queueMicrotask(() => headingEl?.focus());
  }

  async function load(fn) {
    busy = true;
    message = '';
    try {
      return await fn();
    } catch (e) {
      message = e instanceof ApiError && e.status === 409 ? t('taken') : t('error', { phone });
      return null;
    } finally {
      busy = false;
    }
  }

  load(async () => (services = (await api.listBookableServices({ lang })).services));

  async function chooseService(s) {
    service = s;
    barber = null;
    day = null;
    slot = null;
    const r = await load(() => api.listBookableBarbers({ service: s.id, lang }));
    if (r) {
      barbers = r.barbers;
      go('barber');
    }
  }

  function chooseBarber(b) {
    barber = b;
    day = null;
    slot = null;
    go('day');
  }

  async function chooseDay(d) {
    day = d;
    slot = null;
    const r = await load(() => api.listFreeSlots({ service: service.id, barber: barber.id, date: d }));
    if (r) {
      slots = r.slots;
      go('time');
    }
  }

  function chooseSlot(s) {
    slot = s;
    go('contact');
  }

  async function submit(event) {
    event.preventDefault();
    errors = {};
    busy = true;
    message = '';
    try {
      done = await api.bookSlot({ serviceId: service.id, barberId: barber.id, start: slot.start, lang, ...contact });
      go('done');
    } catch (e) {
      if (e instanceof ApiError && e.status === 409) {
        message = t('taken');
        const r = await api.listFreeSlots({ service: service.id, barber: barber.id, date: day }).catch(() => null);
        if (r) slots = r.slots;
        step = 'time';
      } else if (e instanceof ApiError && (e.status === 422 || e.status === 400)) {
        errors = e.fieldErrors();
        message = e.problem.title ?? t('error', { phone });
      } else {
        message = e instanceof ApiError && e.status === 429 ? e.problem.title : t('error', { phone });
      }
    } finally {
      busy = false;
    }
  }

  function restart() {
    service = barber = day = slot = done = null;
    contact = { name: '', phone: '', email: '', website: '' };
    go('service');
  }

  const dayLabel = (d) => new Intl.DateTimeFormat({ fr: 'fr-BE', nl: 'nl-BE', en: 'en-GB' }[lang], { weekday: 'long', day: 'numeric', month: 'long', timeZone: 'UTC' }).format(new Date(`${d}T12:00:00Z`));
</script>

<div class="booking" aria-busy={busy}>
  {#if step !== 'done'}
    <ol class="steps caps" aria-label="Étapes">
      {#each steps as s, i}
        <li class:current={s === step} class:past={i < index}>
          {#if i < index}<button type="button" onclick={() => go(s)}>{t(`step.${s}`)}</button>{:else}<span>{t(`step.${s}`)}</span>{/if}
        </li>
      {/each}
    </ol>
  {/if}

  {#if message}<p class="message" role="alert">{message}</p>{/if}

  {#if step === 'service'}
    <h2 tabindex="-1" bind:this={headingEl}>{t('choose.service')}</h2>
    {#if services.length === 0 && busy}<p class="hint">{t('loading')}</p>{/if}
    <ul class="choices">
      {#each services as s}
        <li><button type="button" class="choice" onclick={() => chooseService(s)} aria-pressed={service?.id === s.id}>
          <span class="name">{s.name}</span><span class="meta">{s.price} · {s.durationMin} {t('minutes')}</span>
          {#if s.description}<span class="desc">{s.description}</span>{/if}
        </button></li>
      {/each}
    </ul>
  {:else if step === 'barber'}
    <h2 tabindex="-1" bind:this={headingEl}>{t('choose.barber')}</h2>
    <ul class="choices">
      <li><button type="button" class="choice" onclick={() => chooseBarber({ id: 'any' })}>
        <span class="name">{t('noPreference')}</span><span class="desc">{t('noPreference.hint')}</span></button></li>
      {#each barbers as b}
        <li><button type="button" class="choice" onclick={() => chooseBarber(b)}>
          <span class="name">{b.firstName}</span><span class="desc">{b.specialty}</span></button></li>
      {/each}
    </ul>
  {:else if step === 'day'}
    <h2 tabindex="-1" bind:this={headingEl}>{t('choose.day')}</h2>
    <Calendar {lang} {t} serviceId={service.id} barberId={barber.id} selected={day} onpick={chooseDay} />
  {:else if step === 'time'}
    <h2 tabindex="-1" bind:this={headingEl}>{t('choose.time')} <small>{dayLabel(day)}</small></h2>
    {#if slots.length === 0}
      <p class="hint">{t('noSlots')}</p>
    {:else}
      <ul class="times">
        {#each slots as s}
          <li><button type="button" class="time" onclick={() => chooseSlot(s)}>{s.time}</button></li>
        {/each}
      </ul>
    {/if}
  {:else if step === 'contact'}
    <h2 tabindex="-1" bind:this={headingEl}>{t('contact')}</h2>
    <p class="recap">{service.name} · {barber.id === 'any' ? t('noPreference') : barber.firstName} · {dayLabel(day)} · {slot.time} · {service.price}</p>
    <form onsubmit={submit} novalidate>
      <label for="bk-name">{t('name')}</label>
      <input id="bk-name" autocomplete="name" bind:value={contact.name} required maxlength="80" aria-invalid={!!errors.name} />
      {#if errors.name}<p class="err">{errors.name}</p>{/if}
      <label for="bk-phone">{t('phone')}</label>
      <input id="bk-phone" type="tel" autocomplete="tel" bind:value={contact.phone} required maxlength="30" aria-invalid={!!errors.phone} />
      {#if errors.phone}<p class="err">{errors.phone}</p>{/if}
      <label for="bk-email">{t('email')}</label>
      <input id="bk-email" type="email" autocomplete="email" bind:value={contact.email} required maxlength="190" aria-invalid={!!errors.email} />
      {#if errors.email}<p class="err">{errors.email}</p>{/if}
      <div class="trap" aria-hidden="true"><label for="bk-website">{t('website')}</label><input id="bk-website" tabindex="-1" autocomplete="off" bind:value={contact.website} /></div>
      <p class="hint">{t('privacy')} <a href={strings['booking.privacyUrl']}>→</a></p>
      <button class="btn" type="submit" disabled={busy}>{t('submit')}</button>
    </form>
  {:else if step === 'done'}
    <div class="done" role="status">
      <h2 tabindex="-1" bind:this={headingEl}>{t('done.title')}</h2>
      <p class="recap">{done.service} · {done.barber} · {dayLabel(done.date)} · {done.time} · {done.price}</p>
      <p>{t('done.text')}</p>
      <button type="button" class="btn ghost" onclick={restart}>{t('done.another')}</button>
    </div>
  {/if}

  {#if index > 0 && step !== 'done'}
    <button type="button" class="back" onclick={() => go(steps[index - 1])}>← {t('back')}</button>
  {/if}
</div>
