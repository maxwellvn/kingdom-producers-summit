<section class="ui-auth">
  <div class="ui-auth__stack">
    <div class="ui-auth__brand ui-rise">
      <span class="ui-auth__crest"><img src="<?= e(asset('img/crest.png')) ?>" alt="" width="40" height="30"></span>
      <span class="ui-auth__name">Producers Summit</span>
    </div>

    <form class="ui-auth__card ui-rise" method="post" action="<?= url('/admin/login') ?>" novalidate>
      <?= csrf_field() ?>
      <div class="ui-auth__head">
        <h1 class="ui-auth__title">Sign in to admin</h1>
        <p class="ui-auth__sub">Registrations, check-in and the live stream for Manchester and Ireland.</p>
      </div>

      <?php if ($err = error_for('auth')): ?>
        <p class="ui-alert" role="alert"><?= ph('warning-circle') ?><span><?= e($err) ?></span></p>
      <?php endif; ?>

      <div class="ui-float">
        <input id="email" name="email" type="email" autocomplete="username" value="<?= old('email') ?>" placeholder=" " required autofocus>
        <label for="email">Email</label>
      </div>
      <div class="ui-float ui-float--secret">
        <input id="password" name="password" type="password" autocomplete="current-password" placeholder=" " required>
        <label for="password">Password</label>
        <button type="button" class="ui-reveal" data-reveal-password aria-controls="password" aria-pressed="false" aria-label="Show password">
          <span class="ui-reveal__icon ui-reveal__icon--show"><?= ph('eye') ?></span>
          <span class="ui-reveal__icon ui-reveal__icon--hide"><?= ph('eye-slash') ?></span>
        </button>
      </div>

      <button type="submit" class="ui-btn ui-btn--primary ui-btn--block">
        <span class="btn__label">Continue</span><?= ph('arrow-right') ?>
      </button>
    </form>

    <p class="ui-auth__fine ui-rise"><?= ph('lock-simple') ?>Authorised staff only. Repeated attempts are paused for a while.</p>
  </div>
</section>
