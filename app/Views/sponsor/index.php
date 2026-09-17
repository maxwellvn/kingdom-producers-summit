<?php /** @var array $summit @var array $methods @var string $espeesCode @var int[] $presets */
$errors = \App\Core\Session::get('_errors', []);
$oldMethod = old('method', 'stripe');
$oldAmount = old('amount');
$isPreset = in_array((int) round(((float) $oldAmount) * 100), $presets, true);
?>
<section class="reg">
  <div class="container pay pay--sponsor">
    <div class="pay__card sponsor__card">
      <p class="mono pay__kicker"><span class="pay__dot" aria-hidden="true"></span> Sponsor the summit</p>
      <h1 class="pay__title">Fund what the room makes possible.</h1>
      <p class="pay__lede">The summit is free to attend. Every gift pays for the venue, the resource packs, the livestream and the mentoring that follows for 90 days. Any amount, from anyone.</p>

      <?php if (isset($_GET['cancelled'])): ?>
        <div class="pay__alert" role="status"><span>Nothing was charged. Pick up where you left off whenever you like.</span></div>
      <?php endif; ?>
      <?php if (!$methods): ?>
        <div class="pay__alert" role="status"><strong>Giving is not open yet.</strong><span>Check back shortly.</span></div>
      <?php else: ?>

      <form class="form pay__form sponsor__form" method="post" action="<?= url('/sponsor') ?>" novalidate>
        <?= csrf_field() ?>

        <fieldset class="sponsor__step">
          <legend class="form__legend"><span class="mono">01</span> Amount</legend>
          <div class="sponsor__chips" role="group" aria-label="Choose an amount">
            <?php foreach ($presets as $p): ?>
              <label class="sponsor__chip mono"><input type="radio" name="preset" value="<?= $p / 100 ?>" <?= (int) round(((float) $oldAmount) * 100) === $p ? 'checked' : '' ?>>£<?= number_format($p / 100) ?></label>
            <?php endforeach; ?>
            <label class="sponsor__chip mono"><input type="radio" name="preset" value="" <?= $oldAmount !== '' && !$isPreset ? 'checked' : '' ?>>Other</label>
          </div>
          <div class="field sponsor__amount <?= error_for('amount') ? 'has-error' : '' ?>">
            <label for="amount">Amount in pounds</label>
            <div class="sponsor__amount-wrap"><span class="mono" aria-hidden="true">£</span>
              <input type="text" inputmode="decimal" id="amount" name="amount" value="<?= $oldAmount ?>" placeholder="Any amount from £5" required autocomplete="off"></div>
            <?php if ($err = error_for('amount')): ?><p class="field__error"><?= e($err) ?></p><?php endif; ?>
          </div>
        </fieldset>

        <fieldset class="sponsor__step">
          <legend class="form__legend"><span class="mono">02</span> Your details</legend>
          <div class="form__row">
            <div class="field <?= error_for('name') ? 'has-error' : '' ?>">
              <label for="name">Name</label>
              <input type="text" id="name" name="name" value="<?= old('name') ?>" required autocomplete="name">
              <?php if ($err = error_for('name')): ?><p class="field__error"><?= e($err) ?></p><?php endif; ?>
            </div>
            <div class="field <?= error_for('email') ? 'has-error' : '' ?>">
              <label for="email">Email</label>
              <input type="email" id="email" name="email" value="<?= old('email') ?>" required autocomplete="email">
              <?php if ($err = error_for('email')): ?><p class="field__error"><?= e($err) ?></p><?php endif; ?>
            </div>
          </div>
        </fieldset>

        <fieldset class="sponsor__step">
          <legend class="form__legend"><span class="mono">03</span> Give by</legend>
          <?php if ($err = error_for('method')): ?><p class="form__error"><?= e($err) ?></p><?php endif; ?>
          <div class="pay__methods">
            <?php foreach ($methods as $id => $m): ?>
              <label class="pay__method sponsor__method">
                <input type="radio" name="method" value="<?= e($id) ?>" <?= $oldMethod === $id ? 'checked' : '' ?> required>
                <span class="pay__method-icon"><?= payment_icon((string) $id) ?></span>
                <span>
                  <strong><?= e($m['label']) ?></strong>
                  <span class="pay__method-blurb"><?= $id === 'stripe' ? 'Secure card checkout. Confirmed instantly.' : ($id === 'espees' ? 'Send from your Espees wallet to code ' . e($espeesCode) . '.' : 'Pay any amount on Revolut\'s checkout page.') ?></span>
                </span>
              </label>
            <?php endforeach; ?>
          </div>
        </fieldset>

        <div class="form__submit">
          <button type="submit" class="btn btn--stamp btn--lg"><span class="btn__label">Continue</span><span class="btn__arrow" aria-hidden="true"><?= icon_arrow() ?></span></button>
          <p class="form__fine mono">Card gifts are taken in pounds sterling. Espees and Revolut gifts are confirmed by the team once received.</p>
        </div>
      </form>
      <?php endif; ?>
    </div>

    <aside class="sponsor__aside">
      <?php if ($espeesCode !== '' && isset($methods['espees'])): ?>
        <div class="sponsor__espees">
          <span class="mono"><?= payment_icon('espees', 20) ?> Espees merchant code</span>
          <strong class="sponsor__code" id="espeesCode"><?= e($espeesCode) ?></strong>
          <button type="button" class="confirmed__copy mono" data-copy="#espeesCode">Copy code</button>
          <p>Already in your wallet? Send any amount to this code, then tell us with the form so we can thank you.</p>
        </div>
      <?php endif; ?>
      <p class="form__fine mono">Questions about sponsoring? Message <a href="https://kingschat.online/user/<?= e(contact_kingschat()) ?>" target="_blank" rel="noopener">@<?= e(contact_kingschat()) ?></a> or email <a href="mailto:<?= e(contact_email()) ?>"><?= e(contact_email()) ?></a>.</p>
    </aside>
  </div>
</section>
