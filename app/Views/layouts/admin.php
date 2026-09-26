<?php
/** @var string $content */
$title = ($title ?? 'Admin') . ' — Producers Summit Admin';
$bodyClass = $bodyClass ?? 'page-admin';
$authed = \App\Core\Session::get('admin_authenticated') === true;
$current = \App\Core\Url::currentPath();
?>
<!DOCTYPE html>
<html lang="en-GB">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="robots" content="noindex, nofollow">
  <title><?= e($title) ?></title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link rel="preconnect" href="https://api.fontshare.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Oswald:wght@500;600;700&family=IBM+Plex+Mono:wght@400;500&display=swap" rel="stylesheet">
  <link href="https://api.fontshare.com/v2/css?f[]=switzer@400,500,600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="<?= asset('css/app.css') ?>">
  <link rel="stylesheet" href="<?= asset('vendor/phosphor/style.css') ?>">
  <link rel="stylesheet" href="<?= asset('css/admin.css') ?>">
</head>
<body class="<?= e($bodyClass) ?>">
  <?php
  // The sidebar remembers being folded to icons; read server-side so the page never jumps on load.
  $rail = ($_COOKIE['adm_side'] ?? '') === 'rail';
  ?>
  <?php if ($authed): ?><div class="adm-shell<?= $rail ? ' adm-shell--rail' : '' ?>" data-shell><?php endif; ?>

  <?php if ($authed): ?>
  <?php
  // One job per page, grouped the way the team works: the day itself, people, messages, numbers, then setup.
  $groups = [
      ['Event day', [
          ['/admin/front-desk', 'Front desk', 'exact', 'door'],
          ['/admin/scanner', 'Access scanner', 'exact', 'scan'],
          ['/admin/stream', 'Stream', 'exact', 'radio'],
          ['/admin/engagement', 'Polls & chat', 'exact', 'chat'],
      ]],
      ['People', [
          ['/admin/registrations', 'Registrations', 'exact', 'users'],
          ['/admin/initiative', 'Initiative', 'exact', 'plant'],
          ['/admin/issue', 'Issue a place', 'exact', 'ticket'],
          ['/admin/commitments', 'Commitments', 'prefix', 'pen'],
      ]],
      ['Campaigns', [
          ['/admin/notifications', 'Notifications', 'prefix', 'send'],
      ]],
      ['Insights', [
          ['/admin/analytics', 'Analytics', 'prefix', 'chart'],
          ['/admin/diagnostics', 'Diagnostics', 'exact', 'pulse'],
          ['/admin/export.csv', 'Export CSV', 'none', 'download'],
      ]],
      ['Editions', [
          ['/admin/edition', 'Current edition', 'exact', 'calendar'],
          ['/admin/archives', 'Archive', 'prefix', 'archive'],
      ]],
      ['Settings', [
          ['/admin/payments', 'Payments', 'exact', 'card'],
          ['/admin/sponsorships', 'Sponsorships', 'exact', 'heart'],
          ['/admin/kingschat', 'KingsChat', 'prefix', 'bubble'],
          ['/admin/admins', 'Admin users', 'exact', 'shield'],
      ]],
  ];
  $isActive = static fn (string $path, string $mode): bool => match ($mode) {
      'exact' => $current === $path,
      'prefix' => str_starts_with($current, $path),
      default => false,
  };
  // Phosphor icon for each page.
  $icons = [
      'home' => 'squares-four', 'door' => 'door-open', 'scan' => 'scan', 'radio' => 'broadcast', 'chat' => 'chat-circle-dots',
      'users' => 'users-three', 'ticket' => 'ticket', 'pen' => 'pen-nib', 'send' => 'paper-plane-tilt', 'chart' => 'chart-bar',
      'pulse' => 'pulse', 'download' => 'download-simple', 'calendar' => 'calendar-blank', 'archive' => 'archive', 'card' => 'credit-card',
      'heart' => 'hand-heart', 'plant' => 'plant', 'bubble' => 'chat-teardrop-text', 'shield' => 'shield-check', 'external' => 'arrow-square-out', 'logout' => 'sign-out',
  ];
  $icon = static fn (string $name): string => ph($icons[$name] ?? $name, 'adm-ico');
  // Where we are, for the breadcrumb.
  $crumbGroup = '';
  $crumbPage = $current === '/admin' ? 'Overview' : '';
  foreach ($groups as [$label, $links]) {
      foreach ($links as [$path, $text, $mode]) {
          if ($mode !== 'none' && ($current === $path || ($mode === 'prefix' && str_starts_with($current, $path)))) {
              [$crumbGroup, $crumbPage] = [$label, $text];
          }
      }
  }
  $adminEmail = (string) \App\Core\Session::get('admin_email', '');
  ?>
  <aside class="adm-side" id="adm-side">
    <div class="adm-side__top">
    <a class="adm-brand" href="<?= url('/admin') ?>">
      <img src="<?= asset('img/crest.png') ?>" alt="" width="36" height="26">
      <span>
        <strong>Producers Summit</strong>
        <span class="adm-brand__edition"><?= e((string) config('app.summit.edition')) ?></span>
      </span>
    </a>
    </div>
    <nav class="adm-side__nav" aria-label="Admin">
      <a href="<?= url('/admin') ?>" data-tip="Overview" class="adm-side__link <?= $current === '/admin' ? 'is-active' : '' ?>"<?= $current === '/admin' ? ' aria-current="page"' : '' ?>><?= $icon('home') ?><span>Overview</span></a>
      <?php foreach ($groups as [$label, $links]): ?>
      <div class="adm-side__group">
        <span class="adm-side__label"><?= e($label) ?></span>
        <?php foreach ($links as [$path, $text, $mode, $ico]): $on = $isActive($path, $mode); ?>
        <a href="<?= url($path) ?>" data-tip="<?= e($text) ?>" class="adm-side__link <?= $on ? 'is-active' : '' ?>"<?= $on ? ' aria-current="page"' : '' ?><?= $path === '/admin/export.csv' ? ' data-export-open' : '' ?>><?= $icon($ico) ?><span><?= e($text) ?></span></a>
        <?php endforeach; ?>
      </div>
      <?php endforeach; ?>
    </nav>
    <div class="adm-side__foot">
      <span class="adm-user" title="<?= e($adminEmail) ?>"><span class="adm-user__avatar" aria-hidden="true"><?= e(strtoupper(mb_substr($adminEmail, 0, 1)) ?: 'A') ?></span><span class="adm-user__email"><?= e($adminEmail) ?></span></span>
      <form method="post" action="<?= url('/admin/logout') ?>">
        <?= csrf_field() ?>
        <button type="submit" class="adm-icon-btn" aria-label="Sign out" title="Sign out"><?= $icon('logout') ?></button>
      </form>
    </div>
  </aside>
  <?php endif; ?>

  <div class="adm-body">
  <?php if ($authed): ?>
  <header class="adm-topbar">
    <button type="button" class="adm-menu-btn" aria-controls="adm-side" aria-expanded="false" data-menu>Menu</button>
    <button type="button" class="adm-icon-btn adm-icon-btn--light adm-rail-btn" data-rail aria-pressed="<?= $rail ? 'true' : 'false' ?>" aria-label="<?= $rail ? 'Expand sidebar' : 'Collapse sidebar' ?>" title="<?= $rail ? 'Expand sidebar' : 'Collapse sidebar' ?>"><?= ph('sidebar-simple') ?></button>
    <nav class="adm-crumbs" aria-label="Breadcrumb">
      <a href="<?= url('/admin') ?>">Admin</a>
      <?php if ($crumbGroup !== ''): ?><span aria-hidden="true">/</span><span><?= e($crumbGroup) ?></span><?php endif; ?>
      <?php if ($crumbPage !== ''): ?><span aria-hidden="true">/</span><span class="adm-crumbs__here" aria-current="page"><?= e($crumbPage) ?></span><?php endif; ?>
    </nav>
    <a class="adm-btn adm-btn--quiet" href="<?= url('/') ?>" target="_blank" rel="noopener"><span>View site</span><?= $icon('external') ?></a>
  </header>
  <?php endif; ?>

  <?php if ($authed): ?>
  <dialog class="adm-dialog" data-export-dialog aria-labelledby="export-title">
    <form method="get" action="<?= url('/admin/export.csv') ?>" data-export-form>
      <h2 id="export-title">Export registrations</h2>
      <p>Downloads a CSV of <?= e((string) config('app.summit.edition')) ?>. It holds personal details, so keep it somewhere safe.</p>
      <fieldset class="adm-dialog__choices">
        <legend>Who to include</legend>
        <?php foreach (['' => 'Everyone', 'onsite' => 'Onsite only', 'online' => 'Online only', 'initiative' => 'Initiative only'] as $value => $text): ?>
          <label><input type="radio" name="type" value="<?= e($value) ?>"<?= $value === '' ? ' checked' : '' ?>> <?= e($text) ?></label>
        <?php endforeach; ?>
      </fieldset>
      <div class="adm-dialog__actions">
        <button type="button" class="adm-btn" data-export-cancel>Cancel</button>
        <button type="submit" class="adm-btn adm-btn--dark">Download CSV</button>
      </div>
    </form>
  </dialog>
  <?php endif; ?>

  <main class="adm-main">
    <?= $content ?>
  </main>
  </div>
  <?php if ($authed): ?></div><?php endif; ?>
  <script src="<?= asset('js/notices.js') ?>" defer></script>
  <div class="adm-progress" data-progress aria-hidden="true"></div>
  <div class="adm-tip" data-tip-box aria-hidden="true"></div>
  <script>
    // Fold the sidebar to icons and back; the cookie keeps the choice for a year.
    (function () {
      var btn = document.querySelector('[data-rail]'), shell = document.querySelector('[data-shell]');
      if (!btn || !shell) return;
      btn.addEventListener('click', function () {
        var on = !shell.classList.contains('adm-shell--rail');
        shell.classList.toggle('adm-shell--rail', on);
        btn.setAttribute('aria-pressed', String(on));
        btn.setAttribute('aria-label', on ? 'Expand sidebar' : 'Collapse sidebar');
        btn.title = btn.getAttribute('aria-label');
        document.cookie = 'adm_side=' + (on ? 'rail' : 'open') + '; path=/; max-age=31536000; SameSite=Lax' + (location.protocol === 'https:' ? '; Secure' : '');
      });
    })();

    // Folded rail: name the page beside its icon on hover or keyboard focus.
    (function () {
      var tip = document.querySelector('[data-tip-box]'), shell = document.querySelector('[data-shell]');
      if (!tip || !shell) return;
      function show(e) {
        var a = e.target.closest && e.target.closest('.adm-side__link');
        if (!a || !shell.classList.contains('adm-shell--rail') || window.innerWidth <= 960) return;
        var r = a.getBoundingClientRect();
        tip.textContent = a.getAttribute('data-tip');
        tip.style.left = (r.right + 10) + 'px';
        tip.style.top = (r.top + r.height / 2) + 'px';
        tip.classList.add('is-on');
      }
      function hide() { tip.classList.remove('is-on'); }
      var side = document.getElementById('adm-side');
      side.addEventListener('pointerover', show);
      side.addEventListener('focusin', show);
      side.addEventListener('pointerleave', hide);
      side.addEventListener('focusout', hide);
      side.querySelector('.adm-side__nav').addEventListener('scroll', hide, { passive: true });
    })();

    // Leaving the page: a thin bar across the top, and the button that did it shows it is working.
    (function () {
      var bar = document.querySelector('[data-progress]');
      // Only for slow loads: a page that arrives within 150ms shows no bar at all.
      var timer = 0;
      function start() { if (!bar) return; clearTimeout(timer); timer = setTimeout(function () { bar.classList.add('is-on'); }, 150); }
      document.addEventListener('click', function (e) {
        var a = e.target.closest('a[href]');
        if (!a || e.defaultPrevented || e.metaKey || e.ctrlKey || e.shiftKey || e.button !== 0) return;
        if (a.target === '_blank' || a.hasAttribute('download') || /\.csv|\.png|#/.test(a.getAttribute('href')) || a.origin !== location.origin) return;
        start();
      });
      document.addEventListener('submit', function (e) {
        var f = e.target;
        if (e.defaultPrevented || f.target === '_blank' || /\.csv/.test(f.getAttribute('action') || '') || f.closest('dialog')) return;
        start();
        var b = e.submitter || f.querySelector('[type=submit]');
        if (b) { b.classList.add('is-loading'); b.setAttribute('aria-busy', 'true'); setTimeout(function () { b.disabled = true; }, 0); }
      });
      // Coming back through the history cache must not leave anything spinning.
      window.addEventListener('pageshow', function () {
        clearTimeout(timer); if (bar) bar.classList.remove('is-on');
        document.querySelectorAll('.is-loading').forEach(function (b) { b.classList.remove('is-loading'); b.disabled = false; b.removeAttribute('aria-busy'); });
      });
    })();

    // Export asks who to include before it downloads; the plain link still works without script.
    (function () {
      var dlg = document.querySelector('[data-export-dialog]');
      if (!dlg || !dlg.showModal) return;
      document.querySelectorAll('[data-export-open]').forEach(function (a) {
        a.addEventListener('click', function (e) { e.preventDefault(); document.body.classList.remove('adm-menu-open'); dlg.showModal(); });
      });
      dlg.querySelector('[data-export-cancel]').addEventListener('click', function () { dlg.close(); });
      dlg.querySelector('[data-export-form]').addEventListener('submit', function () { setTimeout(function () { dlg.close(); }, 0); });
      dlg.addEventListener('click', function (e) { if (e.target === dlg) dlg.close(); });
    })();
  </script>
  <script>
    // Phone: the Menu button opens the grouped drawer; any link or the backdrop closes it.
    (function () {
      var btn = document.querySelector('[data-menu]'), side = document.getElementById('adm-side');
      if (!btn || !side) return;
      function set(open) { document.body.classList.toggle('adm-menu-open', open); btn.setAttribute('aria-expanded', String(open)); btn.textContent = open ? 'Close' : 'Menu'; }
      btn.addEventListener('click', function () { set(!document.body.classList.contains('adm-menu-open')); });
      side.addEventListener('click', function (e) { if (e.target.closest('a')) set(false); });
      document.addEventListener('keydown', function (e) { if (e.key === 'Escape') set(false); });
      document.addEventListener('click', function (e) { if (document.body.classList.contains('adm-menu-open') && !side.contains(e.target) && !btn.contains(e.target)) set(false); });
    })();
  </script>
</body>
</html>
