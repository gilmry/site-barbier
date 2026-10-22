<?php /** @var callable $e @var callable $t @var string $lang @var array<string,mixed> $salon @var string $tagline */ ?>
<footer>
  <div class="wrap">
    <span class="logo"><?= $e($salon['name']) ?></span>
    <small><?= $e($tagline) ?></small>
    <ul class="legal caps">
      <li><a href="/<?= $e($lang) ?>/<?= $t('slug.legal_notice') ?>"><?= $t('footer.legal') ?></a></li>
      <li><a href="/<?= $e($lang) ?>/<?= $t('slug.privacy') ?>"><?= $t('footer.privacy') ?></a></li>
    </ul>
  </div>
</footer>
