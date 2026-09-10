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
$isInitiative = $selectedMode === 'initiative';

$ageLabels = [
  'under18' => 'Under 18', '18-24' => '18–24', '25-34' => '25–34', '35-44' => '35–44',
  '45-54' => '45–54', '55-64' => '55–64', '65plus' => '65+',
];
$stageCopy = [
  'emerge'    => 'I do not yet have a clear idea, product, service, project, business or creative work.',
  'build'     => 'I have an idea or have started something, and I need help taking the next steps.',
  'establish' => 'I already have something active and I want to improve or grow it.',
  'multiply'  => 'What I do is established and I want to scale it or extend its reach.',
];
$stageLabels = [
  'emerge' => 'Nothing yet',
  'build' => 'Getting started',
  'establish' => 'Growing',
  'multiply' => 'Scaling',
];
?>

<section class="reg">
  <div class="container reg__grid">

    <!-- ===== Sticky sidebar ===== -->
    <aside class="reg__aside">
      <div class="reg__card">
        <div class="reg__card-inner">
          <p class="reg__kicker mono">Essex Edition 2026</p>
          <h1 class="reg__title">Register</h1>
          <p class="reg__lede"><?= $isInitiative ? 'Join the 30, 60 and 90 day production journey.' : 'Choose how you will attend, then tell us about your field and interests.' ?></p>

          <ol class="reg__steps mono" id="regSteps">
            <?php
            $steps = $isInitiative
              ? ['path' => 'The initiative', 'you' => 'About you', 'consent' => 'Confirm']
              : ['path' => 'Attendance', 'you' => 'About you', 'produce' => 'Where you are now', 'details' => 'Details', 'consent' => 'Confirm'];
            $stepNo = 0;
            foreach ($steps as $key => $label): $stepNo++; ?>
              <li data-step="<?= $key ?>" class="<?= $stepNo === 1 ? 'is-current' : '' ?>"><span><?= str_pad((string) $stepNo, 2, '0', STR_PAD_LEFT) ?></span> <?= e($label) ?></li>
            <?php endforeach; ?>
          </ol>

          <dl class="reg__facts mono">
            <div><dt>Where</dt><dd><?= e($summit['city']) ?></dd></div>
            <div><dt>When</dt><dd><?= e($summit['date_text']) ?></dd></div>
            <div><dt>Onsite places</dt><dd><?= number_format($seatsLeft) ?> of <?= number_format((int) config('app.summit.onsite_capacity')) ?> left</dd></div>
            <?php if (!$isInitiative): ?>
              <div><dt>Onsite cost</dt><dd><?= e(espees_price(price_pence('onsite'))) ?> <span class="reg__facts-note">inaugural edition price, was <?= e(espees_price(standard_price_pence('onsite'))) ?></span></dd></div>
              <div><dt>Online cost</dt><dd><?= e(espees_price(price_pence('online'))) ?> <span class="reg__facts-note">inaugural edition price, was <?= e(espees_price(standard_price_pence('online'))) ?></span></dd></div>
            <?php endif; ?>
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

      <!-- 01 Attendance or initiative entry -->
      <fieldset class="form__section" id="section-path" data-section="path">
        <legend class="form__legend"><span class="mono">01</span> <?= $isInitiative ? 'Join the initiative' : 'Choose how you will attend' ?></legend>
        <?php if ($err = error_for('participation')): ?><p class="form__error"><?= e($err) ?></p><?php endif; ?>

        <?php if ($isInitiative): ?>
          <input type="hidden" name="participation" value="initiative">
          <div class="initiative-enrolment">
            <p class="initiative-enrolment__eyebrow mono">100 producers per edition</p>
            <h2>Join the initiative</h2>
            <p>The Kingdom Producers initiative develops 100 young people per edition into working producers. It is open to everyone in the United Kingdom, whether or not you attend the summit.</p>
            <p>You enter a structured 30, 60 and 90 day journey: working out what you can produce, developing and testing it, then launching something real. A product, a service, a solution, an enterprise or a piece of intellectual property.</p>
            <h3>What you receive</h3>
            <ul class="initiative-benefits">
              <li>Help turning your skills, ideas and opportunities into something of value</li>
              <li>Practical business, entrepreneurship, technology, innovation and intellectual-property teaching</li>
              <li>Mentorship, expert guidance and a network of producers and professionals</li>
              <li>Your own 30, 60 and 90 day producers plan, and support to work it</li>
              <li>Chances to showcase, pitch, collaborate and reach markets</li>
              <li>A place in the ongoing Kingdom Producers network</li>
            </ul>
            <p>Give us your details below and we will keep you informed by email as the initiative unfolds: what is happening, what is available to you and how to take part.</p>
          </div>
        <?php else: ?>
        <p class="field__label path-choice__label">Choose one</p>
        <div class="tickets tickets--attendance" role="radiogroup" aria-label="How would you like to attend the summit?">
          <?php
          $paths = [
            'onsite' => ['A', 'Attend onsite in Essex', 'The full day in the room: masterclasses, workshops, live mentoring, the business clinic, lunch and networking, a resource pack and a certificate.'],
            'online' => ['B', 'Attend online', 'The main sessions streamed to you, to follow from anywhere. You take no part in the room, so the workshops, mentoring, clinic and networking are not included.'],
          ];
          foreach ($paths as $value => [$letter, $label, $desc]): ?>
            <?php $soldOut = $value === 'onsite' && $seatsLeft <= 0; ?>
            <label class="ticket ticket--<?= e($value) ?> <?= $soldOut ? 'is-soldout' : '' ?>">
              <input type="radio" name="participation" value="<?= $value ?>" <?= $selectedMode === $value && !$soldOut ? 'checked' : '' ?> <?= $soldOut ? 'disabled' : '' ?> required>
              <span class="ticket__body">
                <span class="ticket__letter mono"><?= $letter ?></span>
                <span class="ticket__label"><?= e($label) ?></span>
                <span class="ticket__desc"><?= e($desc) ?></span>
                <span class="ticket__cost">
                  <strong class="ticket__amount"><?= e(espees_price(price_pence($value))) ?></strong>
                  <span class="ticket__was mono">Inaugural edition · was <s><?= e(espees_price(standard_price_pence($value))) ?></s></span>
                </span>
                <?php if ($value === 'onsite'): ?>
                  <span class="ticket__seats mono"><?= $soldOut ? 'Fully booked' : number_format($seatsLeft) . ' of ' . number_format((int) config('app.summit.onsite_capacity')) . ' places left' ?></span>
                <?php else: ?>
                  <span class="ticket__limit mono">Selected sessions only · no workshops, mentoring or networking</span>
                <?php endif; ?>
                <span class="ticket__punch" aria-hidden="true"></span>
              </span>
            </label>
          <?php endforeach; ?>
        </div>

        <?php endif; ?>
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
            <label for="phone">Phone number</label>
            <input id="phone" name="phone" type="tel" autocomplete="tel" inputmode="tel" placeholder="+44" value="<?= old('phone') ?>" required>
            <?php if ($err = error_for('phone')): ?><p class="field__error"><?= e($err) ?></p><?php endif; ?>
          </div>
          <div class="field <?= error_for('kingschat_username') ? 'has-error' : '' ?>">
            <label for="kingschat_username">KingsChat username <span class="field__opt">optional</span></label>
            <input id="kingschat_username" name="kingschat_username" type="text" autocomplete="off" placeholder="@username" maxlength="80" value="<?= old('kingschat_username') ?>">
            <?php if ($err = error_for('kingschat_username')): ?><p class="field__error"><?= e($err) ?></p><?php endif; ?>
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

        <div class="church-hierarchy" data-church-hierarchy data-api-base="https://churches-api.rorportal.org/api/v1">
          <input id="zone" name="zone" type="hidden" value="<?= old('zone') ?>" data-old-value="<?= old('zone') ?>">

          <div class="field <?= error_for('zone') ? 'has-error' : '' ?>">
            <span class="field__label">Church structure</span>
            <div class="chips" role="radiogroup" aria-label="Choose Zone or Campus Ministry">
              <label class="chip"><input type="radio" name="directory_type" value="zone" required><span>Zone</span></label>
              <label class="chip"><input type="radio" name="directory_type" value="campus" required><span>Campus Ministry</span></label>
            </div>
            <?php if ($err = error_for('zone')): ?><p class="field__error"><?= e($err) ?></p><?php endif; ?>
          </div>

          <div class="field directory-choice" data-directory-choice="zone" hidden>
            <label for="zone_directory">Zone</label>
            <select id="zone_directory" disabled>
              <option value="">Loading zones…</option>
            </select>
          </div>

          <div class="field directory-choice" data-directory-choice="campus" hidden>
            <label for="campus_directory">Campus Ministry</label>
            <select id="campus_directory" disabled>
              <option value="">Loading campus ministries…</option>
            </select>
          </div>

          <div class="field <?= error_for('group_name') ? 'has-error' : '' ?>" data-church-step="group" hidden>
            <label for="group_name">Group</label>
            <select id="group_name" name="group_name" required data-old-value="<?= old('group_name') ?>">
              <option value="">Choose your zone first</option>
            </select>
            <?php if ($err = error_for('group_name')): ?><p class="field__error"><?= e($err) ?></p><?php endif; ?>
          </div>

          <div class="field <?= error_for('church_name') ? 'has-error' : '' ?>" data-church-step="church" hidden>
            <label for="church_name">Church</label>
            <input id="church_name" name="church_name" type="text" maxlength="160" required
                   placeholder="Type your church name" value="<?= old('church_name') ?>"
                   list="church_options" autocomplete="off">
            <datalist id="church_options"></datalist>
            <p class="field__hint">Start typing to pick from the churches in your group, or type it in full.</p>
            <?php if ($err = error_for('church_name')): ?><p class="field__error"><?= e($err) ?></p><?php endif; ?>
          </div>

          <p class="field__hint church-hierarchy__status" data-church-status aria-live="polite">Loading the Loveworld church directory…</p>
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

      <?php if (!$isInitiative): ?>
      <!-- 03 Current producer stage -->
      <fieldset class="form__section" data-section="produce">
        <legend class="form__legend"><span class="mono">03</span> Where you are now</legend>

        <div class="field <?= error_for('field') ? 'has-error' : '' ?>">
          <label for="field">Your field</label>
          <select id="field" name="field" required>
            <option value="">Select the closest match</option>
            <?php foreach ($fields as $f): ?>
              <option value="<?= e($f) ?>" <?= old('field') === e($f) ? 'selected' : '' ?>><?= e($f) ?></option>
            <?php endforeach; ?>
          </select>
          <?php if ($err = error_for('field')): ?><p class="field__error"><?= e($err) ?></p><?php endif; ?>
          <div class="field__reveal" data-reveal-when="field:Other">
            <label for="field_other" class="field__sub-label">Tell us your field <span class="field__opt">optional</span></label>
            <input id="field_other" name="field_other" type="text" maxlength="120" placeholder="For example: sport, hospitality, logistics" value="<?= old('field_other') ?>">
            <?php if ($err = error_for('field_other')): ?><p class="field__error"><?= e($err) ?></p><?php endif; ?>
          </div>
        </div>

        <div class="field <?= error_for('producer_stage') ? 'has-error' : '' ?>">
          <span class="field__label">Which option sounds most like you?</span>
          <div class="stages" role="radiogroup">
            <?php foreach ($stages as $i => $s): ?>
              <label class="stage">
                <input type="radio" name="producer_stage" value="<?= $s ?>" <?= old_checked('producer_stage', $s) ?> required>
                <span class="stage__body">
                  <span class="stage__num mono"><?= str_pad((string) ($i + 1), 2, '0', STR_PAD_LEFT) ?></span>
                  <span class="stage__name"><?= e($stageLabels[$s]) ?></span>
                  <span class="stage__desc"><?= e($stageCopy[$s]) ?></span>
                </span>
              </label>
            <?php endforeach; ?>
          </div>
          <?php if ($err = error_for('producer_stage')): ?><p class="field__error"><?= e($err) ?></p><?php endif; ?>
        </div>

        <div class="field <?= error_for('producer_stage_detail') ? 'has-error' : '' ?>">
          <label for="producer_stage_detail">Tell us more about where you are <span class="field__opt">optional</span></label>
          <textarea id="producer_stage_detail" name="producer_stage_detail" rows="3" maxlength="500" placeholder="For example: what you have started, what is already working, or what you want help with."><?= old('producer_stage_detail') ?></textarea>
          <?php if ($err = error_for('producer_stage_detail')): ?><p class="field__error"><?= e($err) ?></p><?php endif; ?>
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
          <div class="field__reveal" data-reveal-when="interests[]:other">
            <label for="interest_other" class="field__sub-label">Tell us which area <span class="field__opt">optional</span></label>
            <input id="interest_other" name="interest_other" type="text" maxlength="160" placeholder="For example: sport, hospitality, logistics" value="<?= old('interest_other') ?>">
            <?php if ($err = error_for('interest_other')): ?><p class="field__error"><?= e($err) ?></p><?php endif; ?>
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

          <div class="field">
            <label for="emergency_contact">Emergency contact <span class="field__opt">optional</span></label>
            <input id="emergency_contact" name="emergency_contact" type="text" placeholder="Name and number" value="<?= old('emergency_contact') ?>">
          </div>
        </div>

        <div class="form__conditional" data-only="initiative">
          <div class="portal-note">
            <span class="portal-note__stamp" aria-hidden="true">Initiative</span>
            <p>You are joining the Kingdom Producers initiative and its 30, 60 and 90 day production journey. You will receive access to methods, case files, capital pathways, mentoring, the producer directory and opportunities to work with other producers.</p>
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
      <?php endif; ?>

      <!-- 05 Consent -->
      <fieldset class="form__section" data-section="consent">
        <legend class="form__legend"><span class="mono"><?= $isInitiative ? '03' : '05' ?></span> Confirm</legend>

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
          <?php foreach (['onsite', 'online'] as $paidPath): ?>
            <div class="form__conditional" data-only="<?= $paidPath ?>">
              <div class="pay-note">
                <span class="pay-note__stamp mono">Secure payment</span>
                <p class="pay-note__amount">You pay <strong><?= e(espees_price(price_pence($paidPath))) ?></strong> for your <?= $paidPath === 'onsite' ? 'onsite' : 'online' ?> place.</p>
                <p>The full price is <?= e(espees_price(standard_price_pence($paidPath))) ?>. This is the inaugural edition, so the price is reduced. On the next page you choose how to pay. Your place is held once payment is confirmed.</p>
              </div>
            </div>
          <?php endforeach; ?>
          <button type="submit" class="btn btn--stamp btn--lg" id="submitBtn">
            <span class="btn__label"
                  data-pay-label-onsite="Continue to payment &mdash; <?= e(espees_price(price_pence('onsite'))) ?>"
                  data-pay-label-online="Continue to payment &mdash; <?= e(espees_price(price_pence('online'))) ?>"
                  data-free-label="Complete registration">Complete registration</span>
            <span class="btn__arrow" aria-hidden="true"><?= icon_arrow() ?></span>
          </button>
          <p class="form__fine mono">You'll receive a reference code on the next page.</p>
        </div>
      </fieldset>
    </form>
  </div>
</section>
