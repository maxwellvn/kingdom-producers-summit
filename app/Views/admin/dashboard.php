<?php /** @var array $stats @var array $stages @var array $recent @var array $attendance @var bool $express @var string $openToken @var array $watchers */
$pathLabel = ['onsite' => 'Onsite', 'online' => 'Online', 'initiative' => 'Initiative'];
$max = max(1, ...array_column($stages, 'count'));
?>
<section class="adm-page">
  <header class="adm-page__head">
    <div>
      <p class="eyebrow"><span class="eyebrow__dot"></span>Overview</p>
      <h1 class="adm-page__title">Registrations</h1>
    </div>
    <span class="mono adm-page__meta">Updated <?= date('j M Y, H:i') ?></span>
  </header>

  <?php $flash = (string) \App\Core\Session::get('admin_flash', ''); ?>
  <?php if ($flash !== ''): ?>
    <div class="form__alert" role="status" style="border-color: rgba(46,160,67,.5);margin-bottom:1.2rem"><span><?= e($flash) ?></span></div>
  <?php endif; ?>

  <section id="event-day" class="adm-panel eventday">
    <h2 class="adm-panel__title">Event day</h2>
    <div class="eventday__grid">
      <div class="eventday__item">
        <div>
          <strong>Express registration</strong>
          <span class="mono eventday__state <?= $express ? 'is-on' : '' ?>"><?= $express ? 'On' : 'Off' ?></span>
          <p>Walk-ins at the desk fill in name, contact and consent only. Everything else is skipped.</p>
        </div>
        <form method="post" action="<?= url('/admin/event-day') ?>">
          <?= csrf_field() ?>
          <input type="hidden" name="action" value="<?= $express ? 'express_off' : 'express_on' ?>">
          <button type="submit" class="adm-btn <?= $express ? '' : 'adm-btn--solid' ?>"><?= $express ? 'Turn off' : 'Turn on' ?></button>
        </form>
      </div>
      <div class="eventday__item">
        <div>
          <strong>Open watch link</strong>
          <span class="mono eventday__state <?= $openToken !== '' ? 'is-on' : '' ?>"><?= $openToken !== '' ? 'Active' : 'Off' ?></span>
          <p>Anyone with the link enters the stream with just a name and email; unregistered people are added as online registrants.</p>
          <?php if ($openToken !== ''): ?>
            <?php $openUrl = site_url() . '/watch?open=' . $openToken; ?>
            <p class="eventday__link"><input type="text" readonly value="<?= e($openUrl) ?>" onclick="this.select()" aria-label="Open watch link"> <button type="button" class="adm-btn" data-copy="<?= e($openUrl) ?>">Copy</button></p>
          <?php endif; ?>
        </div>
        <form method="post" action="<?= url('/admin/event-day') ?>" style="display:flex;gap:.4rem;flex-wrap:wrap">
          <?= csrf_field() ?>
          <?php if ($openToken !== ''): ?>
            <button type="submit" name="action" value="open_link_new" class="adm-btn" onclick="return confirm('Make a new link? The old one stops working.')">New link</button>
            <button type="submit" name="action" value="open_link_off" class="adm-btn">Revoke</button>
          <?php else: ?>
            <button type="submit" name="action" value="open_link_new" class="adm-btn adm-btn--solid">Create link</button>
          <?php endif; ?>
        </form>
      </div>
    </div>
  </section>

  <div class="adm-stats">
    <div class="adm-stat adm-stat--lead">
      <span class="adm-stat__label mono">Total registered</span>
      <span class="adm-stat__value"><?= number_format($stats['total']) ?></span>
      <span class="adm-stat__sub mono">+<?= $stats['today'] ?> today · <?= $stats['countries'] ?> countries</span>
    </div>
    <div class="adm-stat">
      <span class="adm-stat__label mono">A · Onsite</span>
      <span class="adm-stat__value"><?= number_format($stats['onsite']) ?></span>
    </div>
    <div class="adm-stat">
      <span class="adm-stat__label mono">B · Online</span>
      <span class="adm-stat__value"><?= number_format($stats['online']) ?></span>
    </div>
    <div class="adm-stat">
      <span class="adm-stat__label mono">C · Initiative</span>
      <span class="adm-stat__value"><?= number_format($stats['initiative']) ?></span>
    </div>
    <a class="adm-stat adm-stat--attendance" href="<?= url('/admin/scanner') ?>">
      <span class="adm-stat__label mono">Checked in today</span>
      <span class="adm-stat__value"><?= number_format($attendance['today']) ?></span>
      <span class="adm-stat__sub mono"><?= number_format($attendance['total']) ?> total arrivals</span>
    </a>
  </div>

  <section class="adm-panel" style="margin-bottom:1.4rem" data-connected data-url="<?= url('/admin/analytics/live') ?>">
    <div style="display:flex;justify-content:space-between;align-items:baseline;gap:1rem;flex-wrap:wrap">
      <h2 class="adm-panel__title">Connected to the stream <span class="mono adm-muted" style="font-size:.8rem" data-connected-count><?= count($watchers) ?> watching</span></h2>
      <a class="mono adm-muted" style="font-size:.72rem" href="<?= url('/admin/stream') ?>">Stream controls →</a>
    </div>
    <div class="adm-table-wrap" style="max-height:18rem;overflow-y:auto;margin-top:.6rem">
      <table class="adm-table">
        <thead><tr><th>Name</th><th>Reference</th><th>Email</th><th>Device</th><th>Since</th></tr></thead>
        <tbody data-connected-rows>
          <?php if (!$watchers): ?>
            <tr><td colspan="5" class="adm-muted">Nobody is connected at the moment.</td></tr>
          <?php else: foreach ($watchers as $w): ?>
            <tr>
              <td data-label="Name"><?= e(trim(($w['first_name'] ?? '') . ' ' . ($w['last_name'] ?? '')) ?: 'Guest') ?></td>
              <td data-label="Reference" class="mono"><?= e($w['reference'] ?? '—') ?></td>
              <td data-label="Email"><?= e($w['email'] ?? '—') ?></td>
              <td data-label="Device" class="mono"><?= e($w['device']) ?></td>
              <td data-label="Since" class="mono adm-muted"><?= e(date('H:i', strtotime($w['started_at']))) ?></td>
            </tr>
          <?php endforeach; endif; ?>
        </tbody>
      </table>
    </div>
  </section>

  <div class="adm-grid">
    <section class="adm-panel">
      <h2 class="adm-panel__title">Producer stage</h2>
      <ul class="adm-bars">
        <?php foreach ($stages as $s): ?>
          <li class="adm-bar">
            <span class="adm-bar__label"><?= e(ucfirst($s['label'])) ?></span>
            <span class="adm-bar__track"><span class="adm-bar__fill" style="width: <?= round($s['count'] / $max * 100) ?>%"></span></span>
            <span class="adm-bar__value mono"><?= $s['count'] ?></span>
          </li>
        <?php endforeach; ?>
      </ul>
    </section>

    <section class="adm-panel adm-panel--wide">
      <div class="adm-panel__head">
        <h2 class="adm-panel__title">Most recent</h2>
        <a class="mono adm-link" href="<?= url('/admin/registrations') ?>">All registrations →</a>
      </div>
      <?php if (!$recent): ?>
        <p class="adm-empty mono">No registrations yet.</p>
      <?php else: ?>
        <div class="adm-table-wrap">
        <table class="adm-table">
          <thead><tr><th>Reference</th><th>Name</th><th>Path</th><th>Field</th><th>Country</th><th>When</th></tr></thead>
          <tbody>
            <?php foreach ($recent as $r): ?>
              <tr>
                <td class="mono"><?= e($r['reference']) ?></td>
                <td><?= e($r['first_name'] . ' ' . $r['last_name']) ?><br><span class="adm-muted"><?= e($r['email']) ?></span></td>
                <td><span class="adm-pill adm-pill--<?= e($r['participation']) ?>"><?= e($pathLabel[$r['participation']] ?? $r['participation']) ?></span></td>
                <td><?= e(field_label($r)) ?></td>
                <td><?= e($r['country']) ?></td>
                <td class="mono adm-muted"><?= e(date('j M, H:i', strtotime($r['created_at']))) ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
        </div>
      <?php endif; ?>
    </section>
  </div>
