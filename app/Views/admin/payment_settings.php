<?php /** @var array $flash @var bool $paymentsLive */
use App\Models\Setting;
use App\Services\PaymentService;
$espeesOn = Setting::get('pay_espees_enabled', '1') === '1';
$revolutOn = Setting::get('pay_revolut_enabled', '1') === '1';
$stripeOn = Setting::get('pay_stripe_enabled', '1') === '1';
$stripeReady = App\Services\StripeClient::configured();
$stripeWebhook = trim((string) config('payments.stripe.webhook_secret')) !== '';
$stripeKeyKind = str_starts_with((string) config('payments.stripe.secret'), 'sk_live_') ? 'live' : 'test';
$paidPaths = array_values(array_filter(App\Models\Registration::PARTICIPATION, 'is_paid_path'));
$textField = static function (string $key, string $label, string $value, string $hint = ''): void {
    echo '<label style="display:block;margin-top:1rem;font-size:.95rem">'
        . '<span class="mono" style="display:block;margin-bottom:.35rem;font-size:.72rem;letter-spacing:1.5px;text-transform:uppercase;color:#756f60">' . e($label) . '</span>'
        . '<input type="text" name="' . e($key) . '" value="' . e($value) . '" style="width:100%;padding:.6rem .7rem;border:1px solid rgba(0,0,0,.25);background:#fff">'
        . ($hint !== '' ? '<span style="display:block;margin-top:.35rem;color:#5C5648;font-size:.85rem">' . e($hint) . '</span>' : '')
        . '</label>';
};
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
      <p class="eyebrow"><span class="eyebrow__dot"></span>Support</p>
      <h1 class="adm-page__title">Ways <span class="adm-page__title-sub">to give</span></h1>
    </div>
  </header>

  <?php if ($flash !== ''): ?>
    <div class="form__alert" role="status" style="border-color: rgba(46,160,67,.5)">
      <span><?= e($flash) ?></span>
    </div>
  <?php endif; ?>

  <p class="adm-muted mono" style="margin-bottom:1.2rem">Everything on this page goes live as soon as you save.</p>

  <form method="post" action="<?= url('/admin/payments') ?>" style="display:flex;flex-direction:column;gap:1.6rem;max-width:720px">
    <?= csrf_field() ?>

    <fieldset style="border:1px solid rgba(0,0,0,.15);padding:1.4rem">
      <legend class="mono" style="padding:0 .6rem;font-size:.8rem;letter-spacing:2px;text-transform:uppercase">Card via Stripe — shown first</legend>
      <?php $toggle('pay_stripe_enabled', $stripeOn, $stripeOn ? 'On — offered to registrants' : 'Off — hidden from registrants'); ?>
      <?php $row('Keys', $stripeReady ? 'Set (' . $stripeKeyKind . ' mode)' : 'Not set — add STRIPE_SECRET_KEY to the environment'); ?>
      <?php $row('Webhook', $stripeWebhook ? 'Signing secret set' : 'Not set — add the endpoint ' . rtrim(site_url(), '/') . '/webhooks/stripe in the Stripe dashboard and put its signing secret in STRIPE_WEBHOOK_SECRET'); ?>
      <?php $row('Charges', 'Suggested contributions: onsite ' . espees_price(price_pence('onsite')) . ', online ' . espees_price(price_pence('online')) . ', taken in pounds sterling. The contribution is recorded against the registration and a thank-you goes out the moment Stripe reports it.'); ?>
    </fieldset>

    <fieldset style="border:1px solid rgba(0,0,0,.15);padding:1.4rem">
      <legend class="mono" style="padding:0 .6rem;font-size:.8rem;letter-spacing:2px;text-transform:uppercase">Espees</legend>
      <?php $toggle('pay_espees_enabled', $espeesOn, $espeesOn ? 'On — offered to registrants' : 'Off — hidden from registrants'); ?>
      <?php $textField('pay_espees_code', 'Espees code', PaymentService::espeesCode(), 'Registrants see this code with a copy button.'); ?>
    </fieldset>

    <fieldset style="border:1px solid rgba(0,0,0,.15);padding:1.4rem">
      <legend class="mono" style="padding:0 .6rem;font-size:.8rem;letter-spacing:2px;text-transform:uppercase">Revolut checkout</legend>
      <?php $toggle('pay_revolut_enabled', $revolutOn, $revolutOn ? 'On — offered to registrants' : 'Off — hidden from registrants'); ?>
      <?php
      // One link cannot charge two different amounts, so say so plainly.
      $links = [];
      foreach ($paidPaths as $path) {
          $links[$path] = PaymentService::revolutUrl(price_pence($path));
      }
      $shared = count($paidPaths) > 1 && count(array_unique(array_filter($links))) === 1;
      ?>
      <?php if ($shared): ?>
        <div class="form__alert" role="alert" style="margin-bottom:1rem">
          <strong>The same link is set for both suggested amounts.</strong>
          <span>A Revolut checkout link charges the amount it was created for, so one link cannot take
          <?= e(espees_price(price_pence('onsite'))) ?> from onsite supporters and
          <?= e(espees_price(price_pence('online'))) ?> from online ones. Create a second link and paste it below.</span>
        </div>
      <?php endif; ?>

      <?php foreach ($paidPaths as $path): $pence = price_pence($path); ?>
        <?php $textField(
            'pay_revolut_url_' . $pence,
            ucfirst($path) . ' link (' . espees_price($pence) . ')',
            PaymentService::revolutUrl($pence),
            'A Revolut checkout link is normally fixed-amount, so give each suggested amount its own link.'
        ); ?>
      <?php endforeach; ?>
    </fieldset>

    <fieldset style="border:1px solid rgba(0,0,0,.15);padding:1.4rem">
      <legend class="mono" style="padding:0 .6rem;font-size:.8rem;letter-spacing:2px;text-transform:uppercase">Contact — for enquiries only</legend>
      <?php $row('KingsChat', trim((string) config('payments.proof.kingschat')) !== '' ? config('payments.proof.kingschat') : 'Not set'); ?>
      <?php $row('Email', trim((string) config('payments.proof.email')) !== '' ? config('payments.proof.email') : (string) config('app.mail.reply_to')); ?>
      <p class="mono" style="margin:.8rem 0 0;font-size:.75rem;opacity:.7">Supporters are never asked to send proof. Confirm each claimed contribution yourself under Registrations.</p>
    </fieldset>

    <div><button type="submit" class="adm-btn adm-btn--dark">Save switches</button></div>
  </form>
</section>
