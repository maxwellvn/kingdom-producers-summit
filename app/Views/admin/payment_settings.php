<?php /** @var array $values @var string $flash @var bool $paypalLive */
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

  <form method="post" action="<?= url('/admin/payments') ?>" style="display:flex;flex-direction:column;gap:1.6rem;max-width:720px">
    <?= csrf_field() ?>

    <?php
    $text = static function (string $key, string $label, string $placeholder = '') use ($values): void {
        $value = e($values[$key] ?? '');
        echo '<div style="display:flex;flex-direction:column;gap:.3rem">'
            . '<label class="mono" style="font-size:.72rem;letter-spacing:1.5px;text-transform:uppercase;color:#756f60">' . e($label) . '</label>'
            . '<input type="text" name="' . e($key) . '" value="' . $value . '" placeholder="' . e($placeholder) . '">'
            . '</div>';
    };
    $toggle = static function (string $key, string $label) use ($values): void {
        $checked = ($values[$key] ?? '1') === '1' ? ' checked' : '';
        echo '<label style="display:flex;gap:.6rem;align-items:center;font-size:.95rem">'
            . '<input type="checkbox" name="' . e($key) . '" value="1"' . $checked . '>'
            . '<span>' . e($label) . '</span></label>';
    };
    ?>

    <fieldset style="border:1px solid rgba(0,0,0,.15);padding:1.4rem;display:flex;flex-direction:column;gap:.9rem">
      <legend class="mono" style="padding:0 .6rem;font-size:.8rem;letter-spacing:2px;text-transform:uppercase">Offer methods — always in this order: Espees &rarr; PayPal &rarr; Bank</legend>
      <?php $toggle('pay_espees_enabled', 'Espees (shown first — needs an Espees code below)'); ?>
      <?php $toggle('pay_paypal_enabled', 'PayPal — ' . ($paypalLive ? 'credentials configured' : 'no credentials yet, hidden on the site')); ?>
      <?php $toggle('pay_bank_enabled', 'Bank transfer (needs account name + number below)'); ?>
    </fieldset>

    <fieldset style="border:1px solid rgba(0,0,0,.15);padding:1.4rem;display:flex;flex-direction:column;gap:.9rem">
      <legend class="mono" style="padding:0 .6rem;font-size:.8rem;letter-spacing:2px;text-transform:uppercase">Espees</legend>
      <?php $text('pay_espees_code', 'Espees code to display', 'e.g. KINGDOMPRODUCERS'); ?>
      <?php $text('pay_espees_note', 'Optional note shown under the code'); ?>
    </fieldset>

    <fieldset style="border:1px solid rgba(0,0,0,.15);padding:1.4rem;display:flex;flex-direction:column;gap:.9rem">
      <legend class="mono" style="padding:0 .6rem;font-size:.8rem;letter-spacing:2px;text-transform:uppercase">Bank transfer</legend>
      <?php $text('pay_bank_account_name', 'Account name'); ?>
      <?php $text('pay_bank_number', 'Account number', 'e.g. 12345678'); ?>
      <?php $text('pay_bank_sort_code', 'Sort code', 'e.g. 00-00-00'); ?>
      <?php $text('pay_bank_note', 'Optional note (bank name, reference guidance)'); ?>
    </fieldset>

    <fieldset style="border:1px solid rgba(0,0,0,.15);padding:1.4rem;display:flex;flex-direction:column;gap:.9rem">
      <legend class="mono" style="padding:0 .6rem;font-size:.8rem;letter-spacing:2px;text-transform:uppercase">Proof of payment — where registrants send receipts</legend>
      <?php $text('pay_proof_kingschat', 'KingsChat account', 'e.g. @kingdomproducers'); ?>
      <?php $text('pay_proof_email', 'Email address', 'e.g. payments@loveworldconsulate.org'); ?>
    </fieldset>

    <div><button type="submit" class="adm-btn adm-btn--dark">Save payment methods</button></div>
  </form>
</section>
