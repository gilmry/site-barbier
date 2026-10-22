<script>
  import { api, ApiError } from '../../shared/api';
  let { onchange } = $props();
  let code = $state('');
  let error = $state('');
  let busy = $state(false);
  let useRecovery = $state(false);

  async function submit(event) {
    event.preventDefault();
    busy = true;
    error = '';
    try {
      if (useRecovery) await api.useRecoveryCode({ code });
      else await api.verifyTotp({ code: code.replace(/\s/g, '') });
      onchange();
    } catch (e) {
      error = e instanceof ApiError ? e.problem.title ?? 'Code refusé.' : 'Code refusé.';
      code = '';
    } finally {
      busy = false;
    }
  }

  async function cancel() {
    await api.logout().catch(() => {});
    onchange();
  }
</script>

<form class="card" onsubmit={submit} novalidate>
  <h1>{useRecovery ? 'Code de secours' : 'Code de vérification'}</h1>
  <p class="hint">
    {useRecovery ? 'Saisissez un de vos codes de secours (il ne servira qu’une fois).' : 'Saisissez le code à 6 chiffres affiché par votre appli d’authentification.'}
  </p>
  {#if error}<p class="alert" role="alert">{error}</p>{/if}
  <label for="code">{useRecovery ? 'Code de secours' : 'Code à 6 chiffres'}</label>
  <input id="code" inputmode={useRecovery ? 'text' : 'numeric'} autocomplete="one-time-code" bind:value={code} required />
  <div class="actions">
    <button type="submit" disabled={busy}>Valider</button>
    <button type="button" class="ghost" onclick={() => { useRecovery = !useRecovery; code = ''; error = ''; }}>
      {useRecovery ? 'Utiliser mon appli' : 'J’ai perdu mon téléphone'}
    </button>
    <button type="button" class="ghost" onclick={cancel}>Annuler</button>
  </div>
</form>
