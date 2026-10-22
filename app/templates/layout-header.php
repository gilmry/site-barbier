<?php /** @var callable $e @var callable $t @var string $lang @var array<string,mixed> $salon @var array<string,string> $switch @var bool $onHome */ ?>
<a class="skip" href="#contenu"><?= $t('skip') ?></a>
<header class="top">
  <div class="wrap">
    <a class="logo" href="/<?= $e($lang) ?>/"><?= $e($salon['name']) ?></a>
    <nav aria-label="<?= $t('nav.label') ?>">
      <ul class="caps">
        <li><a href="/<?= $e($lang) ?>/#maison"><?= $t('nav.maison') ?></a></li>
        <li><a href="/<?= $e($lang) ?>/#tarifs"><?= $t('nav.tarifs') ?></a></li>
        <li><a href="/<?= $e($lang) ?>/#equipe"><?= $t('nav.equipe') ?></a></li>
        <li><a href="/<?= $e($lang) ?>/#horaires"><?= $t('nav.horaires') ?></a></li>
      </ul>
    </nav>
    <div class="top-actions">
      <ul class="langs caps" aria-label="<?= $t('lang.switch') ?>">
        <?php foreach ($switch as $code => $href): ?>
        <li><a href="<?= $e($href) ?>" hreflang="<?= $e($code) ?>" lang="<?= $e($code) ?>"<?php if ($code === $lang): ?> aria-current="true"<?php endif; ?>><?= $e(strtoupper($code)) ?></a></li>
        <?php endforeach; ?>
      </ul>
      <a class="btn small" href="/<?= $e($lang) ?>/#reserver"><?= $t('nav.book') ?></a>
    </div>
  </div>
</header>
