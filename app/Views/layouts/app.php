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
  <meta name="description" content="The Loveworld Kingdom Producers Summit, London Edition 2026. From consumers to producers — whatever your age, whatever your field. Register to attend, follow online, or join the Kingdom Producers initiative.">
  <meta name="theme-color" content="#F3EEE3">
  <meta property="og:title" content="<?= e($title) ?>">
  <meta property="og:description" content="From consumers to producers. 100 producers → 1,000 UK → 10,000 global. Organised by Loveworld Consulate UK.">
  <meta property="og:type" content="website">
  <link rel="icon" type="image/png" href="<?= e(asset('img/favicon.png')) ?>">
  <link rel="apple-touch-icon" href="<?= e(asset('img/apple-touch-icon.png')) ?>">

  <script>
    (function () {
      var reloaded = performance.getEntriesByType && performance.getEntriesByType('navigation')[0]?.type === 'reload';
      if (reloaded) document.documentElement.classList.add('intro-pending');
    })();
  </script>

  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link rel="preconnect" href="https://api.fontshare.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Oswald:wght@400;500;600;700&family=IBM+Plex+Mono:ital,wght@0,400;0,500;1,400&family=Caveat:wght@500;600&display=swap" rel="stylesheet">
  <link href="https://api.fontshare.com/v2/css?f[]=switzer@400,500,600&display=swap" rel="stylesheet">

  <link rel="stylesheet" href="<?= asset('css/app.css') ?>">
</head>
<body class="<?= e($bodyClass) ?>">
  <div class="site-intro" id="siteIntro" aria-label="Loveworld Kingdom Producers Summit introduction">
    <video class="site-intro__video" autoplay muted playsinline preload="auto" aria-hidden="true">
      <source src="<?= e(asset('media/producers-opening-ident-higgsfield-trimmed-v1.mp4')) ?>" type="video/mp4">
    </video>
  </div>

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

  <div class="video-modal" id="videoModal" hidden>
    <div class="video-modal__backdrop" data-video-close></div>
    <figure class="video-modal__frame" role="dialog" aria-modal="true" aria-label="Video player">
      <video id="videoModalPlayer" controls preload="metadata"></video>
      <button class="video-modal__close" type="button" data-video-close aria-label="Close video">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" aria-hidden="true"><path d="M18 6L6 18"/><path d="M6 6l12 12"/></svg>
      </button>
    </figure>
  </div>

  <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/gsap.min.js" defer></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/ScrollTrigger.min.js" defer></script>
  <script src="<?= asset('js/app.js') ?>" defer></script>
</body>
</html>
