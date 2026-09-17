<?php /** @var array $sponsorship */
$s = $sponsorship;
$paid = $s['status'] === 'paid';
?>
<section class="confirmed">
  <div class="confirmed__architecture" aria-hidden="true"></div>
  <div class="container confirmed__intro" data-reveal>
    <p class="mono confirmed__edition"><?= e(config('app.summit.short')) ?> · Sponsorship</p>
    <div class="confirmed__headline">
      <h1 class="confirmed__title">Thank you,<br><?= e(explode(' ', trim($s['name']))[0]) ?>.</h1>
      <p><?= $paid
        ? 'Your gift of £' . number_format($s['amount_pence'] / 100, 2) . ' is confirmed. A receipt from Stripe is on its way to ' . e($s['email']) . '.'
        : 'We have noted your ' . e(amount_for($s['method'], (int) $s['amount_pence'])) . ' gift and will confirm it once it lands, then thank you properly by email.' ?></p>
    </div>
  </div>
  <div class="container confirmed__grid">
    <aside class="confirmed__next" data-reveal>
      <span class="mono confirmed__section-label">What it funds</span>
      <h2>The room, the stream, the ninety days after.</h2>
      <div class="confirmed__actions">
        <a class="btn btn--ink" href="<?= url('/register') ?>"><span class="btn__label">Register for the summit</span><span class="btn__arrow" aria-hidden="true"><?= icon_arrow() ?></span></a>
        <a class="btn btn--outline" href="<?= url('/') ?>"><span class="btn__label">Back to the summit</span></a>
      </div>
    </aside>
  </div>
</section>
