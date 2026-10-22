<?php
/**
 * Page d'accueil (FR-001) — fidèle à la maquette « Le Corbeau » (docs/design/reference-le-corbeau.md).
 * @var callable $e @var callable $t @var callable $html @var string $lang
 * @var array<string, array<string,mixed>> $sections @var array<string,mixed> $salon
 * @var list<array<string,mixed>> $categories @var list<array<string,mixed>> $barbers @var list<array<string,mixed>> $hours
 * @var list<string> $closures
 */
$s = $sections;
$hero = $s['hero'] ?? [];
include __DIR__ . '/layout-head.php';
?>
<body>
<?php include __DIR__ . '/layout-header.php'; ?>
<main id="contenu">
  <section class="hero" id="accueil">
    <div class="wrap">
      <div class="pole" aria-hidden="true"></div>
      <div>
        <p class="caps eyebrow"><?= $e($hero['eyebrow'] ?? '') ?></p>
        <h1><?= $e($hero['title'] ?? $salon['name']) ?></h1>
        <p class="lead"><?= $e($hero['lead'] ?? '') ?> <em><?= $e($hero['lead_em'] ?? '') ?></em></p>
        <div class="actions">
          <a class="btn" href="#reserver"><?= $e($hero['cta_primary'] ?? '') ?></a>
          <a class="btn ghost" href="#tarifs"><?= $e($hero['cta_secondary'] ?? '') ?></a>
        </div>
        <ul class="facts">
          <?php foreach ([1, 2, 3] as $i): ?>
            <?php if (($hero["fact{$i}_value"] ?? '') !== ''): ?>
          <li><span class="caps"><?= $e($hero["fact{$i}_label"] ?? '') ?></span><strong><?= $e($hero["fact{$i}_value"]) ?></strong></li>
            <?php endif; ?>
          <?php endforeach; ?>
        </ul>
      </div>
      <?php if (isset($hero['image'])): ?>
      <img class="hero-image" src="<?= $e($hero['image']['src']) ?>" width="<?= $e($hero['image']['width']) ?>" height="<?= $e($hero['image']['height']) ?>" alt="">
      <?php endif; ?>
    </div>
  </section>

  <div class="wrap"><div class="divider" aria-hidden="true"><span></span></div></div>

  <section class="block" id="maison">
    <div class="wrap">
      <div class="sec-head">
        <p class="caps eyebrow"><?= $e($s['maison']['eyebrow'] ?? '') ?></p>
        <h2><?= $e($s['maison']['title'] ?? '') ?></h2>
      </div>
      <div class="maison">
        <div class="prose"><?= $html($s['maison']['body'] ?? '') ?></div>
        <?php $rules = array_values(array_filter(explode("\n", (string) ($s['maison']['rules'] ?? '')), static fn ($r) => trim($r) !== '')); ?>
        <?php if ($rules !== []): ?>
        <aside class="oath">
          <h3><?= $e($s['maison']['rules_title'] ?? '') ?></h3>
          <ol><?php foreach ($rules as $rule): ?><li><span><?= $e(trim($rule)) ?></span></li><?php endforeach; ?></ol>
        </aside>
        <?php endif; ?>
      </div>
      <?php if (isset($s['maison']['image'])): ?>
      <img class="maison-image" src="<?= $e($s['maison']['image']['src']) ?>" width="<?= $e($s['maison']['image']['width']) ?>" height="<?= $e($s['maison']['image']['height']) ?>" alt="" loading="lazy">
      <?php endif; ?>
    </div>
  </section>

  <?php if ($categories !== []): ?>
  <div class="wrap"><div class="divider" aria-hidden="true"><span></span></div></div>
  <section class="block" id="tarifs">
    <div class="wrap">
      <div class="sec-head">
        <p class="caps eyebrow"><?= $e($s['carte']['eyebrow'] ?? '') ?></p>
        <h2><?= $e($s['carte']['title'] ?? '') ?></h2>
        <div class="sec-intro"><?= $html($s['carte']['intro'] ?? '') ?></div>
      </div>
      <div class="carte">
        <?php foreach ($categories as $cat): ?>
        <div>
          <h3><?= $e($cat['name']) ?></h3>
          <ul>
            <?php foreach ($cat['services'] as $svc): ?>
            <li class="item">
              <div class="row"><span class="name"><?= $e($svc['name']) ?></span><span class="dots"></span><span class="price"><?php if ($svc['price'] === null): ?><?= $t('price.free') ?><?php else: ?><?= $e($svc['price']) ?><?php endif; ?></span></div>
              <p class="desc"><?= $e($svc['description']) ?> <span class="dur"><?= $e($svc['duration']) ?></span></p>
            </li>
            <?php endforeach; ?>
          </ul>
        </div>
        <?php endforeach; ?>
      </div>
      <div class="note"><?= $html($s['carte']['note'] ?? '') ?></div>
    </div>
  </section>
  <?php endif; ?>

  <?php if ($barbers !== []): ?>
  <div class="wrap"><div class="divider" aria-hidden="true"><span></span></div></div>
  <section class="block" id="equipe">
    <div class="wrap">
      <div class="sec-head">
        <p class="caps eyebrow"><?= $e($s['equipe']['eyebrow'] ?? '') ?></p>
        <h2><?= $e($s['equipe']['title'] ?? '') ?></h2>
      </div>
      <div class="equipe">
        <?php foreach ($barbers as $b): ?>
        <article class="barbier">
          <?php if ($b['photo'] !== null): ?>
          <img class="portrait" src="<?= $e($b['photo']['src']) ?>" width="<?= $e($b['photo']['width']) ?>" height="<?= $e($b['photo']['height']) ?>" alt="<?= $e($b['firstName']) ?>" loading="lazy">
          <?php else: ?>
          <div class="monogram" aria-hidden="true"><?= $e($b['initial']) ?></div>
          <?php endif; ?>
          <div class="body">
            <span class="caps"><?= $e($b['specialty']) ?></span>
            <h3><?= $e($b['firstName']) ?></h3>
            <p><?= $e($b['bio']) ?></p>
          </div>
        </article>
        <?php endforeach; ?>
      </div>
    </div>
  </section>
  <?php endif; ?>

  <section class="block resa" id="reserver">
    <div class="wrap">
      <div class="sec-head">
        <p class="caps eyebrow"><?= $e($s['reservation']['eyebrow'] ?? '') ?></p>
        <h2><?= $e($s['reservation']['title'] ?? '') ?></h2>
        <div class="sec-intro"><?= $html($s['reservation']['intro'] ?? '') ?></div>
      </div>
      <div class="actions">
        <a class="btn" href="/<?= $e($lang) ?>/<?= $t('slug.book') ?>"><?= $t('book.cta') ?></a>
        <p class="hint"><?= $t('book.phone', ['phone' => $salon['phone']]) ?></p>
      </div>
    </div>
  </section>

  <section class="block" id="horaires">
    <div class="wrap">
      <div class="sec-head">
        <p class="caps eyebrow"><?= $e($s['horaires']['eyebrow'] ?? '') ?></p>
        <h2><?= $e($s['horaires']['title'] ?? '') ?></h2>
      </div>
      <div class="infos">
        <div>
          <table class="hours">
            <tbody>
            <?php foreach ($hours as $h): ?>
              <tr class="<?= $e(($h['opens'] === null ? 'closed' : '') . ($h['today'] ? ' today' : '')) ?>">
                <th scope="row"><?= $t('day.' . $h['weekday']) ?><?php if ($h['today']): ?> <span class="badge"><?= $t('hours.today') ?></span><?php endif; ?></th>
                <td><?php if ($h['opens'] === null): ?><?= $t('hours.closed') ?><?php else: ?><?= $e($h['opens'] . ' – ' . $h['closes']) ?><?php endif; ?></td>
              </tr>
            <?php endforeach; ?>
            </tbody>
          </table>
          <?php foreach ($closures ?? [] as $closure): ?>
          <p class="closure"><?= $t('hours.closure', ['date' => $closure]) ?></p>
          <?php endforeach; ?>
        </div>
        <div class="contact">
          <dl>
            <?php if ($salon['address'] !== []): ?>
            <div><dt class="caps"><?= $t('contact.address') ?></dt><dd><?php foreach ($salon['address'] as $i => $line): ?><?= $e($line) ?><?php if ($i < count($salon['address']) - 1): ?><br><?php endif; ?><?php endforeach; ?></dd></div>
            <?php endif; ?>
            <div><dt class="caps"><?= $t('contact.phone') ?></dt><dd class="copyline"><a id="phone" href="<?= $e($salon['phoneHref']) ?>"><?= $e($salon['phone']) ?></a><button class="btn ghost small" type="button" data-copy="#phone" data-copied="<?= $t('contact.copied') ?>" hidden><?= $t('contact.copy') ?></button></dd></div>
            <div><dt class="caps"><?= $t('contact.email') ?></dt><dd><a href="mailto:<?= $e($salon['email']) ?>"><?= $e($salon['email']) ?></a></dd></div>
            <?php if ($salon['access'] !== ''): ?>
            <div><dt class="caps"><?= $t('contact.access') ?></dt><dd><?= $e($salon['access']) ?></dd></div>
            <?php endif; ?>
          </dl>
        </div>
      </div>
    </div>
  </section>
</main>
<?php $tagline = (string) ($s['footer']['tagline'] ?? ''); include __DIR__ . '/layout-footer.php'; ?>
</body>
</html>
