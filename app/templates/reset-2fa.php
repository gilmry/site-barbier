<?php /** @var callable $e @var array<string,string> $errors @var string $email */ ?>
<!doctype html>
<html lang="fr">
<head>
  <meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="robots" content="noindex">
  <title>Réinitialisation du double facteur</title>
  <link rel="stylesheet" href="/assets/admin-pages.css">
</head>
<body>
<main class="card">
  <h1>Réinitialisation du double facteur</h1>
  <p class="hint">Téléphone perdu et plus aucun code de secours : le fichier jeton déposé sur le serveur prouve que vous
    contrôlez l'hébergement. Le salon sera prévenu par e-mail.</p>
  <form method="post" action="/install/reset-2fa" novalidate>
    <?php if (isset($errors['_'])): ?><p class="alert" role="alert"><?= $e($errors['_']) ?></p><?php endif; ?>
    <label for="email">Adresse e-mail du gérant</label>
    <input id="email" name="email" type="email" autocomplete="username" required value="<?= $e($email) ?>">
    <label for="password">Mot de passe</label>
    <input id="password" name="password" type="password" autocomplete="current-password" required>
    <label for="token">Contenu du fichier app/var/reset-2fa.token</label>
    <input id="token" name="token" type="password" autocomplete="off" required>
    <button type="submit">Réinitialiser et réactiver</button>
  </form>
</main>
</body>
</html>
