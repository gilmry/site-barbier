<?php /** @var callable $e @var string $version */ ?>
<!doctype html>
<html lang="fr">
<head>
  <meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="robots" content="noindex, nofollow">
  <title>Administration du site</title>
  <link rel="stylesheet" href="/islands/admin.css?v=<?= $e($version) ?>">
</head>
<body>
  <div id="admin" data-version="<?= $e($version) ?>"><noscript>L'administration nécessite JavaScript.</noscript></div>
  <script type="module" src="/islands/admin.js?v=<?= $e($version) ?>"></script>
</body>
</html>
