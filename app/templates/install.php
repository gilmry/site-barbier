<?php /** @var callable $e @var list<array{label:string, ok:bool, detail:string, blocking:bool}> $checks @var bool $blocked @var array<string,string> $errors @var string $email */ ?>
<!doctype html>
<html lang="fr">
<head>
  <meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="robots" content="noindex">
  <title>Installation du site</title>
  <link rel="stylesheet" href="/assets/admin-pages.css">
</head>
<body>
<main class="card">
  <h1>Installation du site</h1>
  <h2>1. Vérification de l'hébergement</h2>
  <ul class="checks">
  <?php foreach ($checks as $c): ?>
    <li class="<?= $e($c['ok'] ? 'ok' : ($c['blocking'] ? 'ko' : 'warn')) ?>"><strong><?= $e($c['label']) ?></strong> — <?= $e($c['detail']) ?></li>
  <?php endforeach; ?>
  </ul>
  <?php if ($blocked): ?>
    <p class="alert">Corrigez les points en rouge, puis rechargez cette page.</p>
  <?php else: ?>
  <h2>2. Compte du gérant</h2>
  <form method="post" action="/install" novalidate>
    <?php if (isset($errors['_'])): ?><p class="alert" role="alert"><?= $e($errors['_']) ?></p><?php endif; ?>
    <label for="email">Adresse e-mail</label>
    <input id="email" name="email" type="email" autocomplete="username" required value="<?= $e($email) ?>">
    <?php if (isset($errors['email'])): ?><p class="err"><?= $e($errors['email']) ?></p><?php endif; ?>
    <label for="password">Mot de passe (12 caractères au moins)</label>
    <input id="password" name="password" type="password" autocomplete="new-password" required>
    <?php if (isset($errors['password'])): ?><p class="err"><?= $e($errors['password']) ?></p><?php endif; ?>
    <label for="password_again">Mot de passe, à nouveau</label>
    <input id="password_again" name="password_again" type="password" autocomplete="new-password" required>
    <?php if (isset($errors['password_again'])): ?><p class="err"><?= $e($errors['password_again']) ?></p><?php endif; ?>
    <label for="install_code">Code d'installation (dans app/config.php)</label>
    <input id="install_code" name="install_code" type="password" autocomplete="off" required>
    <?php if (isset($errors['install_code'])): ?><p class="err"><?= $e($errors['install_code']) ?></p><?php endif; ?>
    <button type="submit">Installer et créer le compte</button>
    <p class="hint">Étape suivante : activer le double facteur avec une appli d'authentification.</p>
  </form>
  <?php endif; ?>
</main>
</body>
</html>
