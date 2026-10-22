<?php /** @var callable $e @var callable $t @var string $lang @var string $kind */ ?>
<!doctype html>
<html lang="<?= $e($lang) ?>">
<head>
  <meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="robots" content="noindex">
  <title><?= $t($kind . '.title') ?></title>
  <link rel="stylesheet" href="/assets/site.css">
</head>
<body>
<main class="block" id="contenu">
  <div class="wrap narrow">
    <h1 class="page-title"><?= $t($kind . '.title') ?></h1>
    <p><?= $t($kind . '.text') ?></p>
    <p><a class="btn" href="/<?= $e($lang) ?>/"><?= $t('notfound.back') ?></a></p>
  </div>
</main>
</body>
</html>
