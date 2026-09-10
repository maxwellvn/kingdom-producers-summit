<?php /** @var array $summit @var array $registration @var string $kingschat @var string $proofEmail */
$methodLabel = ['espees' => 'Espees', 'revolut' => 'Revolut'][$registration['payment_method'] ?? ''] ?? 'your chosen method';
$amount = espees_price(price_pence((string) $registration['participation']));
$reference = (string) $registration['reference'];
$proofRows = array_filter([
    ['Your reference', $reference, null],
    $kingschat !== '' ? ['KingsChat', $kingschat, null] : null,
    $proofEmail !== '' ? ['Email', $proofEmail, 'mailto:' . $proofEmail . '?subject=' . rawurlencode('Proof of payment ' . $reference)] : null,
]);
?>
<section class="reg">
  <div class="container pay">
    <div class="pay__card">
      <p class="mono pay__kicker"><span class="pay__dot" aria-hidden="true"></span> Payment received — pending confirmation</p>
      <h1 class="pay__title">Thank you — nearly there</h1>

      <p class="pay__lede">We've logged your <?= e($amount) ?> <strong><?= e($methodLabel) ?></strong> payment. Our team is confirming it now.</p>

      <section class="proof" aria-labelledby="proofTitle">
        <h2 class="proof__title" id="proofTitle">Send us your proof of payment</h2>
        <p class="proof__lede">Send a screenshot or receipt to either address below, quoting your reference.</p>

        <dl class="proof__rows">
          <?php foreach ($proofRows as [$label, $value, $href]): ?>
            <div class="proof__row">
              <dt class="mono"><?= e($label) ?></dt>
              <dd>
                <?php if ($href !== null): ?>
                  <a class="proof__value" href="<?= e($href) ?>"><?= e($value) ?></a>
                <?php else: ?>
                  <span class="proof__value mono"><?= e($value) ?></span>
                <?php endif; ?>
                <button type="button" class="copy-btn" data-copy="<?= e($value) ?>">Copy</button>
              </dd>
            </div>
          <?php endforeach; ?>
        </dl>
      </section>

      <p class="pay__lede">Your QR access pass for attendance is emailed to you the moment your payment is confirmed.</p>

      <p class="form__fine mono">Keep your reference safe — you can check this page anytime from your confirmation email.</p>
    </div>
  </div>
</section>
