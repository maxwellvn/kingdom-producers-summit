<?php
/** @var int $days @var array $summary @var array $daily @var array $pages
 *  @var array $referrers @var array $devices @var array $live @var array $watchers */
$change = static function (int $now, int $before): string {
    if ($before === 0) {
        return $now > 0 ? 'new' : '—';
    }
    $delta = (int) round((($now - $before) / $before) * 100);
    return ($delta >= 0 ? '+' : '') . $delta . '%';
};
$peak = max(1, max(array_column($daily, 'views')));
$windows = [1 => 'Today', 7 => '7 days', 30 => '30 days', 90 => '90 days'];
?>
<section class="adm-page">
  <header class="adm-page__head">
    <div>
      <p class="eyebrow"><span class="eyebrow__dot"></span>Analytics</p>
      <h1 class="adm-page__title">Traffic <span class="adm-page__title-sub">and viewers</span></h1>
    </div>
    <div class="adm-tabs">
      <?php foreach ($windows as $value => $label): ?>
        <a class="adm-tab <?= $days === $value ? 'is-active' : '' ?>" href="<?= url('/admin/analytics') ?>?days=<?= $value ?>"><?= e($label) ?></a>
      <?php endforeach; ?>
    </div>
  </header>

  <!-- Live -->
  <div class="adm-stats" data-live-url="<?= e(url('/admin/analytics/live')) ?>">
    <article class="adm-stat adm-stat--lead">
      <p class="adm-stat__label mono">On the site now</p>
      <p class="adm-stat__value" data-live="site">—</p>
      <p class="adm-stat__sub mono"><span data-live="visitors">—</span> unique · updates every 15s</p>
    </article>
    <article class="adm-stat">
      <p class="adm-stat__label mono">Watching the stream</p>
      <p class="adm-stat__value" data-live="watch">—</p>
    </article>
    <article class="adm-stat">
      <p class="adm-stat__label mono">Visitors</p>
      <p class="adm-stat__value"><?= number_format($summary['visitors']) ?></p>
      <p class="adm-stat__sub mono"><?= e($change($summary['visitors'], $summary['visitors_before'])) ?> on the period before</p>
    </article>
    <article class="adm-stat">
      <p class="adm-stat__label mono">Page views</p>
      <p class="adm-stat__value"><?= number_format($summary['views']) ?></p>
      <p class="adm-stat__sub mono"><?= e($change($summary['views'], $summary['views_before'])) ?> on the period before</p>
    </article>
    <article class="adm-stat">
      <p class="adm-stat__label mono">Views per visitor</p>
      <p class="adm-stat__value"><?= $summary['visitors'] ? number_format($summary['views'] / $summary['visitors'], 1) : '0' ?></p>
    </article>
  </div>

  <!-- Trend -->
  <div class="adm-panel">
    <h2 class="adm-panel__title">Daily views and visitors</h2>
    <div class="adm-chart" role="img" aria-label="Daily page views for the last <?= count($daily) ?> days">
      <?php foreach ($daily as $point): ?>
        <div class="adm-chart__col" title="<?= e($point['day']) ?>: <?= $point['views'] ?> views, <?= $point['visitors'] ?> visitors">
          <span class="adm-chart__bar" style="height: <?= max(2, (int) round($point['views'] / $peak * 100)) ?>%"></span>
          <span class="adm-chart__bar adm-chart__bar--visitors" style="height: <?= max(1, (int) round($point['visitors'] / $peak * 100)) ?>%"></span>
        </div>
      <?php endforeach; ?>
    </div>
    <p class="adm-chart__axis mono">
      <span><?= e(date('j M', strtotime($daily[0]['day'] ?? 'now'))) ?></span>
      <span><?= e(date('j M', strtotime($daily[count($daily) - 1]['day'] ?? 'now'))) ?></span>
    </p>
    <p class="adm-muted mono adm-chart__key">
      <span class="adm-chart__swatch"></span> views
      <span class="adm-chart__swatch adm-chart__swatch--visitors"></span> unique visitors
    </p>
  </div>

  <!-- Breakdowns -->
  <div class="adm-columns">
    <?php
    $tables = [
      'Most visited pages' => $pages,
      'Where they came from' => $referrers,
      'Devices' => $devices,
    ];
    foreach ($tables as $heading => $rows):
      $total = max(1, array_sum(array_column($rows, 'count')));
    ?>
      <div class="adm-panel">
        <h2 class="adm-panel__title"><?= e($heading) ?></h2>
        <?php if (!$rows): ?>
          <p class="adm-empty mono">Nothing yet.</p>
        <?php else: ?>
          <ul class="adm-rank">
            <?php $top = max(1, max(array_column($rows, 'count'))); ?>
            <?php foreach ($rows as $row): ?>
              <li class="adm-rank__row">
                <span class="adm-rank__label" title="<?= e($row['label']) ?>"><?= e($row['label']) ?></span>
                <span class="adm-rank__count mono"><?= number_format($row['count']) ?><span class="adm-rank__share"> · <?= (int) round($row['count'] / $total * 100) ?>%</span></span>
                <span class="adm-rank__track"><span class="adm-rank__fill" style="width: <?= max(3, (int) round($row['count'] / $top * 100)) ?>%"></span></span>
              </li>
            <?php endforeach; ?>
          </ul>
        <?php endif; ?>
      </div>
    <?php endforeach; ?>
  </div>

  <!-- Who is watching -->
  <div class="adm-panel">
    <h2 class="adm-panel__title">In the stream now</h2>
    <div class="adm-table-wrap">
      <table class="adm-table" data-watchers>
        <thead><tr><th>Name</th><th>Reference</th><th>Email</th><th>Device</th><th>Since</th></tr></thead>
        <tbody>
          <?php if (!$watchers): ?>
            <tr><td colspan="5" class="adm-muted">Nobody is watching at the moment.</td></tr>
          <?php else: ?>
            <?php foreach ($watchers as $w): ?>
              <tr>
                <td><?= e(trim(($w['first_name'] ?? '') . ' ' . ($w['last_name'] ?? '')) ?: 'Guest') ?></td>
                <td class="mono"><?= e($w['reference'] ?? '—') ?></td>
                <td><?= e($w['email'] ?? '—') ?></td>
                <td class="mono"><?= e($w['device']) ?></td>
                <td class="mono adm-muted"><?= e(date('H:i', strtotime($w['started_at']))) ?></td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</section>

