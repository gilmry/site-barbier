<?php /** @var callable $e @var callable $t @var string $lang @var array<string,mixed> $salon @var array<string,mixed> $footer @var ?array<string,mixed> $summary @var string $state @var string $message @var string $action */
include __DIR__ . '/layout-head.php'; ?>
<body>
<?php include __DIR__ . '/layout-header.php'; ?>
<main id="contenu" class="block">
  <div class="wrap narrow">
    <h1 class="page-title"><?= $t('cancel.title') ?></h1>
    <?php if ($summary === null): ?>
      <p><?= $t('cancel.unknown', ['phone' => $salon['phone']]) ?></p>
    <?php else: ?>
      <div class="recap">
        <p><strong><?= $e($summary['service']) ?></strong> — <?= $e($summary['barber']) ?></p>
        <p><?= $e($summary['date']) ?> · <?= $e($summary['time']) ?> · <?= $e($summary['durationMin']) ?> min · <?= $e($summary['price']) ?></p>
      </div>
      <?php if ($state === 'confirm'): ?>
        <form method="post" action="<?= $e($action) ?>">
          <p><?= $t('cancel.question') ?></p>
          <button class="btn" type="submit"><?= $t('cancel.confirm') ?></button>
        </form>
      <?php elseif ($state === 'done'): ?>
        <p class="notice" role="status"><?= $t('cancel.done') ?></p>
      <?php elseif ($state === 'already'): ?>
        <p class="notice"><?= $t('cancel.already') ?></p>
      <?php else: ?>
        <p class="notice" role="alert"><?= $t('cancel.too_late', ['phone' => $salon['phone']]) ?></p>
      <?php endif; ?>
    <?php endif; ?>
  </div>
</main>
<?php $tagline = (string) ($footer['tagline'] ?? ''); include __DIR__ . '/layout-footer.php'; ?>
</body>
</html>
