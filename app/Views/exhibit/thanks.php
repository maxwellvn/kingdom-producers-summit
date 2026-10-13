<?php /** @var string $business */ ?>
<section class="confirmed">
  <div class="confirmed__architecture" aria-hidden="true"></div>
  <div class="container confirmed__intro" data-reveal>
    <p class="mono confirmed__edition"><?= e((string) config('app.summit.short')) ?> · Exhibitors</p>
    <div class="confirmed__headline">
      <h1 class="confirmed__title">Application<br>received.</h1>
      <p>Thank you. We have your application for <?= e($business) ?>. The team reviews every one and will email you as soon as yours is approved, with setup times and what to bring.</p>
    </div>
  </div>
  <div class="container confirmed__grid">
    <aside class="confirmed__next" data-reveal>
      <span class="mono confirmed__section-label">While you wait</span>
      <h2>Register yourself for the day.</h2>
      <div class="confirmed__actions">
        <a class="btn btn--ink" href="<?= url('/register') ?>"><span class="btn__label">Register for the summit</span><span class="btn__arrow" aria-hidden="true"><?= icon_arrow() ?></span></a>
        <a class="btn btn--outline" href="<?= url('/') ?>"><span class="btn__label">Back to the summit</span></a>
      </div>
    </aside>
  </div>
</section>
