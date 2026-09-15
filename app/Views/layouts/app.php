<?php
/** @var string $content */
$title = $title ?? config('app.name');
$bodyClass = $bodyClass ?? '';
$summit = $summit ?? config('app.summit');
?>
<!DOCTYPE html>
<html lang="en-GB">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= e($title) ?></title>
<?php
  $summit = (array) config('app.summit');
  $canonical = rtrim(site_url(), '/') . \App\Core\Url::currentPath();
  $pageDescription = $description ?? (
      $summit['edition'] . ' of the Loveworld Kingdom Producers Summit. '
      . $summit['date_day'] . ' in ' . $summit['city'] . '. '
      . 'From consumers to producers, whatever your age and whatever your field: attend in person, watch online, or join the Kingdom Producers initiative.'
  );
  // asset() already carries the base path, so only the scheme and host are added.
  $origin = preg_replace('#(https?://[^/]+).*#', '$1', site_url()) ?: '';
  $shareImage = $origin . asset('img/producers-hero-v3.jpg');
  // Pages behind a gate or personal to one person should not be indexed.
  $private = $noIndex ?? false;
?>
  <meta name="description" content="<?= e($pageDescription) ?>">
  <meta name="theme-color" content="#F3EEE3">
  <link rel="canonical" href="<?= e($canonical) ?>">
  <?php if ($private): ?>
    <meta name="robots" content="noindex, nofollow">
  <?php else: ?>
    <meta name="robots" content="index, follow, max-image-preview:large">
  <?php endif; ?>

  <meta property="og:site_name" content="<?= e(config('app.name')) ?>">
  <meta property="og:locale" content="en_GB">
  <meta property="og:type" content="website">
  <meta property="og:url" content="<?= e($canonical) ?>">
  <meta property="og:title" content="<?= e($title) ?>">
  <meta property="og:description" content="<?= e($pageDescription) ?>">
  <meta property="og:image" content="<?= e($shareImage) ?>">
  <meta property="og:image:alt" content="Producers at work during the Loveworld Kingdom Producers Summit">

  <meta name="twitter:card" content="summary_large_image">
  <meta name="twitter:title" content="<?= e($title) ?>">
  <meta name="twitter:description" content="<?= e($pageDescription) ?>">
  <meta name="twitter:image" content="<?= e($shareImage) ?>">

  <link rel="icon" type="image/png" href="<?= e(asset('img/favicon.png')) ?>">
  <link rel="apple-touch-icon" href="<?= e(asset('img/apple-touch-icon.png')) ?>">

  <?php if (($bodyClass ?? '') === 'page-home'): ?>
    <?php
    // Structured data lets search engines show the date, place and price.
    $eventStart = '2026-09-19T12:00:00+01:00';
    $schema = [
        '@context' => 'https://schema.org',
        '@type' => 'Event',
        'name' => config('app.name') . ' — ' . $summit['edition'],
        'description' => $pageDescription,
        'startDate' => $eventStart,
        'eventAttendanceMode' => 'https://schema.org/MixedEventAttendanceMode',
        'eventStatus' => 'https://schema.org/EventScheduled',
        'image' => [$shareImage],
        'url' => $canonical,
        'location' => [
            ['@type' => 'Place', 'name' => $summit['city'],
             'address' => ['@type' => 'PostalAddress', 'addressLocality' => 'Rainham',
                           'addressRegion' => 'Essex', 'addressCountry' => 'GB']],
            ['@type' => 'VirtualLocation', 'url' => $origin . url('/watch')],
        ],
        'organizer' => [
            '@type' => 'Organization',
            'name' => $summit['organiser'],
            'url' => $origin . url('/'),
        ],
        'isAccessibleForFree' => true,
        'offers' => [
            ['@type' => 'Offer', 'name' => 'Onsite place', 'price' => '0', 'priceCurrency' => 'GBP',
             'availability' => 'https://schema.org/InStock', 'url' => $origin . url('/register') . '?mode=onsite',
             'validFrom' => '2026-01-01T00:00:00+00:00'],
            ['@type' => 'Offer', 'name' => 'Online place', 'price' => '0', 'priceCurrency' => 'GBP',
             'availability' => 'https://schema.org/InStock', 'url' => $origin . url('/register') . '?mode=online',
             'validFrom' => '2026-01-01T00:00:00+00:00'],
        ],
    ];
    ?>
    <script type="application/ld+json"><?= json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?></script>
  <?php endif; ?>

<?php $showIntro = $bodyClass === 'page-home'; ?>
<?php if ($showIntro): ?>
  <script>
    (function () {
      var nav = performance.getEntriesByType && performance.getEntriesByType('navigation')[0];
      var firstVisit = !sessionStorage.getItem('introSeen');
      // ponytail: hard reload bypasses cache, so the document is fetched fresh (transferSize > 0)
      var hardReload = nav && nav.type === 'reload' && nav.transferSize > 0;
      if (firstVisit || hardReload) {
        document.documentElement.classList.add('intro-pending');
        try { sessionStorage.setItem('introSeen', '1'); } catch (e) {}
      }
    })();
  </script>
