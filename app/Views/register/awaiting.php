<?php /** @var array $summit @var array $registration @var string $kingschat @var string $proofEmail */
$methodLabel = ['espees' => 'Espees', 'bank' => 'bank transfer', 'paypal' => 'PayPal'][$registration['payment_method'] ?? ''] ?? 'your chosen method';
?>
<section class="reg">
  <div class="container pay">
    <div class="pay__card">
      <p class="mono pay__kicker"><span class="pay__dot" aria-hidden="true"></span> Payment received — pending confirmation</p>
      <h1 class="pay__title">Thank you — nearly there</h1>

      <p class="pay__lede">We've logged your &pound;50 <strong><?= e($methodLabel) ?></strong> payment for reference <strong class="mono"><?= e($registration['reference']) ?></strong>. Our team is confirming it now.</p>

      <div class="pay__alert" role="note">
        <strong>Send your proof of payment.</strong>
        <span>Message it to us with your reference <strong class="mono"><?= e($registration['reference']) ?></strong> <button type="button" class="copy-btn" data-copy="<?= e($registration['reference']) ?>">Copy</button></span>
        <?php if ($kingschat !== ''): ?>
          <span>&bull; KingsChat: <strong><?= e($kingschat) ?></strong> <button type="button" class="copy-btn" data-copy="<?= e($kingschat) ?>">Copy</button></span>
        <?php endif; ?>
        <?php if ($proofEmail !== ''): ?>
          <span>&bull; Email: <a href="mailto:<?= e($proofEmail) ?>?subject=<?= rawurlencode('Proof of payment ' . $registration['reference']) ?>"><?= e($proofEmail) ?></a> <button type="button" class="copy-btn" data-copy="<?= e($proofEmail) ?>">Copy</button></span>
        <?php endif; ?>
      </div>

      <p class="pay__lede">Your QR access pass for attendance is emailed to you the moment your payment is confirmed.</p>

      <p class="form__fine mono">Keep your reference safe — you can check this page anytime from your confirmation email.</p>
    </div>
  </div>
</section>
