<?php /** @var array $summit @var array $registration @var array $methods @var string $amount */
$methodLabels = ['espees' => 'Espees', 'paypal' => 'PayPal', 'bank' => 'Bank transfer'];
?>
<section class="reg">
  <div class="container pay">
    <div class="pay__card">
      <p class="mono pay__kicker"><span class="pay__dot" aria-hidden="true"></span> Payment step</p>
      <h1 class="pay__title">Choose how to pay <?= e($amount) ?> Espees</h1>
      <p class="pay__lede">The standard price is 100 Espees. Your 50 Espees discount leaves <strong><?= e($amount) ?> Espees</strong> due. Registration reference <strong class="mono"><?= e($registration['reference']) ?></strong>.</p>

      <?php if (!$methods): ?>
        <div class="pay__alert" role="alert">
          <strong>Online payment is not available yet.</strong>
          <span>Your registration is saved. Please contact the organisers to complete payment; you do not need to register again.</span>
        </div>
      <?php endif; ?>

      <div class="pay__methods">
        <?php foreach ($methods as $id => $method): ?>
          <?php if ($id === 'paypal'): ?>
            <form method="post" action="<?= url('/register/pay/checkout') ?>" class="pay__method">
              <?= csrf_field() ?>
              <div>
                <strong><?= e($method['label']) ?></strong>
                <span class="pay__method-blurb"><?= e($method['blurb']) ?></span>
              </div>
              <button type="submit" class="btn btn--stamp"><span class="btn__label">Continue to PayPal</span></button>
            </form>
          <?php else: ?>
            <a class="pay__method" href="<?= url('/register/instructions?type=' . $method['href']) ?>">
              <div>
                <strong><?= e($method['label']) ?></strong>
                <span class="pay__method-blurb"><?= e($method['blurb']) ?></span>
              </div>
              <span class="mono">Pay with <?= e($method['label']) ?> &rarr;</span>
            </a>
          <?php endif; ?>
        <?php endforeach; ?>
      </div>

      <p class="form__fine mono">Keep your registration reference — it identifies every payment.</p>
    </div>
  </div>
</section>
