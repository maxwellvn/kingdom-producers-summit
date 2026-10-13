<?php /** @var array $archive @var array<string,string> $tables @var string $table @var string $search @var array $result @var string $flash */
$slug = $archive['slug'];
$qs = static fn (array $extra) => url('/admin/archives/view') . '?' . http_build_query(array_filter(array_merge(['slug' => $slug, 'table' => $table, 'q' => $search], $extra), static fn ($v) => $v !== '' && $v !== null));
$csv = url('/admin/archives/export.csv') . '?' . http_build_query(array_filter(['slug' => $slug, 'table' => $table, 'q' => $search], static fn ($v) => $v !== ''));
?>
<section class="adm-page">
  <header class="adm-page__head">
    <div>
      <p class="eyebrow"><span class="eyebrow__dot"></span><a class="adm-link" href="<?= url('/admin/archives') ?>">Archive</a> · <?= e(\App\Core\Events::label((string) ($archive['event'] ?? \App\Core\Events::active()))) ?> · archived <?= date('j M Y', strtotime((string) $archive['created_at'])) ?></p>
      <h1 class="adm-page__title"><?= e($archive['label']) ?> <span class="adm-page__title-sub"><?= number_format($result['total']) ?> <?= e(strtolower($tables[$table])) ?></span></h1>
    </div>
    <div style="display:flex;flex-wrap:wrap;gap:.5rem;justify-content:flex-end">
      <a class="adm-btn" href="<?= e($csv) ?>"><?= ph('download-simple') ?> Export <?= e(strtolower($tables[$table])) ?> CSV</a>
    </div>
  </header>

  <?php if ($flash !== ''): ?>
    <div class="form__alert" role="status"><span><?= e($flash) ?></span></div>
  <?php endif; ?>

  <?php
  $c = $archive['counts'];
  $groupOf = static function (string $t): string { foreach (\App\Services\Archive::GROUPS as $g => $ts) { if (in_array($t, $ts, true)) { return $g; } } return 'People'; };
  $group = $groupOf($table);
  ?>
  <div class="arch-stats">
    <?php foreach (['registrations' => 'Registrations', 'attendances' => 'Checked in', 'exhibitors' => 'Exhibitors', 'sponsorships' => 'Sponsorships'] as $key => $label): ?>
      <a class="arch-stat<?= $table === $key ? ' is-on' : '' ?>" href="<?= $qs(['table' => $key, 'q' => null, 'page' => null]) ?>"><span><?= e($label) ?></span><strong><?= number_format((int) ($c[$key] ?? 0)) ?></strong></a>
    <?php endforeach; ?>
  </div>

  <form class="adm-filters arch-filters" method="get" action="<?= url('/admin/archives/view') ?>">
    <div class="arch-nav">
      <div class="adm-tabs" role="tablist" aria-label="Group">
        <?php foreach (\App\Services\Archive::GROUPS as $g => $ts): ?>
          <a class="adm-tab <?= $group === $g ? 'is-active' : '' ?>" href="<?= $qs(['table' => $ts[0], 'q' => null, 'page' => null]) ?>"><?= e($g) ?></a>
        <?php endforeach; ?>
      </div>
      <div class="arch-chips" role="tablist" aria-label="Table">
        <?php foreach (\App\Services\Archive::GROUPS[$group] as $key): ?>
          <a class="arch-chip<?= $table === $key ? ' is-on' : '' ?>" href="<?= $qs(['table' => $key, 'q' => null, 'page' => null]) ?>"><?= e($tables[$key]) ?> <span><?= number_format((int) ($c[$key] ?? 0)) ?></span></a>
        <?php endforeach; ?>
      </div>
    </div>
    <input type="hidden" name="slug" value="<?= e($slug) ?>">
    <input type="hidden" name="table" value="<?= e($table) ?>">
    <div class="adm-search">
      <input type="search" name="q" value="<?= e($search) ?>" placeholder="Search <?= e(strtolower($tables[$table])) ?>" aria-label="Search">
      <button type="submit" class="adm-btn adm-btn--ghost">Search</button>
    </div>
  </form>

  <?php if (!$result['rows']): ?>
    <div class="adm-empty"><?= ph($search !== '' ? 'magnifying-glass' : 'tray') ?><strong><?= $search !== '' ? 'No matches' : 'Nothing in this table' ?></strong><?= $search !== '' ? 'Try a shorter search.' : 'This part of the edition had no records.' ?></div>
  <?php else: ?>
    <div class="adm-table-wrap">
    <table class="adm-table">
      <thead><tr><?php foreach ($result['columns'] as $col): ?><th><?= e(str_replace('_', ' ', $col)) ?></th><?php endforeach; ?></tr></thead>
      <tbody>
        <?php foreach ($result['rows'] as $r): ?>
          <tr>
            <?php foreach ($result['columns'] as $col): $v = (string) ($r[$col] ?? ''); ?>
              <td data-label="<?= e(str_replace('_', ' ', $col)) ?>" title="<?= mb_strlen($v) > 60 ? e($v) : '' ?>"><?= e(mb_strlen($v) > 60 ? mb_substr($v, 0, 60) . '…' : $v) ?></td>
            <?php endforeach; ?>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
    </div>

    <?php if ($result['pages'] > 1): ?>
      <nav class="adm-pager mono" aria-label="Pagination">
        <?php if ($result['page'] > 1): ?><a href="<?= $qs(['page' => $result['page'] - 1]) ?>"><?= ph('caret-left') ?> Prev</a><?php endif; ?>
        <span>Page <?= $result['page'] ?> of <?= $result['pages'] ?></span>
        <?php if ($result['page'] < $result['pages']): ?><a href="<?= $qs(['page' => $result['page'] + 1]) ?>">Next <?= ph('caret-right') ?></a><?php endif; ?>
      </nav>
    <?php endif; ?>
  <?php endif; ?>

  <div class="adm-columns adm-columns--2" style="margin-top:2rem">
    <section class="adm-panel">
      <h2 class="adm-panel__title">Rename</h2>
      <form method="post" action="<?= url('/admin/archives/rename') ?>" style="display:flex;gap:.5rem;margin-top:.8rem;flex-wrap:wrap">
        <?= csrf_field() ?><input type="hidden" name="slug" value="<?= e($slug) ?>">
        <input type="text" name="label" value="<?= e($archive['label']) ?>" required maxlength="120" style="flex:1 1 12rem;padding:.55rem .7rem;border:1px solid rgba(0,0,0,.25);background:#fff">
        <button type="submit" class="adm-btn">Save</button>
      </form>
    </section>
    <section class="adm-panel">
      <h2 class="adm-panel__title">Delete for good</h2>
      <p class="adm-muted" style="margin:.4rem 0 .8rem">Drops every table in this archive. Export first if you may need it. Type <strong class="mono"><?= e($slug) ?></strong> to confirm.</p>
      <form method="post" action="<?= url('/admin/archives/delete') ?>" style="display:flex;gap:.5rem;flex-wrap:wrap">
        <?= csrf_field() ?><input type="hidden" name="slug" value="<?= e($slug) ?>">
        <input type="text" name="confirm" autocomplete="off" aria-label="Type the short name to confirm" style="flex:1 1 10rem;padding:.55rem .7rem;border:1px solid rgba(142,26,33,.5);background:#fff">
        <button type="submit" class="adm-btn adm-btn--danger">Delete archive</button>
      </form>
    </section>
  </div>
</section>
