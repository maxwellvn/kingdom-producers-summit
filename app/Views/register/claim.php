<?php /** @var array $summit @var array $registration @var string $type @var string $amount */
$label = $type === 'espees' ? 'Espees' : 'Revolut';
$paidLabel = amount_for($type, $pence);
?>
<section class="reg">
  <div class="container pay">
    <div class="pay__card">
      <p class="mono pay__kicker"><span class="pay__dot" aria-hidden="true"></span> Confirm your contribution</p>
      <h1 class="pay__title">Have you sent <?= e($paidLabel) ?>?</h1>

      <p class="pay__lede">Please confirm:</p>
      <table style="width:100%;border-collapse:collapse;margin:0 0 18px">
        <tr><td class="mono" style="padding:10px 0;border-top:1px solid rgba(0,0,0,.15);color:#756f60;text-transform:uppercase;font-size:.72rem;letter-spacing:1.5px">Method</td><td align="right" style="padding:10px 0;border-top:1px solid rgba(0,0,0,.15);font-weight:600"><?= e(ucfirst($label)) ?></td></tr>
        <tr><td class="mono" style="padding:10px 0;border-top:1px solid rgba(0,0,0,.15);color:#756f60;text-transform:uppercase;font-size:.72rem;letter-spacing:1.5px">Amount</td><td align="right" style="padding:10px 0;border-top:1px solid rgba(0,0,0,.15);font-weight:600"><?= e($paidLabel) ?></td></tr>
        <tr><td class="mono" style="padding:10px 0;border-top:1px solid rgba(0,0,0,.15);border-bottom:1px solid rgba(0,0,0,.15);color:#756f60;text-transform:uppercase;font-size:.72rem;letter-spacing:1.5px">Reference</td><td align="right" class="mono" style="padding:10px 0;border-top:1px solid rgba(0,0,0,.15);border-bottom:1px solid rgba(0,0,0,.15);font-weight:600"><?= e($registration['reference']) ?></td></tr>
      </table>

      <form method="post" action="<?= url('/register/claim') ?>">
        <?= csrf_field() ?>
        <input type="hidden" name="type" value="<?= e($type) ?>">
        <input type="hidden" name="resume" value="<?= e($resume) ?>">
        <button type="submit" class="btn btn--stamp btn--lg">
          <span class="btn__label">Confirm — I have sent <?= e($paidLabel) ?></span>
        </button>
      </form>

      <p class="form__fine mono">Only confirm once the money has actually left your account. <a href="<?= url('/register/instructions?type=' . $type . '&resume=' . rawurlencode($resume)) ?>">Back to payment details</a></p>
    </div>
  </div>
</section>
