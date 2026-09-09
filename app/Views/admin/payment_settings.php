<?php /** @var array $flash @var bool $paypalLive */
use App\Models\Setting;
$espeesOn = Setting::get('pay_espees_enabled', '1') === '1';
$paypalOn = Setting::get('pay_paypal_enabled', '1') === '1';
$bankOn = Setting::get('pay_bank_enabled', '1') === '1';
$toggle = static function (string $key, bool $on, string $label): void {
    $checked = $on ? ' checked' : '';
    echo '<label style="display:flex;gap:.6rem;align-items:center;font-size:.95rem">'
        . '<input type="checkbox" name="' . e($key) . '" value="1"' . $checked . '>'
        . '<span>' . e($label) . '</span></label>';
};
$row = static function (string $label, string $value): void {
    if (trim($value) === '') {
        return;
    }
    echo '<div style="display:flex;justify-content:space-between;gap:1rem;padding:.45rem 0;border-top:1px solid rgba(0,0,0,.12)">'
        . '<span class="mono" style="font-size:.72rem;letter-spacing:1.5px;text-transform:uppercase;color:#756f60">' . e($label) . '</span>'
        . '<span style="text-align:right;font-weight:600">' . e($value) . '</span></div>';
};
?>
<section class="adm-page">
  <header class="adm-page__head">
    <div>
      <p class="eyebrow"><span class="eyebrow__dot"></span>Payments</p>
      <h1 class="adm-page__title">Payment <span class="adm-page__title-sub">methods</span></h1>
    </div>
  </header>

  <?php if ($flash !== ''): ?>
    <div class="form__alert" role="status" style="border-color: rgba(46,160,67,.5)">
      <span><?= e($flash) ?></span>
    </div>
  <?php endif; ?>

  <p class="adm-muted mono" style="margin-bottom:1.2rem">Details are maintained in <strong>config/payments.php</strong> (edit + redeploy to change them). The switches below go live immediately.</p>

  <form method="post" action="<?= url('/admin/payments') ?>" style="display:flex;flex-direction:column;gap:1.6rem;max-width:720px">
    <?= csrf_field() ?>

    <fieldset style="border:1px solid rgba(0,0,0,.15);padding:1.4rem">
      <legend class="mono" style="padding:0 .6rem;font-size:.8rem;letter-spacing:2px;text-transform:uppercase">Espees — order: Espees &rarr; PayPal &rarr; Bank</legend>
      <?php $toggle('pay_espees_enabled', $espeesOn, $espeesOn ? 'On — offered to registrants' : 'Off — hidden from registrants'); ?>
      <?php $row('Espees code', trim((string) config('payments.espees.code')) !== '' ? config('payments.espees.code') : 'Not set yet — add it in config/payments.php'); ?>
    </fieldset>

    <fieldset style="border:1px solid rgba(0,0,0,.15);padding:1.4rem">
      <legend class="mono" style="padding:0 .6rem;font-size:.8rem;letter-spacing:2px;text-transform:uppercase">PayPal — direct integration</legend>
      <?php $toggle('pay_paypal_enabled', $paypalOn, $paypalOn ? 'On — offered to registrants' : 'Off — hidden from registrants'); ?>
      <?php $row('Credentials', $paypalLive ? 'Configured via env' : 'Missing — set PAYPAL_CLIENT_ID / PAYPAL_SECRET'); ?>
      <?php $row('Mode', (string) config('paypal.mode')); ?>
    </fieldset>

    <fieldset style="border:1px solid rgba(0,0,0,.15);padding:1.4rem">
      <legend class="mono" style="padding:0 .6rem;font-size:.8rem;letter-spacing:2px;text-transform:uppercase">Bank transfer</legend>
      <?php $toggle('pay_bank_enabled', $bankOn, $bankOn ? 'On — offered to registrants' : 'Off — hidden from registrants'); ?>
      <?php $row('Recipient', (string) config('payments.bank.account_name')); ?>
      <?php $row('Address', (string) config('payments.bank.account_address')); ?>
      <?php $row('Account number', (string) config('payments.bank.account_number')); ?>
      <?php $row('Sort code', (string) config('payments.bank.sort_code')); ?>
      <?php $row('IBAN', (string) config('payments.bank.iban')); ?>
      <?php $row('BIC', (string) config('payments.bank.bic')); ?>
      <?php $row('Intermediary BIC', (string) config('payments.bank.intermediary_bic')); ?>
    </fieldset>

    <fieldset style="border:1px solid rgba(0,0,0,.15);padding:1.4rem">
      <legend class="mono" style="padding:0 .6rem;font-size:.8rem;letter-spacing:2px;text-transform:uppercase">Proof of payment — shown after a claim</legend>
      <?php $row('KingsChat', trim((string) config('payments.proof.kingschat')) !== '' ? config('payments.proof.kingschat') : 'Not set'); ?>
      <?php $row('Email', trim((string) config('payments.proof.email')) !== '' ? config('payments.proof.email') : (string) config('app.mail.reply_to')); ?>
    </fieldset>

    <div><button type="submit" class="adm-btn adm-btn--dark">Save switches</button></div>
  </form>
</section>
