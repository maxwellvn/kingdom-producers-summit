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

  <?php if ($authed): ?>
  <header class="adm-nav">
    <div class="adm-nav__inner">
      <a class="adm-nav__brand" href="<?= url('/admin') ?>">
        <span class="adm-nav__mark">Producers Summit</span>
        <span class="mono adm-nav__sub">Registrations desk</span>
      </a>
      <nav class="adm-nav__links">
        <a href="<?= url('/admin') ?>" class="<?= $current === '/admin' ? 'is-active' : '' ?>">Overview</a>
        <a href="<?= url('/admin/registrations') ?>" class="<?= $current === '/admin/registrations' ? 'is-active' : '' ?>">Registrations</a>
        <a href="<?= url('/admin/analytics') ?>" class="<?= str_starts_with($current, '/admin/analytics') ? 'is-active' : '' ?>">Analytics</a>
        <a href="<?= url('/admin/stream') ?>" class="<?= str_starts_with($current, '/admin/stream') ? 'is-active' : '' ?>">Stream</a>
        <a href="<?= url('/admin/issue') ?>" class="<?= $current === '/admin/issue' ? 'is-active' : '' ?>">Issue a place</a>
        <a href="<?= url('/admin/scanner') ?>" class="<?= $current === '/admin/scanner' ? 'is-active' : '' ?>">Access scanner</a>
        <a href="<?= url('/admin/payments') ?>" class="<?= $current === '/admin/payments' ? 'is-active' : '' ?>">Payments</a>
        <a href="<?= url('/admin/kingschat') ?>" class="<?= str_starts_with($current, '/admin/kingschat') ? 'is-active' : '' ?>">KingsChat</a>
        <a href="<?= url('/admin/admins') ?>" class="<?= $current === '/admin/admins' ? 'is-active' : '' ?>">Admin users</a>
        <a href="<?= url('/admin/export.csv') ?>">Export CSV</a>
        <a href="<?= url('/') ?>" target="_blank" rel="noopener">View site ↗</a>
      </nav>
      <form method="post" action="<?= url('/admin/logout') ?>" class="adm-nav__logout">
        <?= csrf_field() ?>
        <button type="submit" class="adm-btn adm-btn--ghost">Sign out</button>
      </form>
    </div>
  </header>
  <?php endif; ?>

  <main class="adm-main">
    <?= $content ?>
  </main>
  <script src="<?= asset('js/notices.js') ?>" defer></script>
</body>
</html>
