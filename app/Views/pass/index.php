<?php /** @var array $errors */
$errors = $errors ?: \App\Core\Session::get('_errors', []);
?>
<section class="reg">
  <div class="container pay">
    <div class="pay__card">
      <p class="mono pay__kicker"><span class="pay__dot" aria-hidden="true"></span> Attendance desk</p>
      <h1 class="pay__title">Find my pass</h1>
      <?php if ($err = ($errors['auth'] ?? '')): ?>
        <div class="pay__alert" role="alert"><span><?= e($err) ?></span></div>
      <?php endif; ?>
      <p class="pay__lede">Enter the email address or KingsChat username you registered with and your QR pass appears on this screen. Show it at the desk.</p>
      <form class="pay__form" method="post" action="<?= url('/pass') ?>">
        <?= csrf_field() ?>
        <div class="field">
          <label for="identifier">Email or KingsChat username</label>
          <input id="identifier" name="identifier" type="text" inputmode="email" autocapitalize="off" autocorrect="off" autocomplete="email" required value="<?= e(old('identifier')) ?>">
        </div>
        <button type="submit" class="btn btn--stamp btn--lg">
          <span class="btn__label">Show my pass</span>
          <span class="btn__arrow" aria-hidden="true"><?= icon_arrow() ?></span>
        </button>
      </form>
      <p class="pay__fine mono">Not registered yet? <a href="<?= url('/register') ?>">Register here</a>.</p>
    </div>
  </div>
</section>
