<?php /** @var array $summit @var array $registration @var string $type @var string $amount */
$isEspees = $type === 'espees';
$code = \App\Services\PaymentService::espeesCode();
$revolutUrl = \App\Services\PaymentService::revolutUrl(price_pence((string) $registration['participation']));
$note = (string) config($isEspees ? 'payments.espees.note' : 'payments.revolut.note');
$reference = (string) $registration['reference'];
$cell = 'padding:10px 0;border-top:1px solid rgba(0,0,0,.15);color:#756f60;text-transform:uppercase;font-size:.72rem;letter-spacing:1.5px';
$cellR = 'padding:10px 0;border-top:1px solid rgba(0,0,0,.15);font-weight:600';
$row = static function (string $label, string $value) use ($cell, $cellR): void {
    echo '<tr>'
        . '<td class="mono" style="' . $cell . '">' . e($label) . '</td>'
        . '<td align="right" style="' . $cellR . '"><span class="mono">' . e($value) . '</span> '
        . '<button type="button" class="copy-btn" data-copy="' . e($value) . '">Copy</button></td></tr>';
};
?>
<section class="reg">
  <div class="container pay">
    <div class="pay__card">
      <p class="mono pay__kicker"><span class="pay__dot" aria-hidden="true"></span> <?= $isEspees ? 'Pay with Espees' : 'Pay by card or bank' ?></p>
      <h1 class="pay__title">Send <?= e($amount) ?> Espees</h1>

      <?php if ($isEspees): ?>
        <p class="pay__lede">Open your Espees wallet and send <strong><?= e($amount) ?> Espees</strong> to this Espees code:</p>
        <table style="width:100%;border-collapse:collapse;margin:0 0 8px">
          <?php $row('Espees code', $code); ?>
        </table>
      <?php else: ?>
        <p class="pay__lede">Pay <strong><?= e($amount) ?> Espees</strong> on Revolut's secure checkout page. You can pay by card or from your bank.</p>
        <p style="margin:18px 0 6px">
          <a class="btn btn--stamp btn--lg" style="display:inline-block" href="<?= e($revolutUrl) ?>" target="_blank" rel="noopener noreferrer">
            <span class="btn__label">Open the Revolut checkout</span>
          </a>
        </p>
        <p class="form__fine mono">The page opens in a new tab. Come back here once you have paid.</p>
      <?php endif; ?>

      <div class="pay__alert" role="note" style="margin-top:14px">
        <strong>Use your reference:</strong>
        <span class="mono"><?= e($reference) ?> <button type="button" class="copy-btn" data-copy="<?= e($reference) ?>">Copy</button></span>
        <span>Include it in the payment note so we can match your money to your place.</span>
      </div>

      <?php if ($note !== ''): ?>
        <p class="pay__lede" style="margin-top:14px"><?= e($note) ?></p>
      <?php endif; ?>

      <p class="pay__lede" style="margin-top:18px"><strong>Already sent it?</strong></p>

      <a class="btn btn--stamp btn--lg" style="display:inline-block;margin-top:6px" href="<?= url('/register/claim?type=' . $type . '&resume=' . rawurlencode($resume)) ?>">
        <span class="btn__label">I have paid</span>
      </a>

      <p class="form__fine mono"><a href="<?= url('/register/method?resume=' . rawurlencode($resume)) ?>">Choose a different payment method</a></p>
    </div>
  </div>
</section>