<?php endif; ?>

  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link rel="preconnect" href="https://api.fontshare.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Oswald:wght@400;500;600;700&family=IBM+Plex+Mono:ital,wght@0,400;0,500;1,400&family=Caveat:wght@500;600&display=swap" rel="stylesheet">
  <link href="https://api.fontshare.com/v2/css?f[]=switzer@400,500,600&display=swap" rel="stylesheet">

  <link rel="stylesheet" href="<?= asset('css/app.css') ?>">
</head>
<body class="<?= e($bodyClass) ?>"
      data-presence="<?= e($presenceContext ?? 'site') ?>"
      data-presence-url="<?= e(url('/api/presence')) ?>"
      data-presence-leave="<?= e(url('/api/presence/leave')) ?>"
      data-analytics-choice="<?= e(url('/api/analytics-choice')) ?>">
  <?php if ($showIntro): ?>
    <div class="site-intro" id="siteIntro" aria-label="Loveworld Kingdom Producers Summit introduction">
      <video class="site-intro__video" autoplay muted playsinline preload="auto" aria-hidden="true">
        <source src="<?= e(asset('media/producers-opening-ident-higgsfield-trimmed-v1.mp4')) ?>" type="video/mp4">
      </video>
      <button type="button" class="site-intro__skip mono" data-intro-skip>Skip</button>
    </div>
  <?php endif; ?>

  <a class="skip-link" href="#main">Skip to content</a>
  <div class="grain" aria-hidden="true"></div>

  <?= \App\Core\View::partial('partials/nav') ?>

  <main id="main">
    <?= $content ?>
  </main>

  <?= \App\Core\View::partial('partials/footer', ['summit' => $summit]) ?>

  <aside class="cookie-banner" data-cookie-banner data-consent-endpoint="<?= e(url('api/consent')) ?>" aria-label="Cookie preferences" hidden>
    <div class="cookie-banner__top">
      <span class="cookie-banner__icon" aria-hidden="true">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M21.1 12.1A9 9 0 1 1 11.9 2.9a3.4 3.4 0 0 0 4.1 4.1 3.4 3.4 0 0 0 5.1 5.1z"/><circle cx="9" cy="9.5" r=".6" fill="currentColor" stroke="none"/><circle cx="8.2" cy="14.4" r=".6" fill="currentColor" stroke="none"/><circle cx="13" cy="13.2" r=".6" fill="currentColor" stroke="none"/><circle cx="12.2" cy="17.6" r=".6" fill="currentColor" stroke="none"/></svg>
      </span>
      <p class="cookie-banner__title">Cookies &amp; storage</p>
    </div>
    <p class="cookie-banner__text">We use essential storage to run this site. Choose which optional categories you're happy with — you can change your mind anytime.</p>
    <div class="cookie-banner__actions">
      <button type="button" class="btn btn--ink" data-cookie-choice="accepted"><span class="btn__label">Accept all</span></button>
      <button type="button" class="cookie-banner__reject mono" data-cookie-choice="rejected">Essential only</button>
      <button type="button" class="cookie-banner__reject mono" data-cookie-customize aria-expanded="false" aria-controls="cookiePrefs">Customize</button>
    </div>
    <div class="cookie-banner__prefs" id="cookiePrefs">
      <div class="cookie-banner__prefs-inner">
        <div class="cookie-banner__cats">
          <div class="cookie-switch cookie-switch--locked">
            <span class="cookie-switch__label">Essential<small>Always on — needed for the site to work</small></span>
            <span class="cookie-switch__track is-on" aria-hidden="true"><span class="cookie-switch__thumb"></span></span>
          </div>
          <label class="cookie-switch">
            <span class="cookie-switch__label">Preferences<small>Remember your choices and settings</small></span>
            <input type="checkbox" class="cookie-switch__input" data-cookie-toggle="preferences" checked>
            <span class="cookie-switch__track" aria-hidden="true"><span class="cookie-switch__thumb"></span></span>
          </label>
          <label class="cookie-switch">
            <span class="cookie-switch__label">Analytics<small>Help us understand how the site is used</small></span>
            <input type="checkbox" class="cookie-switch__input" data-cookie-toggle="analytics">
            <span class="cookie-switch__track" aria-hidden="true"><span class="cookie-switch__thumb"></span></span>
          </label>
          <label class="cookie-switch">
            <span class="cookie-switch__label">Marketing<small>Personalised event updates and campaigns</small></span>
            <input type="checkbox" class="cookie-switch__input" data-cookie-toggle="marketing">
            <span class="cookie-switch__track" aria-hidden="true"><span class="cookie-switch__thumb"></span></span>
          </label>
        </div>
        <button type="button" class="btn btn--ink cookie-banner__save" data-cookie-choice="custom"><span class="btn__label">Save choices</span></button>
      </div>
    </div>
  </aside>

  <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/gsap.min.js" defer></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/ScrollTrigger.min.js" defer></script>
  <?php if (($presenceContext ?? '') === 'watch'): ?>
    <!-- Only the stream page needs an HLS player. -->
    <script src="https://cdn.jsdelivr.net/npm/hls.js@1.5.17/dist/hls.min.js" defer></script>
  <?php endif; ?>
  <script src="<?= asset('js/app.js') ?>" defer></script>
  <script src="<?= asset('js/notices.js') ?>" defer></script>
</body>
</html>
