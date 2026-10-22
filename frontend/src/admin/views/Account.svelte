<script>
  // Mon compte : codes de secours (FR-043) et changement de téléphone (réactivation du TOTP).
  import { api, ApiError } from '../../shared/api';
  import Enrol from './Enrol.svelte';
  let { session, onchange, oncodes } = $props();
  let code = $state('');
  let error = $state('');
  let reenrol = $state(false);

  async function confirmIdentity(event) {
    event.preventDefault();
    error = '';
    try {
      await api.verifyTotp({ code: code.replace(/\s/g, '') });
      code = '';
      onchange();
    } catch (e) {
      error = e instanceof ApiError ? e.problem.title ?? 'Code refusé.' : 'Code refusé.';
    }
  }

  async function regenerate() {
    error = '';
    try {
      oncodes((await api.regenerateRecoveryCodes()).codes);
    } catch (e) {
      error = e instanceof ApiError ? e.problem.title ?? 'Action refusée.' : 'Action refusée.';
    }
  }
</script>

<h1>Mon compte</h1>
<p>Connecté en tant que <strong>{session.email}</strong>. Codes de secours restants : {session.recoveryCodesRemaining}.</p>
{#if error}<p class="alert" role="alert">{error}</p>{/if}
{#if !session.recentTotp}
  <form onsubmit={confirmIdentity} novalidate>
    <p class="note">Pour ces actions sensibles, saisissez d’abord un code de votre appli.</p>
    <label for="step-up">Code à 6 chiffres</label>
    <input id="step-up" inputmode="numeric" autocomplete="one-time-code" bind:value={code} />
    <div class="actions"><button type="submit">Confirmer</button></div>
  </form>
{:else if reenrol}
  <Enrol recovery={true} {oncodes} />
{:else}
  <div class="actions">
    <button onclick={regenerate}>Régénérer mes codes de secours</button>
    <button class="ghost" onclick={() => (reenrol = true)}>Changer de téléphone</button>
  </div>
{/if}
