<?php /** @var array $summit @var array $registration @var string $type @var string $amount */
$isEspees = $type === 'espees';
$code = (string) config('payments.espees.code');
$note = (string) config($isEspees ? 'payments.espees.note' : 'payments.bank.note');
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
      <p class="mono pay__kicker"><span class="pay__dot" aria-hidden="true"></span> <?= $isEspees ? 'Pay with Espees' : 'Pay by bank transfer' ?></p>
      <h1 class="pay__title">Send &pound;<?= e($amount) ?></h1>

      <?php if ($isEspees): ?>
        <p class="pay__lede">Open your Espees wallet and send <strong>&pound;<?= e($amount) ?></strong> to this Espees code:</p>
        <table style="width:100%;border-collapse:collapse;margin:0 0 8px">
          <?php $row('Espees code', $code); ?>
        </table>
      <?php else: ?>
        <p class="pay__lede">Transfer <strong>&pound;<?= e($amount) ?></strong> to:</p>
        <table style="width:100%;border-collapse:collapse;margin:0 0 8px">
          <?php $row('Recipient', (string) config('payments.bank.account_name')); ?>
        </table>
        <p class="pay__lede" style="margin-top:18px"><strong>Transfer from a UK bank</strong></p>
        <table style="width:100%;border-collapse:collapse;margin:0 0 8px">
          <?php $row('Account number', (string) config('payments.bank.account_number')); ?>
          <?php $row('Sort code', (string) config('payments.bank.sort_code')); ?>
        </table>
        <p class="pay__lede" style="margin-top:18px"><strong>Transfer from outside the UK</strong></p>
        <table style="width:100%;border-collapse:collapse;margin:0 0 8px">
          <?php $row('IBAN', (string) config('payments.bank.iban')); ?>
          <?php $row('BIC', (string) config('payments.bank.bic')); ?>
          <?php $row('Intermediary BIC', (string) config('payments.bank.intermediary_bic')); ?>
        </table>
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

      <a class="btn btn--stamp btn--lg" style="display:inline-block;margin-top:6px" href="<?= url('/register/claim?type=' . $type) ?>">
        <span class="btn__label">I have paid</span>
      </a>

      <p class="form__fine mono"><a href="<?= url('/register/method') ?>">Choose a different payment method</a></p>
    </div>
  </div>
</section>
