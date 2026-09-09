<?php
/** @var array $registration @var array $summit @var string $accessToken */
$r = $registration;
$pathLabel = ['onsite' => 'Attending in London', 'online' => 'Attending online — Rainham, Essex', 'initiative' => 'Kingdom Producers member'][$r['participation']] ?? $r['participation'];
$next = [
  'onsite' => [
    'Join us on ' . ($summit['date_text'] ?? '19th September 2026, 12 noon') . ' — arrival details and the full programme will follow by email.',
    'Your reference code is your ticket reference. Keep it — you will be asked for it at registration on the day.',
    'The programme, travel notes and any invitation letter you requested will follow by email.',
  ],
  'online' => [
    'Join the live stream from Rainham, Essex on ' . ($summit['date_text'] ?? '19th September 2026, 12 noon') . ' — your access link arrives by email.',
    'Session recordings and producer resources will be sent after the summit.',
    'If you decide to attend in person later, reply to any of our emails and we will switch you over.',
  ],
  'initiative' => [
    'You are now on the register of Kingdom Producers. Your reference is your member reference.',
    'You will be among the first to receive access to the portal as it opens — the repository, directory and opportunities.',
    'Summit updates come to you too; if you want to attend in London, tell us by replying to any email.',
  ],
][$r['participation']] ?? [];
?>

<section class="confirmed">
  <div class="confirmed__architecture" aria-hidden="true"></div>
  <div class="container confirmed__intro" data-reveal>
    <p class="mono confirmed__edition"><?= e($summit['short']) ?> · <?= e($summit['edition']) ?></p>
    <div class="confirmed__headline">
      <h1 class="confirmed__title">You're in,<br><?= e($r['first_name']) ?>.</h1>
      <p>Your place is registered. Keep this page or save your reference—the access pass is ready when you arrive.</p>
    </div>
  </div>

  <div class="container confirmed__grid">
    <article class="confirmed__ticket" data-reveal aria-labelledby="access-pass-title">
      <header class="confirmed__ticket-head">
        <div>
          <span class="mono">Official access pass</span>
          <h2 id="access-pass-title"><?= e($pathLabel) ?></h2>
        </div>
        <div class="confirmed__stamp" aria-hidden="true"><span>Registered</span></div>
      </header>

      <div class="confirmed__pass">
        <div class="confirmed__qr-wrap">
          <div class="confirmed__qr" id="accessQr" data-qr-value="<?= e($accessToken) ?>" aria-label="QR access code for <?= e($r['reference']) ?>"></div>
          <span class="mono">Scan once at entry</span>
        </div>
        <div class="confirmed__pass-copy">
          <span class="mono">Registration reference</span>
          <strong class="confirmed__ref-code" id="refCode"><?= e($r['reference']) ?></strong>
          <button type="button" class="confirmed__copy mono" data-copy="#refCode">Copy reference</button>
          <p>Present this QR code to the attendance team. It confirms your arrival without exposing your registration details.</p>
        </div>
      </div>

      <dl class="confirmed__facts mono">
        <div><dt>Name</dt><dd><?= e(trim(($r['title'] ?? '') . ' ' . $r['first_name'] . ' ' . $r['last_name'])) ?></dd></div>
        <div><dt>Email</dt><dd><?= e($r['email']) ?></dd></div>
        <div><dt>Field</dt><dd><?= e($r['field']) ?></dd></div>
        <div><dt>Stage</dt><dd><?= e(ucfirst($r['producer_stage'])) ?></dd></div>
        <?php if ($r['participation'] === 'onsite' && ($r['payment_status'] ?? '') === 'paid'): ?>
          <div><dt>Paid</dt><dd>&pound;<?= number_format((int) $r['payment_amount'] / 100, 2) ?></dd></div>
        <?php endif; ?>
        <div><dt>Registered</dt><dd><?= e(date('j M Y, H:i', strtotime($r['created_at']))) ?></dd></div>
      </dl>
    </article>

    <aside class="confirmed__next" data-reveal>
      <span class="mono confirmed__section-label">What happens next</span>
      <h2>From registration to arrival.</h2>
      <ol class="next__list">
        <?php foreach ($next as $i => $line): ?>
          <li>
            <span class="mono next__num"><?= str_pad((string) ($i + 1), 2, '0', STR_PAD_LEFT) ?></span>
            <p><?= e($line) ?></p>
          </li>
        <?php endforeach; ?>
      </ol>

      <div class="confirmed__actions">
        <a class="btn btn--ink" href="<?= url('/about') ?>"><span class="btn__label">Read about the initiative</span><span class="btn__arrow" aria-hidden="true"><?= icon_arrow() ?></span></a>
        <a class="btn btn--outline" href="<?= url('/') ?>"><span class="btn__label">Back to the summit</span></a>
      </div>

      <p class="confirmed__fine mono">Something wrong? Email <a href="mailto:<?= e(config('app.mail.reply_to')) ?>"><?= e(config('app.mail.reply_to')) ?></a> quoting your reference.</p>
    </aside>
  </div>
</section>

<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
<script>
(function () {
  var el = document.getElementById('accessQr');
  if (!el || !window.QRCode) return;
  new QRCode(el, {text: el.dataset.qrValue, width: 196, height: 196, colorDark: '#1b2242', colorLight: '#f3eee2', correctLevel: QRCode.CorrectLevel.H});
})();
</script>
