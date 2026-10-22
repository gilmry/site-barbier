<script>
  import { api, ApiError } from '../../shared/api';
  let { onchange } = $props();
  let email = $state('');
  let password = $state('');
  let error = $state('');
  let busy = $state(false);

  async function submit(event) {
    event.preventDefault();
    busy = true;
    error = '';
    try {
      await api.login({ email, password });
      onchange();
    } catch (e) {
      error = e instanceof ApiError ? e.problem.title ?? 'Connexion impossible.' : 'Connexion impossible.';
    } finally {
      busy = false;
    }
  }
</script>

<form class="card" onsubmit={submit} novalidate>
  <h1>Administration</h1>
  <p class="hint">Connexion du gérant.</p>
  {#if error}<p class="alert" role="alert">{error}</p>{/if}
  <label for="email">Adresse e-mail</label>
  <input id="email" type="email" autocomplete="username" bind:value={email} required />
  <label for="password">Mot de passe</label>
  <input id="password" type="password" autocomplete="current-password" bind:value={password} required />
  <div class="actions"><button type="submit" disabled={busy}>Continuer</button></div>
</form>