<script>
// The live figures refresh on their own; everything else is rendered once.
(function () {
  var panel = document.querySelector('[data-live-url]');
  if (!panel) return;
  var url = panel.getAttribute('data-live-url');

  function paint(data) {
    ['site', 'watch', 'visitors'].forEach(function (key) {
      var el = panel.querySelector('[data-live="' + key + '"]');
      if (el) el.textContent = String(data.live[key]);
    });

    var body = document.querySelector('[data-watchers] tbody');
    if (!body) return;
    if (!data.watchers.length) {
      body.innerHTML = '<tr><td colspan="5" class="adm-muted">Nobody is watching at the moment.</td></tr>';
      return;
    }
    body.innerHTML = data.watchers.map(function (w) {
      var since = w.since ? new Date(w.since.replace(' ', 'T')) : null;
      return '<tr>' +
        '<td>' + esc(w.name) + '</td>' +
        '<td class="mono">' + esc(w.reference || '—') + '</td>' +
        '<td>' + esc(w.email || '—') + '</td>' +
        '<td class="mono">' + esc(w.device) + '</td>' +
        '<td class="mono adm-muted">' + (since ? since.toTimeString().slice(0, 5) : '—') + '</td>' +
        '</tr>';
    }).join('');
  }

  function esc(value) {
    return String(value == null ? '' : value).replace(/[&<>"]/g, function (c) {
      return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' }[c];
    });
  }

  function refresh() {
    if (document.hidden) return;
    fetch(url, { headers: { Accept: 'application/json' } })
      .then(function (r) { return r.json(); })
      .then(function (d) { if (d && d.ok) paint(d); })
      .catch(function () {});
  }

  refresh();
  window.setInterval(refresh, 15000);
  document.addEventListener('visibilitychange', function () { if (!document.hidden) refresh(); });
})();
</script>
