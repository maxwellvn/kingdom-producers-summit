<?php
/** @var array $summit @var string $reference @var string $identifier @var bool $cancelled @var bool $expired @var string $error */
?>
<section class="reg">
  <div class="container pay">
    <div class="pay__card">
      <p class="mono pay__kicker"><span class="pay__dot" aria-hidden="true"></span> Optional contribution</p>
      <h1 class="pay__title">Support the programme</h1>

      <?php if (!empty($expired)): ?>
        <div class="pay__alert" role="status">
          <strong>That link had expired.</strong>
          <span>Nothing is lost. Enter the email address or KingsChat username you registered with to carry on.</span>
        </div>
      <?php elseif ($cancelled && $error === ''): ?>
        <div class="pay__alert" role="status">
          <strong>The contribution wasn't completed.</strong>
          <span>Your place is confirmed regardless. You can try again below, or leave it.</span>
        </div>
      <?php elseif ($error !== ''): ?>
        <div class="pay__alert" role="alert">
          <strong>Something needs attention.</strong>
          <span><?= e($error) ?></span>
        </div>
      <?php elseif (!empty($savedRegistration)): ?>
        <p class="pay__lede">Your place is confirmed. Continue below if you would like to contribute to the programme.</p>
      <?php else: ?>
        <p class="pay__lede">Enter the email address or KingsChat username you registered with, and we will take you to the ways to give.</p>
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
          <span class="btn__label">Continue</span>
          <span class="btn__arrow" aria-hidden="true"><?= icon_arrow() ?></span>
        </button>
        <p class="form__fine mono">We never see your card details. Contributing is optional.</p>
      </form>

      <p class="pay__fine mono">Not registered yet? <a href="<?= url('/register') ?>">Register here</a> — attending is free.</p>
    </div>
  </div>
</section>
