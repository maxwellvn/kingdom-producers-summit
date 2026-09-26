<?php
/** @var string $flash */
$errors = \App\Core\Session::get('_errors', []);
$paths = [
    'onsite'     => ['map-pin', 'Onsite in ' . config('app.summit.place'), 'Full access to the room. Gets a QR pass for the door.'],
    'online'     => ['broadcast', 'Online', 'Livestream access. No limit on numbers.'],
    'initiative' => ['plant', 'Initiative only', 'On the register for the 90-day journey, not attending.'],
];
$picked = (string) old('participation');
$field = static function (string $id, string $label, string $input, bool $optional = false, string $hint = ''): void {
    $err = error_for($id);
    echo '<div class="adm-field' . ($err ? ' has-error' : '') . '">'
        . '<label class="adm-field__label" for="' . e($id) . '">' . e($label) . ($optional ? ' <span class="adm-field__opt">Optional</span>' : '') . '</label>'
        . $input
        . ($hint !== '' ? '<p class="adm-field__hint">' . e($hint) . '</p>' : '')
        . ($err ? '<p class="adm-field__error">' . ph('warning-circle') . ' ' . e($err) . '</p>' : '')
        . '</div>';
};
?>
<section class="adm-page">
  <header class="adm-page__head">
    <div>
      <p class="eyebrow"><span class="eyebrow__dot"></span>People</p>
      <h1 class="adm-page__title">Issue a place</h1>
    </div>
  </header>

  <?php if ($flash !== ''): ?>
    <div class="form__alert" role="status"><span><?= e($flash) ?></span></div>
  <?php endif; ?>

  <?php if ($errors): ?>
    <div class="form__alert" role="alert">
      <strong>Please check the form.</strong>
      <?php foreach ($errors as $message): ?><span><?= e($message) ?></span><?php endforeach; ?>
    </div>
  <?php endif; ?>

  <form method="post" action="<?= url('/admin/issue') ?>" class="adm-form">
    <?= csrf_field() ?>

    <section class="adm-panel adm-form__section">
      <header class="adm-form__head">
        <h2 class="adm-panel__title">Which place</h2>
        <p>For guests, speakers and anyone you add by hand. They are emailed their reference and pass straight away.</p>
      </header>
      <div class="adm-choices" role="radiogroup" aria-label="Which place">
        <?php foreach ($paths as $value => [$ico, $label, $hint]): ?>
          <label class="adm-choice">
            <input type="radio" name="participation" value="<?= e($value) ?>" <?= $picked === $value || ($picked === '' && $value === 'onsite') ? 'checked' : '' ?> required>
            <span class="adm-choice__icon"><?= ph($ico) ?></span>
            <span class="adm-choice__text"><strong><?= e($label) ?></strong><span><?= e($hint) ?></span></span>
            <span class="adm-choice__tick"><?= ph('check') ?></span>
          </label>
        <?php endforeach; ?>
      </div>
      <?php if ($err = error_for('participation')): ?><p class="adm-field__error"><?= ph('warning-circle') ?> <?= e($err) ?></p><?php endif; ?>
    </section>

    <section class="adm-panel adm-form__section">
      <header class="adm-form__head"><h2 class="adm-panel__title">Who</h2></header>
      <div class="adm-form__grid adm-form__grid--name">
        <?php
        $titles = '<option value="">None</option>';
        foreach (['Mr', 'Mrs', 'Ms', 'Miss', 'Brother', 'Sister', 'Dr', 'Pastor', 'Deacon', 'Deaconess', 'Rev'] as $t) {
            $titles .= '<option value="' . $t . '"' . (old('title') === $t ? ' selected' : '') . '>' . $t . '</option>';
        }
        $field('title', 'Title', '<select id="title" name="title">' . $titles . '</select>', true);
        $field('first_name', 'First name', '<input id="first_name" name="first_name" type="text" autocomplete="off" value="' . old('first_name') . '" required>');
        $field('last_name', 'Surname', '<input id="last_name" name="last_name" type="text" autocomplete="off" value="' . old('last_name') . '" required>');
        ?>
      </div>
      <div class="adm-form__grid adm-form__grid--2">
        <?php
        $field('email', 'Email address', '<input id="email" name="email" type="email" autocomplete="off" inputmode="email" value="' . old('email') . '" required>', false, 'Their reference and pass go here.');
        $field('kingschat_username', 'KingsChat username', '<span class="adm-input-affix"><span>@</span><input id="kingschat_username" name="kingschat_username" type="text" autocomplete="off" autocapitalize="none" spellcheck="false" placeholder="username" value="' . old('kingschat_username') . '"></span>', true, 'They also get the pass by KingsChat message.');
        $field('phone', 'Phone', '<input id="phone" name="phone" type="tel" autocomplete="off" inputmode="tel" value="' . old('phone') . '">', true);
        $field('zone', 'Zone', '<input id="zone" name="zone" type="text" autocomplete="off" value="' . old('zone') . '">', true);
        ?>
      </div>
      <?php $field('note', 'Why it was issued', '<input id="note" name="note" type="text" maxlength="255" placeholder="Guest speaker" value="' . old('note') . '">', true, 'Shown on the registration so the team knows where it came from.'); ?>
    </section>

    <div class="adm-form__actions">
      <button type="submit" class="adm-btn adm-btn--dark adm-btn--lg"><?= ph('ticket') ?> Issue the place</button>
    </div>
  </form>
</section>
