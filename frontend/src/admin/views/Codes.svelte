<script>
  let { codes, ondone } = $props();
  let kept = $state(false);
</script>

<section class="card">
  <h1>Vos codes de secours</h1>
  <p class="note">
    Notez ou imprimez ces 10 codes et rangez-les à l’abri. Chacun permet <strong>une</strong> connexion si vous perdez
    votre téléphone. Ils ne seront <strong>plus jamais affichés</strong>.
  </p>
  <ol class="codes" data-testid="recovery-codes">
    {#each codes as c}<li>{c}</li>{/each}
  </ol>
  <label><input type="checkbox" bind:checked={kept} style="width:auto" /> J’ai mis ces codes à l’abri.</label>
  <div class="actions"><button disabled={!kept} onclick={ondone}>Continuer</button></div>
</section>
