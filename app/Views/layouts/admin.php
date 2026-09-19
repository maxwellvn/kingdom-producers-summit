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
  <link rel="stylesheet" href="<?= asset('css/admin.css') ?>">
</head>
<body class="<?= e($bodyClass) ?>">
  <div class="grain" aria-hidden="true"></div>
  <?php if ($authed): ?><div class="adm-shell"><?php endif; ?>

  <?php if ($authed): ?>
  <?php
  // Grouped like an event desk: who is coming, what happens on the day, then the plumbing.
  $groups = [
      ['People', [
          ['/admin/registrations', 'Registrations', 'exact'],
          ['/admin/issue', 'Issue a place', 'exact'],
          ['/admin/scanner', 'Access scanner', 'exact'],
          ['/admin/commitments', 'Commitments', 'prefix'],
      ]],
      ['Event day', [
          ['/admin/stream', 'Stream & holding screen', 'prefix'],
          ['/admin/notifications', 'Notifications', 'prefix'],
      ]],
      ['Insights', [
          ['/admin/analytics', 'Analytics', 'prefix'],
          ['/admin/export.csv', 'Export CSV', 'none'],
      ]],
      ['Settings', [
          ['/admin/payments', 'Support & payments', 'exact'],
          ['/admin/kingschat', 'KingsChat', 'prefix'],
          ['/admin/admins', 'Admin users', 'exact'],
      ]],
  ];
  $isActive = static fn (string $path, string $mode): bool => match ($mode) {
      'exact' => $current === $path,
      'prefix' => str_starts_with($current, $path),
      default => false,
  };
  ?>
  <header class="adm-top">
    <a class="adm-nav__brand" href="<?= url('/admin') ?>">
      <span class="adm-nav__mark">Producers Summit</span>
      <span class="mono adm-nav__sub">Admin</span>
    </a>
    <button type="button" class="adm-menu-btn" aria-controls="adm-side" aria-expanded="false" data-menu>Menu</button>
  </header>
  <aside class="adm-side" id="adm-side">
    <nav class="adm-side__nav" aria-label="Admin">
      <a href="<?= url('/admin') ?>" class="adm-side__link <?= $current === '/admin' ? 'is-active' : '' ?>">Overview</a>
      <?php foreach ($groups as [$label, $links]): ?>
      <div class="adm-side__group">
        <span class="adm-side__label"><?= e($label) ?></span>
        <?php foreach ($links as [$path, $text, $mode]): ?>
        <a href="<?= url($path) ?>" class="adm-side__link <?= $isActive($path, $mode) ? 'is-active' : '' ?>"><?= e($text) ?></a>
        <?php endforeach; ?>
      </div>
      <?php endforeach; ?>
    </nav>
    <div class="adm-side__foot">
      <a href="<?= url('/') ?>" target="_blank" rel="noopener" class="adm-side__link">View site ↗</a>
      <form method="post" action="<?= url('/admin/logout') ?>">
        <?= csrf_field() ?>
        <button type="submit" class="adm-btn adm-btn--ghost">Sign out</button>
      </form>
    </div>
  </aside>
  <?php endif; ?>

  <main class="adm-main">
    <?= $content ?>
  </main>
  <?php if ($authed): ?></div><?php endif; ?>
  <script src="<?= asset('js/notices.js') ?>" defer></script>
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