</section>
<script>
  // Who is connected, refreshed every fifteen seconds.
  (function () {
    var root = document.querySelector('[data-connected]'); if (!root) return;
    var rows = root.querySelector('[data-connected-rows]'), count = root.querySelector('[data-connected-count]');
    var esc = function (s) { return String(s == null ? '' : s).replace(/[&<>"]/g, function (c) { return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;'}[c]; }); };
    function tick() {
      fetch(root.getAttribute('data-url'), { credentials: 'same-origin', headers: { 'Accept': 'application/json' } })
        .then(function (r) { return r.json(); })
        .then(function (d) {
          if (!d || !d.ok) return;
          count.textContent = d.watchers.length + ' watching';
          rows.innerHTML = d.watchers.length ? d.watchers.map(function (w) {
            var since = w.since ? new Date(w.since.replace(' ', 'T')) : null;
            return '<tr><td data-label="Name">' + esc(w.name) + '</td><td data-label="Reference" class="mono">' + esc(w.reference || '—') + '</td><td data-label="Email">' + esc(w.email || '—') + '</td><td data-label="Device" class="mono">' + esc(w.device) + '</td><td data-label="Since" class="mono adm-muted">' + (since ? since.toTimeString().slice(0, 5) : '') + '</td></tr>';
          }).join('') : '<tr><td colspan="5" class="adm-muted">Nobody is connected at the moment.</td></tr>';
        }).catch(function () {});
    }
    setInterval(tick, 15000);
  })();
  document.querySelectorAll('[data-copy]').forEach(function (b) {
    b.addEventListener('click', function () {
      var t = b.getAttribute('data-copy'), done = function () { b.textContent = 'Copied'; setTimeout(function () { b.textContent = 'Copy'; }, 1500); };
      if (navigator.clipboard && navigator.clipboard.writeText) { navigator.clipboard.writeText(t).then(done); }
      else { var i = b.previousElementSibling; i.select(); document.execCommand('copy'); done(); }
    });
  });
</script>
