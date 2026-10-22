<?php /** @var callable $e @var callable $t @var string $lang @var array<string,mixed> $salon @var array<string,mixed> $footer @var string $islandStrings @var string $version */
include __DIR__ . '/layout-head.php'; ?>
<body>
<?php include __DIR__ . '/layout-header.php'; ?>
<main id="contenu" class="block booking-page">
  <div class="wrap narrow">
    <p class="caps eyebrow"><?= $t('booking.eyebrow') ?></p>
    <h1 class="page-title"><?= $t('booking.title') ?></h1>
    <div id="booking" data-lang="<?= $e($lang) ?>" data-phone="<?= $e($salon['phone']) ?>" data-strings="<?= $e($islandStrings) ?>">
      <noscript><p><?= $t('booking.noscript', ['phone' => $salon['phone']]) ?></p></noscript>
    </div>
  </div>
</main>
<link rel="stylesheet" href="/islands/booking.css?v=<?= $e($version) ?>">
<script type="module" src="/islands/booking.js?v=<?= $e($version) ?>"></script>
<?php $tagline = (string) ($footer['tagline'] ?? ''); include __DIR__ . '/layout-footer.php'; ?>
</body>
</html>
