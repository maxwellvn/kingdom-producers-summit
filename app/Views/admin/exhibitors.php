<?php /** @var array $rows @var array $counts @var string $status @var string $flash @var string $link */
$tabs = ['' => 'All', 'pending' => 'To review', 'approved' => 'Approved', 'declined' => 'Declined'];
$pill = ['pending' => 'claimed', 'approved' => 'paid', 'declined' => 'none'];
?>
<section class="adm-page">
  <header class="adm-page__head">
    <div>
      <p class="eyebrow"><span class="eyebrow__dot"></span>People</p>
      <h1 class="adm-page__title">Exhibitors <span class="adm-page__title-sub"><?= (int) $counts['pending'] ?> to review</span></h1>
    </div>
    <div style="display:flex;flex-wrap:wrap;gap:.5rem;justify-content:flex-end">
      <a class="adm-btn" href="<?= e($link) ?>" target="_blank" rel="noopener"><?= ph('arrow-square-out') ?> Application form</a>
      <a class="adm-btn" href="<?= url('/admin/exhibitors.csv') ?>"><?= ph('download-simple') ?> Export CSV</a>
    </div>
  </header>

  <?php if ($flash !== ''): ?>
    <div class="form__alert" role="status"><span><?= e($flash) ?></span></div>
  <?php endif; ?>

  <div class="adm-filters">
    <div class="adm-tabs" role="tablist">
      <?php foreach ($tabs as $val => $label): ?>
        <a class="adm-tab <?= $status === $val ? 'is-active' : '' ?>" href="<?= url('/admin/exhibitors') . ($val !== '' ? '?status=' . $val : '') ?>"><?= e($label) ?> <span class="adm-muted"><?= $val === '' ? array_sum($counts) : (int) $counts[$val] ?></span></a>
      <?php endforeach; ?>
    </div>
  </div>

  <?php if (!$rows): ?>
    <div class="adm-empty"><?= ph('storefront') ?><strong><?= $status === '' ? 'No applications yet' : 'Nothing here' ?></strong>Share the application form and applications appear here for you to approve.</div>
  <?php else: ?>
    <div class="exh-list">
      <?php foreach ($rows as $r): ?>
        <article class="adm-panel exh">
          <header class="exh__head">
            <div>
              <h2 class="adm-panel__title"><?= e($r['business']) ?></h2>
              <p class="adm-muted"><?= e($r['category']) ?> · applied <?= date('j M, H:i', strtotime((string) $r['created_at'])) ?></p>
            </div>
            <span class="adm-pill adm-pill--<?= e($pill[$r['status']]) ?>"><?= e(['pending' => 'To review', 'approved' => 'Approved', 'declined' => 'Declined'][$r['status']]) ?></span>
          </header>
          <p class="exh__desc"><?= nl2br(e((string) $r['description'])) ?></p>
          <dl class="exh__meta">
            <div><dt>Contact</dt><dd><?= e(trim($r['first_name'] . ' ' . $r['last_name'])) ?> · <a href="mailto:<?= e($r['email']) ?>"><?= e($r['email']) ?></a><?= $r['phone'] ? ' · ' . e($r['phone']) : '' ?><?= $r['kingschat'] ? ' · @' . e($r['kingschat']) : '' ?></dd></div>
            <?php if ($r['website']): $href = preg_match('#^https?://#i', $r['website']) ? $r['website'] : 'https://' . $r['website']; ?><div><dt>Online</dt><dd><a href="<?= e($href) ?>" target="_blank" rel="noopener nofollow"><?= e($r['website']) ?></a></dd></div><?php endif; ?>
            <div><dt>Stand</dt><dd><?= (int) $r['staff'] ?> <?= (int) $r['staff'] === 1 ? 'person' : 'people' ?><?= $r['needs_power'] ? ' · needs power' : '' ?></dd></div>
            <?php if ($r['notes']): ?><div><dt>Notes</dt><dd><?= e($r['notes']) ?></dd></div><?php endif; ?>
            <?php if ($r['decided_at']): ?><div><dt>Decided</dt><dd><?= date('j M, H:i', strtotime((string) $r['decided_at'])) ?><?= $r['decided_by'] ? ' by ' . e($r['decided_by']) : '' ?></dd></div><?php endif; ?>
          </dl>
          <div class="exh__actions">
            <?php foreach (['approved' => ['Approve', 'check', 'adm-btn--dark'], 'declined' => ['Decline', 'x', '']] as $to => [$label, $ico, $cls]): if ($r['status'] === $to) continue; ?>
              <form method="post" action="<?= url('/admin/exhibitors/decide') ?>" onsubmit="return confirm('<?= $to === 'approved' ? 'Approve ' . e(addslashes($r['business'])) . '? They will be emailed their confirmation.' : 'Decline ' . e(addslashes($r['business'])) . '? They will be emailed.' ?>')">
                <?= csrf_field() ?>
                <input type="hidden" name="id" value="<?= (int) $r['id'] ?>">
                <input type="hidden" name="status" value="<?= $to ?>">
                <input type="hidden" name="back_status" value="<?= e($status) ?>">
                <button type="submit" class="adm-btn <?= $cls ?>"><?= ph($ico) ?> <?= $label ?></button>
              </form>
            <?php endforeach; ?>
          </div>
        </article>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</section>
