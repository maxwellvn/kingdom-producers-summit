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

  <aside class="cookie-banner" data-cookie-banner aria-label="Cookie preferences" hidden>
    <p><strong>Cookie preferences</strong><span>We only use essential storage for your preferences and this introduction.</span></p>
    <div class="cookie-banner__actions">
      <button type="button" class="btn btn--ink" data-cookie-choice="accepted"><span class="btn__label">Accept</span></button>
      <button type="button" class="cookie-banner__reject mono" data-cookie-choice="rejected">Essential only</button>
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
