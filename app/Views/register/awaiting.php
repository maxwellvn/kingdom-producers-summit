<?php /** @var array $summit @var array $registration */
$paid = payment_phrase($registration);
$reference = (string) $registration['reference'];
?>
<section class="reg">
  <div class="container pay">
    <div class="pay__card">
      <p class="mono pay__kicker"><span class="pay__dot" aria-hidden="true"></span> Payment received — pending confirmation</p>
      <h1 class="pay__title">Thank you — nearly there</h1>

      <p class="pay__lede">We've logged your <strong class="pay__with"><?= payment_icon((string) ($registration['payment_method'] ?? ''), 20) ?><?= e($paid) ?></strong> payment. Our team checks it against our records and confirms it from our end. There is nothing further for you to send.</p>

      <section class="proof" aria-labelledby="proofTitle">
        <h2 class="proof__title" id="proofTitle">Your reference</h2>
        <p class="proof__lede">Quote this if you ever need to contact us about your registration.</p>

        <dl class="proof__rows">
          <div class="proof__row">
            <dt class="mono">Reference</dt>
            <dd>
              <span class="proof__value mono"><?= e($reference) ?></span>
              <button type="button" class="copy-btn" data-copy="<?= e($reference) ?>">Copy</button>
            </dd>
          </div>
        </dl>
      </section>

      <p class="pay__lede">Your QR access pass for attendance is emailed to you the moment your payment is confirmed.</p>

      <p class="form__fine mono">Keep your reference safe — you can check this page anytime from your confirmation email.</p>
    </div>
  </div>
</section>
