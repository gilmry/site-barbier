<script>
  // Activation (ou réactivation) du double facteur : QR code + secret, confirmé par un premier code.
  import QRCode from 'qrcode';
  import { api, ApiError } from '../../shared/api';
  let { recovery = false, oncodes } = $props();
  let enrolment = $state(null);
  let qr = $state('');
  let code = $state('');
  let error = $state('');
  let busy = $state(false);

  async function start() {
    error = '';
    try {
      enrolment = await api.startTotpEnrollment();
      qr = await QRCode.toString(enrolment.uri, { type: 'svg', margin: 0, errorCorrectionLevel: 'M' });
    } catch (e) {
      error = e instanceof ApiError ? e.problem.title ?? 'Activation impossible.' : 'Activation impossible.';
    }
  }
  start();

  async function confirm(event) {
    event.preventDefault();
    busy = true;
    error = '';
    try {
      const res = await api.confirmTotpEnrollment({ code: code.replace(/\s/g, '') });
      oncodes(res.codes);
    } catch (e) {
      error = e instanceof ApiError ? e.problem.title ?? 'Code refusé.' : 'Code refusé.';
      code = '';
    } finally {
      busy = false;
    }
  }
</script>

<form class="card" onsubmit={confirm} novalidate>
  <h1>{recovery ? 'Réactiver le double facteur' : 'Activer le double facteur'}</h1>
  <p class="hint">
    Installez une appli d’authentification (Google Authenticator, Aegis, 2FAS…), scannez ce code, puis saisissez
    le code à 6 chiffres qu’elle affiche.{#if recovery} L’ancien téléphone ne fonctionnera plus.{/if}
  </p>
  {#if error}<p class="alert" role="alert">{error}</p>{/if}
  {#if enrolment}
    <!-- SVG produit localement par la bibliothèque qrcode à partir de l'URI otpauth renvoyée par le serveur. -->
    <div class="qr" aria-label="QR code à scanner">{@html qr}</div>
    <p class="hint">Impossible de scanner ? Saisissez ce secret dans l’appli :</p>
    <code class="secret" data-testid="totp-secret">{enrolment.secret}</code>
    <label for="code">Code à 6 chiffres</label>
    <input id="code" inputmode="numeric" autocomplete="one-time-code" bind:value={code} required />
    <div class="actions"><button type="submit" disabled={busy}>Activer</button></div>
  {/if}
</form>
