<?php
/** @var string $state ready|closed|welcome @var array $summit @var array|null $person @var string|null $error @var string|null $identifier @var string|null $checkedInAt */
$person = $person ?? null;
$error = $error ?? '';
?>
<section class="reg checkin">
  <div class="container pay">
    <?php if ($state === 'welcome'): ?>
      <?php $p = $person; $at = strtotime((string) $checkedInAt) ?: time(); ?>
      <div class="checkin__show" role="status">Show this screen to the official at the registration desk</div>
      <article class="pay__card checkin__welcome" aria-labelledby="welcomeTitle">
        <div class="checkin__stamp" aria-hidden="true"><span>Checked in</span></div>
        <h1 class="pay__title" id="welcomeTitle">Welcome,<br><?= e($p['first_name']) ?>.</h1>
        <p class="pay__lede">You are checked in to <?= e($summit['short']) ?>, <?= e($summit['edition']) ?>. We are glad you are here.</p>
        <dl class="checkin__facts">
          <div><dt>Name</dt><dd><?= e(trim(($p['title'] ?? '') . ' ' . $p['first_name'] . ' ' . $p['last_name'])) ?></dd></div>
          <div><dt>Reference</dt><dd class="mono"><?= e($p['reference']) ?></dd></div>
          <div><dt>Checked in</dt><dd><?= e(date('j M Y, H:i', $at)) ?></dd></div>
        </dl>
        <p class="checkin__live mono"><span class="checkin__pulse" aria-hidden="true"></span>Live <time data-clock><?= e(date('H:i:s')) ?></time></p>
      </article>
      <script>
        // A ticking clock tells the desk this is the live page, not a screenshot.
        (function () {
          var el = document.querySelector('[data-clock]');
          var pad = function (n) { return String(n).padStart(2, '0'); };
          setInterval(function () { var d = new Date(); el.textContent = pad(d.getHours()) + ':' + pad(d.getMinutes()) + ':' + pad(d.getSeconds()); }, 1000);
        })();
      </script>

    <?php elseif ($state === 'closed'): ?>
      <div class="pay__card">
        <h1 class="pay__title">Check in at the desk</h1>
        <p class="pay__lede">Self check-in is not open right now. Please see the official at the registration desk.</p>
        <p class="pay__fine mono">Already registered and need your pass? <a href="<?= url('/pass') ?>">Find my pass</a>.</p>
      </div>

    <?php else: ?>
      <div class="pay__card">
        <h1 class="pay__title">Check in</h1>
        <?php if ($error !== ''): ?>
          <div class="pay__alert" role="alert"><span><?= e($error) ?></span></div>
        <?php endif; ?>

        <?php if ($person !== null): ?>
          <p class="pay__lede">Welcome, <strong><?= e($person['first_name']) ?></strong>. Tap below to check in as <?= e(trim($person['first_name'] . ' ' . $person['last_name'])) ?>.</p>
          <form class="pay__form" method="post" action="<?= url('/checkin') ?>">
            <?= csrf_field() ?>
            <input type="hidden" name="me" value="1">
            <button type="submit" class="btn btn--stamp btn--lg">
              <span class="btn__label">Check me in</span>
              <span class="btn__arrow" aria-hidden="true"><?= icon_arrow() ?></span>
            </button>
          </form>
          <details class="checkin__other">
            <summary>Not <?= e($person['first_name']) ?>? Check in someone else</summary>
        <?php else: ?>
          <p class="pay__lede">Enter the email address or KingsChat username you registered with, then tap check in.</p>
        <?php endif; ?>

        <form class="pay__form" method="post" action="<?= url('/checkin') ?>">
          <?= csrf_field() ?>
          <div class="field">
            <label for="identifier">Email or KingsChat username</label>
            <input id="identifier" name="identifier" type="text" inputmode="email" autocapitalize="off" autocorrect="off" autocomplete="email" required value="<?= e($identifier ?? '') ?>">
          </div>
          <button type="submit" class="btn <?= $person !== null ? 'btn--outline' : 'btn--stamp' ?> btn--lg">
            <span class="btn__label">Check me in</span>
            <span class="btn__arrow" aria-hidden="true"><?= icon_arrow() ?></span>
          </button>
        </form>

        <?php if ($person !== null): ?>
          </details>
        <?php endif; ?>

        <div class="checkin__new">
          <p><strong>Not registered yet?</strong> Register now. You are checked in as soon as you finish.</p>
          <a class="btn btn--ink" href="<?= url('/register?mode=onsite') ?>">
            <span class="btn__label">Register and check in</span>
            <span class="btn__arrow" aria-hidden="true"><?= icon_arrow() ?></span>
          </a>
        </div>
      </div>
    <?php endif; ?>
  </div>
</section>
