<?php /** @var array $summit @var array<int,string> $categories */
$errors = \App\Core\Session::get('_errors', []);
$f = static function (string $id, string $label, string $input, bool $optional = false, string $hint = ''): void {
    $err = error_for($id);
    echo '<div class="field' . ($err ? ' has-error' : '') . '">'
        . '<label for="' . e($id) . '">' . e($label) . ($optional ? ' <span class="field__opt">optional</span>' : '') . '</label>'
        . $input
        . ($hint !== '' ? '<p class="field__hint">' . e($hint) . '</p>' : '')
        . ($err ? '<p class="field__error">' . e($err) . '</p>' : '')
        . '</div>';
};
?>
<section class="reg">
  <div class="container pay pay--sponsor">
    <div class="pay__card sponsor__card">
      <p class="mono pay__kicker"><span class="pay__dot" aria-hidden="true"></span> Exhibit in <?= e((string) $summit['place']) ?></p>
      <h1 class="pay__title">Show what you produce.</h1>
      <p class="pay__lede">Apply for a free stand at the <?= e((string) $summit['edition']) ?> and put your products, services or work in front of the whole room. The team reviews every application and emails you once yours is approved.</p>

      <?php if ($errors): ?>
        <div class="pay__alert" role="alert"><strong>Please check the form.</strong></div>
      <?php endif; ?>

      <form class="form pay__form sponsor__form" method="post" action="<?= url('/exhibit') ?>" novalidate>
        <?= csrf_field() ?>
        <div class="sr-only" aria-hidden="true"><label>Leave this empty <input type="text" name="website_url" tabindex="-1" autocomplete="off"></label></div>
        <input type="hidden" name="opened_at" value="<?= time() ?>">

        <fieldset class="sponsor__step">
          <legend class="form__legend"><span class="mono">01</span> You</legend>
          <div class="form__row">
            <?php $f('first_name', 'First name', '<input type="text" id="first_name" name="first_name" value="' . old('first_name') . '" required autocomplete="given-name">'); ?>
            <?php $f('last_name', 'Surname', '<input type="text" id="last_name" name="last_name" value="' . old('last_name') . '" required autocomplete="family-name">'); ?>
          </div>
          <div class="form__row">
            <?php $f('email', 'Email', '<input type="email" id="email" name="email" value="' . old('email') . '" required autocomplete="email">', false, 'We email the outcome here.'); ?>
            <?php $f('phone', 'Phone', '<input type="tel" id="phone" name="phone" value="' . old('phone') . '" autocomplete="tel" placeholder="' . e((string) ($summit['dial'] ?? '+44')) . '">', true); ?>
          </div>
          <?php $f('kingschat', 'KingsChat username', '<input type="text" id="kingschat" name="kingschat" value="' . old('kingschat') . '" autocapitalize="none" spellcheck="false" placeholder="@username">', true); ?>
        </fieldset>

        <fieldset class="sponsor__step">
          <legend class="form__legend"><span class="mono">02</span> What you will show</legend>
          <?php $f('business', 'Business or project name', '<input type="text" id="business" name="business" value="' . old('business') . '" required autocomplete="organization">'); ?>
          <?php
          $opts = '<option value="">Choose the closest match</option>';
          foreach ($categories as $c) { $opts .= '<option value="' . e($c) . '"' . (old('category') === e($c) ? ' selected' : '') . '>' . e($c) . '</option>'; }
          $f('category', 'Category', '<select id="category" name="category" required>' . $opts . '</select>');
          ?>
          <?php $f('description', 'What will people see at your stand?', '<textarea id="description" name="description" rows="4" maxlength="1500" required>' . old('description') . '</textarea>', false, 'Two or three sentences: what you make or offer, and who it is for.'); ?>
          <?php $f('website', 'Website or social page', '<input type="text" id="website" name="website" value="' . old('website') . '" inputmode="url" placeholder="https://">', true); ?>
        </fieldset>

        <fieldset class="sponsor__step">
          <legend class="form__legend"><span class="mono">03</span> Your stand</legend>
          <div class="form__row">
            <?php
            $staff = '';
            foreach (range(1, 6) as $n) { $staff .= '<option value="' . $n . '"' . ((int) old('staff', '1') === $n ? ' selected' : '') . '>' . $n . '</option>'; }
            $f('staff', 'People staffing the stand', '<select id="staff" name="staff">' . $staff . '</select>');
            ?>
            <div class="field">
              <span class="field__label">Power</span>
              <label class="check"><input type="checkbox" name="needs_power" value="1" <?= old('needs_power') === '1' ? 'checked' : '' ?>><span>I need a power socket</span></label>
            </div>
          </div>
          <?php $f('notes', 'Anything else we should know', '<textarea id="notes" name="notes" rows="2" maxlength="600">' . old('notes') . '</textarea>', true, 'Space, equipment, accessibility, anything that helps us place you.'); ?>
        </fieldset>

        <div class="field <?= error_for('consent') ? 'has-error' : '' ?>">
          <label class="check"><input type="checkbox" name="consent" value="1" <?= old('consent') === '1' ? 'checked' : '' ?> required><span>I agree to be contacted about this application. See the <a href="<?= url('/privacy') ?>">privacy notice</a>.</span></label>
          <?php if ($err = error_for('consent')): ?><p class="field__error"><?= e($err) ?></p><?php endif; ?>
        </div>

        <div class="form__submit">
          <button type="submit" class="btn btn--stamp btn--lg"><span class="btn__label">Apply to exhibit</span><span class="btn__arrow" aria-hidden="true"><?= icon_arrow() ?></span></button>
          <p class="form__fine mono">Stands are free. Space is limited, so each application is reviewed.</p>
        </div>
      </form>
    </div>

    <aside class="sponsor__aside">
      <div class="sponsor__espees exhibit__facts">
        <span class="mono"><?= ph('storefront') ?> At a glance</span>
        <p><strong><?= e((string) $summit['date_text']) ?></strong><br><?= e(venue_line()) ?></p>
        <p>Free stand · up to 6 people · power on request · approval by email</p>
      </div>
      <p class="mono">Questions about exhibiting? Message @lwconsul_uk or email <?= e(contact_email()) ?>.</p>
    </aside>
  </div>
</section>
