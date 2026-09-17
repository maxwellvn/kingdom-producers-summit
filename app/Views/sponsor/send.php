<?php /** @var array $sponsorship @var string $espeesCode @var string $revolutUrl */
$s = $sponsorship;
$isEspees = $s['method'] === 'espees';
$amount = amount_for($isEspees ? 'espees' : 'revolut', (int) $s['amount_pence']);
?>
<section class="reg">
  <div class="container pay">
    <div class="pay__card">
      <p class="mono pay__kicker"><?= payment_icon($s['method'], 22) ?> <?= $isEspees ? 'Send with Espees' : 'Pay via Revolut' ?></p>
      <h1 class="pay__title"><?= $isEspees ? 'Send ' : 'Pay ' ?><?= e($amount) ?></h1>
      <?php if ($isEspees): ?>
        <p class="pay__lede">Open your Espees wallet and send <strong><?= e($amount) ?></strong> to this merchant code:</p>
        <div class="sponsor__espees sponsor__espees--inline">
          <strong class="sponsor__code" id="espeesCode"><?= e($espeesCode) ?></strong>
          <button type="button" class="confirmed__copy mono" data-copy="#espeesCode">Copy code</button>
        </div>
      <?php else: ?>
        <p class="pay__lede">Revolut's checkout opens in a new tab. Enter <strong><?= e($amount) ?></strong> as the amount, then come back here.</p>
        <a class="btn btn--stamp btn--lg" style="display:inline-block" href="<?= e($revolutUrl) ?>" target="_blank" rel="noopener noreferrer"><span class="btn__label">Open Revolut checkout</span></a>
      <?php endif; ?>
      <form method="post" action="<?= url('/sponsor/send') ?>" class="sponsor__claim">
        <?= csrf_field() ?>
        <p class="form__fine">Put <strong><?= e($s['name']) ?></strong> as the reference where you can, so we can match it.</p>
        <button type="submit" class="btn btn--ink btn--lg"><span class="btn__label">I have sent it</span><span class="btn__arrow" aria-hidden="true"><?= icon_arrow() ?></span></button>
      </form>
      <p class="form__fine mono"><a href="<?= url('/sponsor') ?>">Choose a different way to give</a></p>
    </div>
  </div>
</section>
