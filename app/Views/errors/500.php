<section class="errpage section">
  <div class="container errpage__inner">
    <span class="errpage__code mono">500</span>
    <h1 class="errpage__title">Something went wrong on our side.</h1>
    <p>We've logged it. Please try again in a moment.</p>
    <?php if (!empty($detail)): ?><p class="mono" style="font-size:.8rem;opacity:.7"><?= e($detail) ?></p><?php endif; ?>
    <a class="btn btn--ink" href="<?= url('/') ?>"><span class="btn__label">Back to the summit</span><span class="btn__arrow" aria-hidden="true"><?= icon_arrow() ?></span></a>
  </div>
</section>
