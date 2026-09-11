<?php
/** @var array $registration @var array $summit @var string $accessToken */
$r = $registration;
$pathLabel = ['onsite' => 'Attending onsite', 'online' => 'Attending online', 'initiative' => 'Joined the Kingdom Producers initiative'][$r['participation']] ?? $r['participation'];
$next = [
  'onsite' => [
    'Join us on ' . ($summit['date_text'] ?? 'Saturday 19th September 2026, 12 noon') . ' — arrival details and the full programme will follow by email.',
    'Your reference code is your ticket reference. Keep it — you will be asked for it at registration on the day.',
    'The programme and travel notes will follow by email.',
  ],
  'online' => [
    'Watch the main sessions by livestream from Rainham, Essex on ' . ($summit['date_text'] ?? 'Saturday 19th September 2026, 12 noon') . ' — your link arrives by email.',
    'The workshops, mentoring, clinic and networking happen in the room and are not part of online access.',
    'If you decide to attend in person later, reply to any of our emails and we will switch you over.',
  ],
  'initiative' => [
    'You are now on the register of Kingdom Producers. Keep your initiative reference safe.',
    'You will be among the first to receive access to the portal as it opens — the repository, directory and opportunities.',
    'Summit updates come to you too; if you want to attend in Essex, tell us by replying to any email.',
  ],
][$r['participation']] ?? [];
?>

<section class="confirmed">
  <div class="confirmed__architecture" aria-hidden="true"></div>
  <div class="container confirmed__intro" data-reveal>
    <p class="mono confirmed__edition"><?= e($summit['short']) ?> · <?= e($summit['edition']) ?></p>
    <div class="confirmed__headline">
      <h1 class="confirmed__title">You're in,<br><?= e($r['first_name']) ?>.</h1>
      <p>Your place is registered. Keep this page or save your reference—the access pass is ready when you arrive.</p>
    </div>
  </div>

  <div class="container confirmed__grid">
    <article class="confirmed__ticket" data-reveal aria-labelledby="access-pass-title">
      <header class="confirmed__ticket-head">
        <div>
          <span class="mono">Official access pass</span>
          <h2 id="access-pass-title"><?= e($pathLabel) ?></h2>
        </div>
        <div class="confirmed__stamp" aria-hidden="true"><span>Registered</span></div>
      </header>

      <?php $onsite = $r['participation'] === 'onsite'; ?>
      <div class="confirmed__pass<?= $onsite ? '' : ' confirmed__pass--noqr' ?>">
        <?php if ($onsite): ?>
        <div class="confirmed__qr-wrap">
          <div class="confirmed__qr">
            <img src="<?= e(url('/access/qr?token=' . rawurlencode($accessToken))) ?>"
                 width="196" height="196" alt="QR access code for <?= e($r['reference']) ?>">
          </div>
          <span class="mono">Scan once at entry</span>
        </div>
        <?php endif; ?>
        <div class="confirmed__pass-copy">
          <span class="mono">Registration reference</span>
          <strong class="confirmed__ref-code" id="refCode"><?= e($r['reference']) ?></strong>
          <button type="button" class="confirmed__copy mono" data-copy="#refCode">Copy reference</button>
          <?php if ($onsite): ?>
            <p>Present this QR code to the attendance team. It confirms your arrival without exposing your registration details.</p>
          <?php elseif ($r['participation'] === 'online'): ?>
            <p>Your live viewing link is sent to this email address before the programme begins. There is no pass to bring, and nothing further to do until then.</p>
          <?php else: ?>
            <p>Keep this reference for any correspondence with us about the initiative.</p>
          <?php endif; ?>
        </div>
      </div>

      <dl class="confirmed__facts mono">
        <div><dt>Name</dt><dd><?= e(trim(($r['title'] ?? '') . ' ' . $r['first_name'] . ' ' . $r['last_name'])) ?></dd></div>
        <div><dt>Email</dt><dd><?= e($r['email']) ?></dd></div>
        <?php if (!empty($r['field'])): ?><div><dt>Field</dt><dd><?= e(field_label($r)) ?></dd></div><?php endif; ?>
        <?php if (!empty($r['producer_stage'])): ?><div><dt>Stage</dt><dd><?= e(ucfirst($r['producer_stage'])) ?></dd></div><?php endif; ?>
        <?php if (($r['payment_status'] ?? '') === 'paid'): ?>
          <div><dt>Paid</dt><dd class="pay__with"><?= payment_icon((string) ($r['payment_method'] ?? ''), 18) ?><?= e(payment_phrase($r)) ?></dd></div>
        <?php endif; ?>
        <div><dt>Registered</dt><dd><?= e(date('j M Y, H:i', strtotime($r['created_at']))) ?></dd></div>
      </dl>
    </article>

    <aside class="confirmed__next" data-reveal>
      <span class="mono confirmed__section-label">What happens next</span>
      <h2>From registration to arrival.</h2>
      <ol class="next__list">
        <?php foreach ($next as $i => $line): ?>
          <li>
            <span class="mono next__num"><?= str_pad((string) ($i + 1), 2, '0', STR_PAD_LEFT) ?></span>
            <p><?= e($line) ?></p>
          </li>
        <?php endforeach; ?>
      </ol>

      <div class="confirmed__actions">
        <a class="btn btn--ink" href="<?= url('/about') ?>"><span class="btn__label">Read about the initiative</span><span class="btn__arrow" aria-hidden="true"><?= icon_arrow() ?></span></a>
        <a class="btn btn--outline" href="<?= url('/') ?>"><span class="btn__label">Back to the summit</span></a>
      </div>

      <p class="confirmed__fine mono">Something wrong? Quote your reference and message us on KingsChat at
        <a href="https://kingschat.online/user/<?= e(contact_kingschat()) ?>" target="_blank" rel="noopener">@<?= e(contact_kingschat()) ?></a>
        or email <a href="mailto:<?= e(contact_email()) ?>"><?= e(contact_email()) ?></a>.</p>
    </aside>
  </div>
</section>


