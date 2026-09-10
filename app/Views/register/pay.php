<?php
/** @var array $summit @var string $reference @var string $identifier @var bool $cancelled @var bool $expired @var string $error */
?>
<section class="reg">
  <div class="container pay">
    <div class="pay__card">
      <p class="mono pay__kicker"><span class="pay__dot" aria-hidden="true"></span> Payment step</p>
      <h1 class="pay__title">Complete your place</h1>

      <?php if (!empty($expired)): ?>
        <div class="pay__alert" role="status">
          <strong>Your payment session timed out.</strong>
          <span>Nothing is lost. Enter the email address or KingsChat username you registered with to pick up where you left off.</span>
        </div>
      <?php elseif ($cancelled && $error === ''): ?>
        <div class="pay__alert" role="status">
          <strong>Payment wasn't completed.</strong>
          <span>Your registration is saved — pick up where you left off below.</span>
        </div>
      <?php elseif ($error !== ''): ?>
        <div class="pay__alert" role="alert">
          <strong>Something needs attention.</strong>
          <span><?= e($error) ?></span>
        </div>
      <?php elseif (!empty($savedRegistration)): ?>
        <p class="pay__lede">Your registration is saved. Continue below to complete payment for your onsite place.</p>
      <?php else: ?>
        <p class="pay__lede">Your details are already with us. Enter the email address or KingsChat username you registered with to complete your payment.</p>
      <?php endif; ?>

      <form class="pay__form" method="post" action="<?= url('/register/pay') ?>">
        <?= csrf_field() ?>
        <?php if (!empty($savedRegistration)): ?>
        <p class="pay__ref"><span class="mono">Registration reference</span><strong><?= e($reference) ?></strong></p>
        <input type="hidden" name="reference" value="<?= e($reference) ?>">
        <?php else: ?>
        <div class="field">
          <label for="identifier">Email or KingsChat username</label>
          <input id="identifier" name="identifier" type="text" inputmode="email"
                 autocapitalize="off" autocorrect="off" autocomplete="email"
                 value="<?= e($identifier ?? '') ?>" required>
          <p class="field__hint">Whichever you gave when you registered.</p>
        </div>
        <?php endif; ?>
        <button type="submit" class="btn btn--stamp btn--lg">
          <span class="btn__label">Continue to payment</span>
          <span class="btn__arrow" aria-hidden="true"><?= icon_arrow() ?></span>
        </button>
        <p class="form__fine mono">We never see your card details.</p>
      </form>

      <p class="pay__fine mono">Not registered yet? <a href="<?= url('/register') ?>">Register here</a> — joining the initiative is free.</p>
    </div>
  </div>
</section>
