<?php
/**
 * @var string   $mode
 * @var string[] $fields
 * @var string[] $stages
 * @var string[] $ageBands
 * @var array    $interests
 * @var array    $contribute
 * @var array    $hearAbout
 * @var string[] $countries
 * @var array    $summit
 * @var int      $seatsLeft
 */
$errors = \App\Core\Session::get('_errors', []);
$oldMode = \App\Core\Session::get('_old', [])['participation'] ?? null;
$selectedMode = $oldMode ?: $mode;

$ageLabels = [
  'under18' => 'Under 18', '18-24' => '18–24', '25-34' => '25–34', '35-44' => '35–44',
  '45-54' => '45–54', '55-64' => '55–64', '65plus' => '65+',
];
$stageCopy = [
  'emerge'    => 'I have an idea or a skill, and I\'m working out what to produce.',
  'build'     => 'I\'m making the first version — prototype, pilot, first customers.',
  'establish' => 'Something works. I\'m structuring it to last.',
  'multiply'  => 'I\'m reproducing what I\'ve built through others.',
];
?>

<section class="reg">
  <div class="container reg__grid">

    <!-- ===== Sticky sidebar ===== -->
    <aside class="reg__aside">
      <div class="reg__card">
        <div class="reg__card-inner">
          <p class="reg__kicker mono">London Edition 2026</p>
          <h1 class="reg__title">Register</h1>
          <p class="reg__lede">Choose how you will take part, then tell us a little about the work you want to produce.</p>

          <ol class="reg__steps mono" id="regSteps">
            <li data-step="path" class="is-current"><span>01</span> Choose your path</li>
            <li data-step="you"><span>02</span> About you</li>
            <li data-step="produce"><span>03</span> What you produce</li>
            <li data-step="details"><span>04</span> Details</li>
            <li data-step="consent"><span>05</span> Confirm</li>
          </ol>

          <dl class="reg__facts mono">
            <div><dt>Where</dt><dd><?= e($summit['city']) ?></dd></div>
            <div><dt>When</dt><dd><?= e($summit['date_text']) ?></dd></div>
            <div><dt>Onsite places</dt><dd><?= number_format($seatsLeft) ?> of <?= number_format((int) config('app.summit.onsite_capacity')) ?> left</dd></div>
          </dl>
        </div>
      </div>
    </aside>

    <!-- ===== Form ===== -->
    <form class="form" method="post" action="<?= url('/register') ?>" novalidate id="regForm">
      <?= csrf_field() ?>

      <?php if ($errors): ?>
        <div class="form__alert" role="alert">
          <strong>Please check the form.</strong>
          <span><?= count($errors) === 1 ? 'There is one thing to correct.' : 'There are ' . count($errors) . ' things to correct.' ?></span>
          <?php if (!empty($errors['email'])): ?>
            <p><?= e($errors['email']) ?></p>
            <?php if (str_contains($errors['email'], 'payment is still outstanding')): ?>
              <a href="<?= url('/register/pay') ?>">Complete payment</a>
            <?php endif; ?>
          <?php endif; ?>
        </div>
      <?php endif; ?>

      <!-- 01 Path -->
      <fieldset class="form__section" id="section-path" data-section="path">
        <legend class="form__legend"><span class="mono">01</span> Choose your path</legend>
        <?php if ($err = error_for('participation')): ?><p class="form__error"><?= e($err) ?></p><?php endif; ?>

        <div class="tickets" role="radiogroup" aria-label="How would you like to take part?">
          <?php
          $paths = [
            'onsite'     => ['A', 'Attend in London', 'Onsite for the full summit.'],
            'online'     => ['B', 'Attend online', 'Live stream from Rainham, Essex — programme, recordings and updates.'],
            'initiative' => ['C', 'Join the initiative', 'Member of Kingdom Producers with portal access.'],
          ];
          foreach ($paths as $value => [$letter, $label, $desc]): ?>
            <label class="ticket ticket--<?= e($value) ?>">
              <input type="radio" name="participation" value="<?= $value ?>" <?= $selectedMode === $value ? 'checked' : '' ?> required>
              <span class="ticket__body">
                <span class="ticket__letter mono"><?= $letter ?></span>
                <span class="ticket__label"><?= e($label) ?></span>
                <span class="ticket__desc"><?= e($desc) ?></span>
                <?php if ($value === 'onsite'): ?>
                  <span class="ticket__price mono">&pound;<?= number_format(config('paypal.price_pence') / 100, 0) ?> <em>per place</em></span>
                  <span class="ticket__seats mono"><?= number_format($seatsLeft) ?> of <?= number_format((int) config('app.summit.onsite_capacity')) ?> places left</span>
                <?php else: ?>
                  <span class="ticket__price mono"><em>Free</em></span>
                <?php endif; ?>
                <span class="ticket__punch" aria-hidden="true"></span>
              </span>
            </label>
          <?php endforeach; ?>
        </div>
      </fieldset>

      <!-- 02 About you -->
      <fieldset class="form__section" data-section="you">
        <legend class="form__legend"><span class="mono">02</span> About you</legend>

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
            <input id="first_name" name="first_name" type="text" autocomplete="given-name" value="<?= old('first_name') ?>" required>
            <?php if ($err = error_for('first_name')): ?><p class="field__error"><?= e($err) ?></p><?php endif; ?>
          </div>
          <div class="field <?= error_for('last_name') ? 'has-error' : '' ?>">
            <label for="last_name">Surname</label>
            <input id="last_name" name="last_name" type="text" autocomplete="family-name" value="<?= old('last_name') ?>" required>
            <?php if ($err = error_for('last_name')): ?><p class="field__error"><?= e($err) ?></p><?php endif; ?>
          </div>
        </div>

        <div class="form__row">
          <div class="field <?= error_for('email') ? 'has-error' : '' ?>">
            <label for="email">Email address</label>
            <input id="email" name="email" type="email" autocomplete="email" inputmode="email" value="<?= old('email') ?>" required>
            <p class="field__hint">Your reference and all updates go here.</p>
            <?php if ($err = error_for('email')): ?><p class="field__error"><?= e($err) ?></p><?php endif; ?>
            <?php if (str_contains((string) error_for('email'), 'payment is still outstanding')): ?>
              <p><a href="<?= url('/register/pay') ?>">Complete payment</a></p>
            <?php endif; ?>
          </div>
          <div class="field <?= error_for('phone') ? 'has-error' : '' ?>">
            <label for="phone">Phone <span class="field__opt" data-onsite-hide>optional</span></label>
            <input id="phone" name="phone" type="tel" autocomplete="tel" inputmode="tel" placeholder="+44" value="<?= old('phone') ?>">
            <p class="field__hint" data-only="onsite">Required for onsite attendees so we can reach you on the day.</p>
            <?php if ($err = error_for('phone')): ?><p class="field__error"><?= e($err) ?></p><?php endif; ?>
          </div>
        </div>

        <div class="form__row">
          <div class="field <?= error_for('country') ? 'has-error' : '' ?>">
            <label for="country">Country</label>
            <select id="country" name="country" autocomplete="country-name" required>
              <option value="">Select a country</option>
              <?php foreach ($countries as $c): ?>
                <option value="<?= e($c) ?>" <?= old('country', 'United Kingdom') === e($c) ? 'selected' : '' ?>><?= e($c) ?></option>
              <?php endforeach; ?>
            </select>
            <?php if ($err = error_for('country')): ?><p class="field__error"><?= e($err) ?></p><?php endif; ?>
          </div>
          <div class="field">
            <label for="city">City <span class="field__opt">optional</span></label>
            <input id="city" name="city" type="text" autocomplete="address-level2" value="<?= old('city') ?>">
          </div>
        </div>

        <div class="field <?= error_for('age_band') ? 'has-error' : '' ?>">
          <span class="field__label">Age group</span>
          <div class="chips" role="radiogroup">
            <?php foreach ($ageBands as $band): ?>
              <label class="chip">
                <input type="radio" name="age_band" value="<?= $band ?>" <?= old_checked('age_band', $band) ?> required>
                <span><?= e($ageLabels[$band]) ?></span>
              </label>
            <?php endforeach; ?>
          </div>
          <?php if ($err = error_for('age_band')): ?><p class="field__error"><?= e($err) ?></p><?php endif; ?>
        </div>

        <div class="form__row">
          <div class="field">
            <label for="church_group">Church / group <span class="field__opt">optional</span></label>
            <input id="church_group" name="church_group" type="text" value="<?= old('church_group') ?>">
          </div>
          <div class="field">
            <label for="zone">Zone / region <span class="field__opt">optional</span></label>
            <input id="zone" name="zone" type="text" value="<?= old('zone') ?>">
          </div>
        </div>

        <div class="form__row">
          <div class="field">
            <label for="organisation">Organisation / business <span class="field__opt">optional</span></label>
            <input id="organisation" name="organisation" type="text" autocomplete="organization" value="<?= old('organisation') ?>">
          </div>
          <div class="field">
            <label for="role_title">Role <span class="field__opt">optional</span></label>
            <input id="role_title" name="role_title" type="text" autocomplete="organization-title" placeholder="e.g. Founder, Student, Engineer" value="<?= old('role_title') ?>">
          </div>
        </div>
      </fieldset>

      <!-- 03 What you produce -->
      <fieldset class="form__section" data-section="produce">
        <legend class="form__legend"><span class="mono">03</span> What you produce</legend>

        <div class="field <?= error_for('field') ? 'has-error' : '' ?>">
          <label for="field">Your field</label>
          <select id="field" name="field" required>
            <option value="">Select the closest match</option>
            <?php foreach ($fields as $f): ?>
              <option value="<?= e($f) ?>" <?= old('field') === e($f) ? 'selected' : '' ?>><?= e($f) ?></option>
            <?php endforeach; ?>
          </select>
          <?php if ($err = error_for('field')): ?><p class="field__error"><?= e($err) ?></p><?php endif; ?>
        </div>

        <div class="field <?= error_for('producer_stage') ? 'has-error' : '' ?>">
          <span class="field__label">Where are you on the producer's path?</span>
          <div class="stages" role="radiogroup">
            <?php foreach ($stages as $i => $s): ?>
              <label class="stage">
                <input type="radio" name="producer_stage" value="<?= $s ?>" <?= old_checked('producer_stage', $s) ?> required>
                <span class="stage__body">
                  <span class="stage__num mono"><?= str_pad((string) ($i + 1), 2, '0', STR_PAD_LEFT) ?></span>
                  <span class="stage__name"><?= e(ucfirst($s)) ?></span>
                  <span class="stage__desc"><?= e($stageCopy[$s]) ?></span>
                </span>
              </label>
            <?php endforeach; ?>
          </div>
          <?php if ($err = error_for('producer_stage')): ?><p class="field__error"><?= e($err) ?></p><?php endif; ?>
        </div>

        <div class="field <?= error_for('what_to_produce') ? 'has-error' : '' ?>">
          <label for="what_to_produce">What can you produce? <span class="field__opt">optional, but useful</span></label>
          <textarea id="what_to_produce" name="what_to_produce" rows="4" maxlength="1200" placeholder="A sentence or two. What are you making, or what would you like to make?"><?= old('what_to_produce') ?></textarea>
          <?php if ($err = error_for('what_to_produce')): ?><p class="field__error"><?= e($err) ?></p><?php endif; ?>
        </div>

        <div class="field <?= error_for('interests') ? 'has-error' : '' ?>">
          <span class="field__label">Areas of interest <span class="field__opt">choose any</span></span>
          <div class="chips chips--check">
            <?php foreach ($interests as $key => $label): ?>
              <label class="chip">
                <input type="checkbox" name="interests[]" value="<?= $key ?>" <?= old_checked('interests', $key) ?>>
                <span><?= e($label) ?></span>
              </label>
            <?php endforeach; ?>
          </div>
          <?php if ($err = error_for('interests')): ?><p class="field__error"><?= e($err) ?></p><?php endif; ?>
        </div>
      </fieldset>

      <!-- 04 Details (conditional) -->
      <fieldset class="form__section" data-section="details">
        <legend class="form__legend"><span class="mono">04</span> Details</legend>

        <div class="form__conditional" data-only="onsite">
          <div class="form__row">
            <div class="field">
              <label for="dietary">Dietary requirements <span class="field__opt">optional</span></label>
              <input id="dietary" name="dietary" type="text" value="<?= old('dietary') ?>">
            </div>
            <div class="field">
              <label for="accessibility">Access requirements <span class="field__opt">optional</span></label>
              <input id="accessibility" name="accessibility" type="text" placeholder="Step-free access, BSL, hearing loop…" value="<?= old('accessibility') ?>">
            </div>
          </div>

          <div class="form__row">
            <div class="field">
              <label for="emergency_contact">Emergency contact <span class="field__opt">optional</span></label>
              <input id="emergency_contact" name="emergency_contact" type="text" placeholder="Name and number" value="<?= old('emergency_contact') ?>">
            </div>
            <div class="field field--check">
              <label class="check">
                <input type="checkbox" name="needs_letter" value="1" <?= old_checked('needs_letter', '1') ?>>
                <span>I need an invitation letter (e.g. for visa or employer)</span>
              </label>
            </div>
          </div>
        </div>

        <div class="form__conditional" data-only="online">
          <div class="field field--check">
            <label class="check">
              <input type="checkbox" name="wants_updates" value="1" <?= old_checked('wants_updates', '1') ?: (old('wants_updates') === '' ? 'checked' : '') ?>>
              <span>Send me the programme, speaker announcements and session recordings</span>
            </label>
          </div>
          <div class="field field--check">
            <label class="check">
              <input type="checkbox" name="wants_portal" value="1" <?= old_checked('wants_portal', '1') ?>>
              <span>Notify me when the Kingdom Producers portal opens</span>
            </label>
          </div>
        </div>

        <div class="form__conditional" data-only="initiative">
          <div class="portal-note">
            <span class="portal-note__stamp" aria-hidden="true">Member</span>
            <p>As a member you'll receive access to the Kingdom Producers portal as it opens — a detailed repository of methods, case files, capital pathways, a producer directory, and opportunities to work with other producers.</p>
          </div>

          <div class="field <?= error_for('contribute_as') ? 'has-error' : '' ?>">
            <span class="field__label">How might you contribute? <span class="field__opt">choose any</span></span>
            <div class="chips chips--check">
              <?php foreach ($contribute as $key => $label): ?>
                <label class="chip"><input type="checkbox" name="contribute_as[]" value="<?= $key ?>" <?= old_checked('contribute_as', $key) ?>><span><?= e($label) ?></span></label>
              <?php endforeach; ?>
            </div>
            <?php if ($err = error_for('contribute_as')): ?><p class="field__error"><?= e($err) ?></p><?php endif; ?>
          </div>

          <div class="field">
            <label for="portal_interest">What would you most want from the portal? <span class="field__opt">optional</span></label>
            <textarea id="portal_interest" name="portal_interest" rows="3" maxlength="1200"><?= old('portal_interest') ?></textarea>
          </div>
        </div>

        <div class="field">
          <label for="hear_about">How did you hear about the summit? <span class="field__opt">optional</span></label>
          <select id="hear_about" name="hear_about">
            <option value="">—</option>
            <?php foreach ($hearAbout as $key => $label): ?>
              <option value="<?= $key ?>" <?= old('hear_about') === $key ? 'selected' : '' ?>><?= e($label) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </fieldset>

      <!-- 05 Consent -->
      <fieldset class="form__section" data-section="consent">
        <legend class="form__legend"><span class="mono">05</span> Confirm</legend>

        <div class="field field--check <?= error_for('consent_terms') ? 'has-error' : '' ?>">
          <label class="check">
            <input type="checkbox" name="consent_terms" value="1" <?= old_checked('consent_terms', '1') ?> required>
            <span>I understand my details will be used by Loveworld Consulate UK to administer the summit and the Kingdom Producers initiative, and I can ask for them to be removed at any time.</span>
          </label>
          <?php if ($err = error_for('consent_terms')): ?><p class="field__error"><?= e($err) ?></p><?php endif; ?>
        </div>
        <div class="field field--check">
          <label class="check">
            <input type="checkbox" name="consent_marketing" value="1" <?= old_checked('consent_marketing', '1') ?>>
            <span>I'm happy to hear about other Loveworld Consulate UK programmes</span>
          </label>
        </div>

        <div class="form__submit">
          <div class="form__conditional" data-only="onsite">
            <div class="pay-note">
              <span class="pay-note__stamp mono">Secure payment</span>
              <p>Onsite attendance is <strong>&pound;<?= number_format(config('paypal.price_pence') / 100, 0) ?></strong> per place. You'll complete payment on the next step via PayPal.</p>
            </div>
          </div>
          <button type="submit" class="btn btn--stamp btn--lg" id="submitBtn">
            <span class="btn__label" data-pay-label="Continue to payment &mdash; &pound;<?= number_format(config('paypal.price_pence') / 100, 0) ?>" data-free-label="Complete registration">Complete registration</span>
            <span class="btn__arrow" aria-hidden="true"><?= icon_arrow() ?></span>
          </button>
          <p class="form__fine mono">You'll receive a reference code on the next page.</p>
        </div>
      </fieldset>
    </form>
  </div>
</section>
