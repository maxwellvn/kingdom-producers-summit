<?php
/** @var array $registration @var array $summit @var string $accessToken */
$r = $registration;
$pathLabel = ['onsite' => 'Attending in London', 'online' => 'Following online', 'initiative' => 'Kingdom Producers member'][$r['participation']] ?? $r['participation'];
$next = [
  'onsite' => [
    'We will email the confirmed London date and venue to you before it is announced publicly.',
    'Your reference code is your ticket reference. Keep it — you will be asked for it at registration on the day.',
    'The programme, travel notes and any invitation letter you requested will follow by email.',
  ],
  'online' => [
    'You will receive the programme and speaker announcements as they are confirmed.',
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
  <div class="container confirmed__grid">
    <div class="confirmed__card" data-reveal>
      <span class="tape tape--mustard confirmed__tape" aria-hidden="true"></span>
      <div class="confirmed__stamp" aria-hidden="true"><span>Registered</span></div>

      <p class="eyebrow"><span class="eyebrow__dot"></span><?= e($summit['short']) ?> <span class="eyebrow__sep">/</span> <?= e($summit['edition']) ?></p>
      <h1 class="confirmed__title">You're in,<br><?= e($r['first_name']) ?>.</h1>

      <div class="confirmed__ref">
        <span class="mono confirmed__ref-label">Reference</span>
        <span class="confirmed__ref-code" id="refCode"><?= e($r['reference']) ?></span>
        <button type="button" class="confirmed__copy mono" data-copy="#refCode">Copy</button>
      </div>

      <section class="confirmed__pass" aria-labelledby="access-pass-title">
        <div class="confirmed__pass-copy">
          <span class="mono">Access pass</span>
          <h2 id="access-pass-title">Show this code at the entrance.</h2>
          <p>The attendance desk will scan it once to confirm your arrival.</p>
        </div>
        <div class="confirmed__qr" id="accessQr" data-qr-value="<?= e($accessToken) ?>" aria-label="QR access code for <?= e($r['reference']) ?>"></div>
      </section>

      <dl class="confirmed__facts mono">
        <div><dt>Path</dt><dd><?= e($pathLabel) ?></dd></div>
        <div><dt>Name</dt><dd><?= e(trim(($r['title'] ?? '') . ' ' . $r['first_name'] . ' ' . $r['last_name'])) ?></dd></div>
        <div><dt>Email</dt><dd><?= e($r['email']) ?></dd></div>
        <div><dt>Field</dt><dd><?= e($r['field']) ?></dd></div>
        <div><dt>Stage</dt><dd><?= e(ucfirst($r['producer_stage'])) ?></dd></div>
        <div><dt>Registered</dt><dd><?= e(date('j M Y, H:i', strtotime($r['created_at']))) ?></dd></div>
      </dl>
    </div>

    <div class="confirmed__next" data-reveal>
      <p class="eyebrow"><span class="eyebrow__dot"></span>What happens next</p>
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

      <p class="confirmed__fine mono">Something wrong? Email <a href="mailto:<?= e(config('app.mail.from')) ?>"><?= e(config('app.mail.from')) ?></a> quoting your reference.</p>
    </div>
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
