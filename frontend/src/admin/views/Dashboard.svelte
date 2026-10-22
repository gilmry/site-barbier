<script>
  // Tableau de bord (Story 5.2) : le jour en un coup d'œil et les rappels d'exploitation.
  import { api, ApiError } from '../../shared/api';
  import { messageOf } from '../components/Errors.js';
  let { session, onchange } = $props();

  const today = new Intl.DateTimeFormat('en-CA', { timeZone: 'Europe/Brussels' }).format(new Date());
  let health = $state(null);
  let todayCount = $state(null);
  let error = $state('');
  let status = $state('');
  let askCode = $state(null); // action en attente d'un code TOTP récent
  let code = $state('');

  async function load() {
    try {
      health = await api.getAdminHealth();
      todayCount = (await api.listBookings({ date: today, state: 'confirmed' })).bookings.length;
    } catch (e) { error = messageOf(e, 'Tableau de bord indisponible.'); }
  }
  load();

  async function withRecentTotp(action) {
    error = status = '';
    try {
      await action();
    } catch (e) {
      if (e instanceof ApiError && e.problem?.detail === 'totp_required') askCode = action;
      else error = messageOf(e);
    }
  }

  async function confirmCode(event) {
    event.preventDefault();
    try {
      await api.verifyTotp({ code: code.replace(/\s/g, '') });
      code = '';
      const action = askCode;
      askCode = null;
      onchange();
      await withRecentTotp(action);
    } catch (e) { error = messageOf(e, 'Code refusé.'); }
  }

  const backup = () => withRecentTotp(async () => {
    const sql = await api.downloadBackup();
    const a = document.createElement('a');
    a.href = URL.createObjectURL(new Blob([sql], { type: 'application/sql' }));
    a.download = `sauvegarde-${today}.sql`;
    a.click();
    URL.revokeObjectURL(a.href);
    status = 'Sauvegarde téléchargée. Elle contient des données de clients : rangez-la en lieu sûr et supprimez-la après 6 mois.';
    await load();
  });

  const migrate = () => withRecentTotp(async () => {
    await api.applyMigrations();
    status = 'Base de données mise à jour.';
    await load();
  });

  const kb = (n) => (n / 1024 / 1024).toFixed(1).replace('.', ',');
</script>

<h1>Tableau de bord</h1>
{#if error}<p class="alert" role="alert">{error}</p>{/if}
{#if status}<p class="note" role="status">{status}</p>{/if}
{#if askCode}
  <form class="panel" onsubmit={confirmCode} novalidate>
    <p>Action sensible : saisissez un code de votre appli.</p>
    <label for="dash-code">Code à 6 chiffres</label>
    <input id="dash-code" inputmode="numeric" autocomplete="one-time-code" bind:value={code} />
    <div class="actions"><button type="submit">Confirmer</button><button type="button" class="ghost" onclick={() => (askCode = null)}>Annuler</button></div>
  </form>
{/if}

<div class="cards">
  <section class="panel"><h2>Aujourd’hui</h2><p class="big">{todayCount ?? '…'}</p><p class="hint">rendez-vous confirmé(s)</p></section>
  {#if health}
    <section class="panel"><h2>E-mails</h2>
      {#if health.failedMails > 0}<p class="alert">{health.failedMails} envoi(s) en échec — voir « E-mails ».</p>{:else}<p>Tous les e-mails sont partis.</p>{/if}
      {#if !health.smtpConfigured}<p class="alert">Serveur d’envoi non configuré (config.php).</p>{/if}
    </section>
    <section class="panel"><h2>Sauvegarde</h2>
      <p>{health.lastBackup ? `Dernière : ${health.lastBackup}` : 'Jamais téléchargée.'}</p>
      {#if health.backupDue}<p class="alert">Pensez à télécharger une sauvegarde (une par mois).</p>{/if}
      <button onclick={backup}>Télécharger une sauvegarde</button>
    </section>
    <section class="panel"><h2>Site</h2>
      <p>Version {health.version} · PHP {health.php}</p>
      {#if health.phpSupport === 'ended'}<p class="alert">PHP {health.php} n’a plus de correctifs de sécurité : changez de version dans l’espace OVH.</p>
      {:else if health.phpSupport === 'ending'}<p class="alert">Les correctifs de PHP {health.php} s’arrêtent le {health.phpSecurityEnd}.</p>{/if}
      <p class="hint">Dernière purge : {health.lastPurge ?? '—'} · Images : {kb(health.uploadsBytes)} Mo</p>
      {#if !health.logWritable}<p class="alert">Le journal (app/var/log) n’est pas inscriptible.</p>{/if}
      {#if health.pendingMigrations.length > 0}
        <p class="alert">Mise à jour de la base en attente ({health.pendingMigrations.join(', ')}).</p>
        <button onclick={migrate}>Mettre à jour la base</button>
      {/if}
    </section>
  {/if}
</div>
{#if session.recoveryCodesRemaining !== undefined && session.recoveryCodesRemaining < 3}
  <p class="alert" role="alert">Il ne vous reste que {session.recoveryCodesRemaining} code(s) de secours : régénérez-les dans « Mon compte ».</p>
{/if}
