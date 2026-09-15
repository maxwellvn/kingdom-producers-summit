<?php
/** @var string $flash */
$errors = \App\Core\Session::get('_errors', []);
$paths = [
    'onsite'     => ['Onsite in Rainham', 'Full access to the room.'],
    'online'     => ['Online', 'Livestream access. No limit on numbers.'],
    'initiative' => ['Initiative only', 'On the register, not attending the summit.'],
];
?>
<section class="adm-page">
  <header class="adm-page__head">
    <div>
      <p class="eyebrow"><span class="eyebrow__dot"></span>Registrations</p>
      <h1 class="adm-page__title">Issue <span class="adm-page__title-sub">a place</span></h1>
    </div>
  </header>

  <?php if ($flash !== ''): ?>
    <div class="form__alert" role="status" style="border-color: rgba(46,160,67,.5)">
      <span><?= e($flash) ?></span>
    </div>
  <?php endif; ?>

  <?php if ($errors): ?>
    <div class="form__alert" role="alert">
      <strong>Please check the form.</strong>
      <?php foreach ($errors as $message): ?><span><?= e($message) ?></span><?php endforeach; ?>
    </div>
  <?php endif; ?>

  <p class="adm-muted" style="max-width:70ch;margin-bottom:1.6rem">
    Registers someone on their behalf, for guests, speakers and anyone you add by hand.
    They are emailed their reference and pass straight away, and messaged on KingsChat if you add a username.
  </p>

  <form method="post" action="<?= url('/admin/issue') ?>" class="form" style="max-width:720px">
    <?= csrf_field() ?>

    <div class="field <?= error_for('participation') ? 'has-error' : '' ?>">
      <span class="field__label">Which place</span>
      <div class="chips" role="radiogroup">
        <?php foreach ($paths as $value => [$label, $hint]): ?>
          <label class="chip">
            <input type="radio" name="participation" value="<?= $value ?>" <?= old_checked('participation', $value) ?> required>
            <span><?= e($label) ?></span>
          </label>
        <?php endforeach; ?>
      </div>
      <p class="field__hint"><?= e($paths['onsite'][1]) ?></p>
      <?php if ($err = error_for('participation')): ?><p class="field__error"><?= e($err) ?></p><?php endif; ?>
    </div>

    <div class="form__row form__row--title">
      <div class="field field--sm">
        <label for="title">Title <span class="field__opt">optional</span></label>
        <select id="title" name="title">
          <option value="">—</option>
          <?php foreach (['Mr', 'Mrs', 'Ms', 'Miss', 'Brother', 'Sister', 'Dr', 'Pastor', 'Deacon', 'Deaconess', 'Rev'] as $t): ?>
            <option value="<?= $t ?>" <?= old('title') === $t ? 'selected' : '' ?>><?= $t ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="field <?= error_for('first_name') ? 'has-error' : '' ?>">
        <label for="first_name">First name</label>
        <input id="first_name" name="first_name" type="text" value="<?= old('first_name') ?>" required>
        <?php if ($err = error_for('first_name')): ?><p class="field__error"><?= e($err) ?></p><?php endif; ?>
      </div>
      <div class="field <?= error_for('last_name') ? 'has-error' : '' ?>">
        <label for="last_name">Surname</label>
        <input id="last_name" name="last_name" type="text" value="<?= old('last_name') ?>" required>
        <?php if ($err = error_for('last_name')): ?><p class="field__error"><?= e($err) ?></p><?php endif; ?>
      </div>
    </div>

    <div class="form__row">
      <div class="field <?= error_for('email') ? 'has-error' : '' ?>">
        <label for="email">Email address</label>
        <input id="email" name="email" type="email" value="<?= old('email') ?>" required>
        <p class="field__hint">Their reference and pass go here.</p>
        <?php if ($err = error_for('email')): ?><p class="field__error"><?= e($err) ?></p><?php endif; ?>
      </div>
      <div class="field">
        <label for="kingschat_username">KingsChat username <span class="field__opt">optional</span></label>
        <input id="kingschat_username" name="kingschat_username" type="text" placeholder="username" value="<?= old('kingschat_username') ?>">
      </div>
    </div>

    <div class="form__row">
      <div class="field">
        <label for="phone">Phone <span class="field__opt">optional</span></label>
        <input id="phone" name="phone" type="tel" value="<?= old('phone') ?>">
      </div>
      <div class="field">
        <label for="zone">Zone <span class="field__opt">optional</span></label>
        <input id="zone" name="zone" type="text" value="<?= old('zone') ?>">
      </div>
    </div>

    <div class="field">
      <label for="note">Why it was issued <span class="field__opt">optional</span></label>
      <input id="note" name="note" type="text" maxlength="255" placeholder="e.g. Guest speaker" value="<?= old('note') ?>">
    </div>

    <button type="submit" class="adm-btn adm-btn--dark">Issue the place</button>
  </form>
</section>
