<script>
  let { session } = $props();
</script>

<h1>Tableau de bord</h1>
<p>Bienvenue. Le contenu du site, la carte, l’équipe, les horaires et les réservations se gèrent depuis les rubriques.</p>
{#if session.recoveryCodesRemaining !== undefined && session.recoveryCodesRemaining < 3}
  <p class="alert" role="alert">
    Il ne vous reste que {session.recoveryCodesRemaining} code(s) de secours : régénérez-les dans « Mon compte ».
  </p>
{/if}
