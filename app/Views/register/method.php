<?php /** @var array $summit @var array $registration @var array $methods @var string $amount */
?>
<section class="reg">
  <div class="container pay">
    <div class="pay__card">
      <p class="mono pay__kicker"><span class="pay__dot" aria-hidden="true"></span> Optional contribution</p>
      <h1 class="pay__title">Support the programme</h1>
      <p class="pay__lede">Your place is confirmed. If you would like to help fund the summit and the initiative, a contribution of <strong><?= e($amount) ?> Espees</strong> is suggested. Choose how you would like to give. Registration reference <strong class="mono"><?= e($registration['reference']) ?></strong>.</p>

      <?php $payError = \App\Core\Session::get('_errors', [])['pay'] ?? ''; ?>
      <?php if ($payError !== ''): ?>
        <div class="pay__alert" role="alert"><span><?= e($payError) ?></span></div>
      <?php endif; ?>
      <?php if (!$methods): ?>
        <div class="pay__alert" role="status">
          <strong>Contributions are not open yet.</strong>
          <span>Your place is confirmed regardless. We will let you know when giving opens.</span>
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

      <p class="form__fine mono">Not today? <a href="<?= url('/register/confirmed') ?>">Back to your registration</a>. Your place is not affected either way.</p>
    </div>
  </div>
</section>
