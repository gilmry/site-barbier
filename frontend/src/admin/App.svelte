<script>
  // Aiguillage par étape de connexion (FR-040 à FR-043) : la session vit côté serveur, l'îlot l'affiche.
  import { api, setCsrf } from '../shared/api';
  import Login from './views/Login.svelte';
  import TotpStep from './views/TotpStep.svelte';
  import Enrol from './views/Enrol.svelte';
  import Codes from './views/Codes.svelte';
  import Shell from './Shell.svelte';

  let session = $state(null);
  let freshCodes = $state(null);
  let loadError = $state('');

  async function refresh() {
    try {
      session = await api.getAdminSession();
      setCsrf(session.csrf);
      loadError = '';
    } catch (e) {
      loadError = "Le serveur ne répond pas. Réessayez dans un instant.";
    }
  }
  refresh();

  function onCodes(codes) {
    freshCodes = codes;
    refresh();
  }
</script>

{#if loadError}
  <div class="card"><p class="alert" role="alert">{loadError}</p><button onclick={refresh}>Réessayer</button></div>
{:else if !session}
  <div class="card"><p>Chargement…</p></div>
{:else if freshCodes}
  <Codes codes={freshCodes} ondone={() => (freshCodes = null)} />
{:else if session.stage === 'none'}
  <Login onchange={refresh} />
{:else if session.stage === 'password_ok'}
  <TotpStep onchange={refresh} />
{:else if session.stage === 'pre_enrolment' || session.stage === 'recovery'}
  <Enrol recovery={session.stage === 'recovery'} oncodes={onCodes} />
{:else}
  <Shell {session} onchange={refresh} oncodes={onCodes} />
{/if}
