<?php /** @var callable $e @var callable $t @var callable $html @var string $lang @var array<string,mixed> $page @var array<string,mixed> $salon @var array<string,mixed> $footer */
include __DIR__ . '/layout-head.php'; ?>
<body>
<?php include __DIR__ . '/layout-header.php'; ?>
<main id="contenu" class="block">
  <div class="wrap narrow">
    <h1 class="page-title"><?= $e($page['title'] ?? '') ?></h1>
    <div class="prose legal-text"><?= $html($page['body'] ?? '') ?></div>
  </div>
</main>
<?php $tagline = (string) ($footer['tagline'] ?? ''); include __DIR__ . '/layout-footer.php'; ?>
</body>
</html>
