<?php /** @var callable $e @var callable $t @var string $lang @var string $title @var string $description @var array<string,string> $alternates @var string $version */ ?>
<!doctype html>
<html lang="<?= $e($lang) ?>">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover">
  <title><?= $e($title) ?></title>
  <meta name="description" content="<?= $e($description) ?>">
  <?php foreach ($alternates as $altLang => $altHref): ?>
  <link rel="alternate" hreflang="<?= $e($altLang) ?>" href="<?= $e($altHref) ?>">
  <?php endforeach; ?>
  <link rel="preload" href="/assets/fonts/unifrakturmaguntia-latin-400.woff2" as="font" type="font/woff2" crossorigin>
  <link rel="preload" href="/assets/fonts/cormorant-garamond-latin-500.woff2" as="font" type="font/woff2" crossorigin>
  <link rel="stylesheet" href="/assets/site.css?v=<?= $e($version) ?>">
  <script src="/assets/site.js?v=<?= $e($version) ?>" defer></script>
</head>
