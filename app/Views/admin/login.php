<section class="adm-login">
  <form class="adm-login__card" method="post" action="<?= url('/admin/login') ?>">
    <?= csrf_field() ?>
    <span class="tape tape--mustard adm-login__tape" aria-hidden="true"></span>
    <p class="eyebrow"><span class="eyebrow__dot"></span>Producers Summit <span class="eyebrow__sep">/</span> Registrations desk</p>
    <h1 class="adm-login__title">Sign in</h1>

    <?php if ($err = error_for('auth')): ?>
      <p class="form__alert" role="alert"><?= e($err) ?></p>
    <?php endif; ?>

    <div class="field">
      <label for="email">Email</label>
      <input id="email" name="email" type="email" autocomplete="username" value="<?= old('email') ?>" required autofocus>
    </div>
    <div class="field">
      <label for="password">Password</label>
      <input id="password" name="password" type="password" autocomplete="current-password" required>
    </div>

    <button type="submit" class="btn btn--ink btn--block"><span class="btn__label">Continue</span><span class="btn__arrow" aria-hidden="true">→</span></button>
    <p class="adm-login__fine mono">Authorised staff only. Attempts are rate-limited.</p>
  </form>
</section>
