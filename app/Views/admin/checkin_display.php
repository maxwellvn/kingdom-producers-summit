<?php /** @var array $summit The desk QR, full screen on a tablet or monitor, or printed. */ ?>
<!DOCTYPE html>
<html lang="en-GB">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="robots" content="noindex, nofollow">
  <title>Scan to check in</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Oswald:wght@500;600;700&family=IBM+Plex+Mono:wght@400;500&display=swap" rel="stylesheet">
  <link href="https://api.fontshare.com/v2/css?f[]=switzer@400,500,600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="<?= asset('css/app.css') ?>">
  <style>
    body { margin: 0; min-height: 100svh; display: grid; place-items: center; background: #FFFFFF; color: var(--ink); text-align: center; }
    main { display: grid; justify-items: center; gap: clamp(1rem, 3vh, 2rem); padding: 4vh 16px; }
    h1 { margin: 0; font-family: var(--font-display); font-size: clamp(2.6rem, 8vw, 6rem); line-height: .9; text-transform: uppercase; letter-spacing: -.01em; }
    p { margin: 0; max-width: 34ch; font-size: clamp(1rem, 2.2vw, 1.4rem); line-height: 1.45; }
    img { width: min(72vw, 58vh); height: auto; aspect-ratio: 1; image-rendering: pixelated; }
    .event { font-family: var(--font-mono); font-size: .9rem; letter-spacing: .14em; text-transform: uppercase; color: var(--muted); }
  </style>
</head>
<body>
  <main>
    <h1>Scan to check in</h1>
    <img src="<?= url('/admin/check-in/qr.png') ?>" alt="QR code for self check-in" width="600" height="600">
    <p>Point your phone camera at the code and tap check in. Not registered yet? You can register there too. Then show the welcome screen at the desk.</p>
    <span class="event"><?= e($summit['short']) ?> · <?= e($summit['edition']) ?></span>
  </main>
</body>
</html>
