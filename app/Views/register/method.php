<?php /** @var array $summit @var array $registration @var array $methods @var string $amount */
$standard = espees_price(standard_price_pence((string) $registration['participation']));
?>
<section class="reg">
  <div class="container pay">
    <div class="pay__card">
      <p class="mono pay__kicker"><span class="pay__dot" aria-hidden="true"></span> Payment step</p>
      <h1 class="pay__title">Choose how to pay <?= e($amount) ?> Espees</h1>
      <p class="pay__lede">The full price is <?= e($standard) ?>. The inaugural edition price leaves <strong><?= e($amount) ?> Espees</strong> due. Registration reference <strong class="mono"><?= e($registration['reference']) ?></strong>.</p>

      <?php $payError = \App\Core\Session::get('_errors', [])['pay'] ?? ''; ?>
      <?php if ($payError !== ''): ?>
        <div class="pay__alert" role="alert"><span><?= e($payError) ?></span></div>
      <?php endif; ?>
      <?php if (!$methods): ?>
        <div class="pay__alert" role="alert">
          <strong>Payment is not available yet.</strong>
          <span>Your registration is saved. Please contact the organisers to complete payment; you do not need to register again.</span>
        </div>
      <?php endif; ?>

      <div class="pay__methods">
        <?php foreach ($methods as $id => $method): ?>
          <?php $target = $id === 'stripe'
              ? url('/register/stripe?resume=' . rawurlencode($resume))
              : url('/register/instructions?type=' . $method['href'] . '&resume=' . rawurlencode($resume)); ?>
          <a class="pay__method" href="<?= $target ?>">
            <span class="pay__method-icon"><?= payment_icon((string) $id) ?></span>
            <div>
              <strong><?= e($method['label']) ?></strong>
              <span class="pay__method-blurb"><?= e($method['blurb']) ?></span>
            </div>
            <span class="mono">Continue &rarr;</span>
          </a>
        <?php endforeach; ?>
      </div>

      <p class="form__fine mono">Keep your registration reference — it identifies every payment.</p>
    </div>
  </div>
</section>
