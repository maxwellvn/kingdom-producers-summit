<?php /** @var array $summit @var array $registration @var string $type @var string $amount */
$isEspees = $type === 'espees';
$code = \App\Models\Setting::get('pay_espees_code');
$note = \App\Models\Setting::get($isEspees ? 'pay_espees_note' : 'pay_bank_note');
?>
<section class="reg">
  <div class="container pay">
    <div class="pay__card">
      <p class="mono pay__kicker"><span class="pay__dot" aria-hidden="true"></span> <?= $isEspees ? 'Pay with Espees' : 'Pay by bank transfer' ?></p>
      <h1 class="pay__title">Send &pound;<?= e($amount) ?></h1>

      <?php if ($isEspees): ?>
        <p class="pay__lede">Open your Espees wallet and send <strong>&pound;<?= e($amount) ?></strong> to this Espees code:</p>
        <p class="mono" style="font-size:1.6rem;letter-spacing:2px;color:var(--ink,#1b2242);font-weight:700"><?= e($code) ?></p>
      <?php else: ?>
        <p class="pay__lede">Transfer <strong>&pound;<?= e($amount) ?></strong> to the account below:</p>
        <table style="width:100%;border-collapse:collapse;margin:0 0 8px">
          <tr><td class="mono" style="padding:10px 0;border-top:1px solid rgba(0,0,0,.15);color:#756f60;text-transform:uppercase;font-size:.72rem;letter-spacing:1.5px">Account name</td><td align="right" style="padding:10px 0;border-top:1px solid rgba(0,0,0,.15);font-weight:600"><?= e(\App\Models\Setting::get('pay_bank_account_name')) ?></td></tr>
          <tr><td class="mono" style="padding:10px 0;border-top:1px solid rgba(0,0,0,.15);color:#756f60;text-transform:uppercase;font-size:.72rem;letter-spacing:1.5px">Account number</td><td align="right" class="mono" style="padding:10px 0;border-top:1px solid rgba(0,0,0,.15);font-weight:600"><?= e(\App\Models\Setting::get('pay_bank_number')) ?></td></tr>
          <tr><td class="mono" style="padding:10px 0;border-top:1px solid rgba(0,0,0,.15);border-bottom:1px solid rgba(0,0,0,.15);color:#756f60;text-transform:uppercase;font-size:.72rem;letter-spacing:1.5px">Sort code</td><td align="right" class="mono" style="padding:10px 0;border-top:1px solid rgba(0,0,0,.15);border-bottom:1px solid rgba(0,0,0,.15);font-weight:600"><?= e(\App\Models\Setting::get('pay_bank_sort_code')) ?></td></tr>
        </table>
      <?php endif; ?>

      <div class="pay__alert" role="note" style="margin-top:14px">
        <strong>Use your reference:</strong>
        <span class="mono"><?= e($registration['reference']) ?></span>
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
