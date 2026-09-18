<?php
/** @var bool $live @var string $note @var array $errors @var array $summit @var array $holding */
$errors = $errors ?: \App\Core\Session::get('_errors', []);
?>
<section class="reg">
  <div class="container pay">
    <div class="pay__card">
      <p class="mono pay__kicker">
        <span class="pay__dot <?= $live ? 'is-live' : '' ?>" aria-hidden="true"></span>
        <?= $live ? 'Live now' : e($holding['label']) ?>
      </p>
      <h1 class="pay__title">Watch the summit</h1>

      <?php if ($err = ($errors['auth'] ?? '')): ?>
        <div class="pay__alert" role="alert">
          <strong>We could not let you in.</strong>
          <span><?= e($err) ?></span>
        </div>
      <?php endif; ?>

      <p class="pay__lede">
        Your place is personal to you and can only be open in one place at a time.
        Enter the email address or KingsChat username you registered with.
      </p>

      <form class="pay__form" method="post" action="<?= url('/watch') ?>">
        <?= csrf_field() ?>
        <div class="field">
          <label for="identifier">Email or KingsChat username</label>
          <input id="identifier" name="identifier" type="text" inputmode="email"
                 autocapitalize="off" autocorrect="off" autocomplete="email" required>
          <p class="field__hint">Whichever you gave when you registered.</p>
        </div>
        <button type="submit" class="btn btn--stamp btn--lg">
          <span class="btn__label">Enter the stream</span>
          <span class="btn__arrow" aria-hidden="true"><?= icon_arrow() ?></span>
        </button>
      </form>

      <p class="pay__fine mono">
        <?= e($note) ?>
        Not registered yet? <a href="<?= url('/register') ?>">Register here</a>.
      </p>
    </div>
  </div>
</section>
