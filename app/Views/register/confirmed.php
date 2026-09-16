<?php
/** @var array $registration @var array $summit @var string $accessToken @var string|null $supportUrl */
$r = $registration;
$pathLabel = ['onsite' => 'Attending onsite', 'online' => 'Attending online', 'initiative' => 'Joined the Kingdom Producers initiative'][$r['participation']] ?? $r['participation'];
$next = [
  'onsite' => [
    'Join us on ' . ($summit['date_text'] ?? 'Saturday 19th September 2026, 12 noon') . ' at ' . config('app.summit.venue.unit') . ', ' . config('app.summit.venue.name') . ', ' . config('app.summit.venue.street') . ', Rainham ' . config('app.summit.venue.postcode') . '. Directions are on the summit page.',
    'Your reference code is your ticket reference. Keep it — you will be asked for it at registration on the day.',
    'The programme and travel notes will follow by email.',
  ],
  'online' => [
    'Watch the main sessions by livestream from Rainham, Essex on ' . ($summit['date_text'] ?? 'Saturday 19th September 2026, 12 noon') . '. Open ' . site_url() . '/watch and sign in with this email or your reference.',
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
      <p>Your place is registered. A copy of this pass has been sent to <strong><?= e($r['email']) ?></strong>.</p>
      <p class="mono confirmed__countdown" data-countdown="<?= e($summit['starts_at']) ?>" hidden>Starting in <span></span></p>
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
            <p>On the day, sign in with this email or your reference to watch. There is no pass to bring.</p>
            <a class="btn btn--stamp" href="<?= url('/watch') ?>"><span class="btn__label">Open the live room</span><span class="btn__arrow" aria-hidden="true"><?= icon_arrow() ?></span></a>
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
          <div><dt>Contribution</dt><dd class="pay__with"><?= payment_icon((string) ($r['payment_method'] ?? ''), 18) ?><?= e(payment_phrase($r)) ?></dd></div>
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

      <?php if (!empty($supportUrl)): ?>
        <section class="support" aria-labelledby="supportTitle">
          <span class="mono support__kicker">Optional</span>
          <h3 class="support__title" id="supportTitle">Support the programme</h3>
          <p class="support__lede">If you would like to help fund the summit and the initiative, a contribution of <strong><?= e(espees_price(price_pence((string) $r['participation']))) ?></strong> is suggested. Any amount is welcome, and so is none at all.</p>
          <a class="btn btn--stamp" href="<?= e($supportUrl) ?>"><span class="btn__label">Support the programme</span><span class="btn__arrow" aria-hidden="true"><?= icon_arrow() ?></span></a>
        </section>
      <?php elseif (in_array($r['payment_status'] ?? '', ['paid', 'claimed'], true)): ?>
        <p class="support__thanks mono">Thank you for supporting the programme.</p>
      <?php endif; ?>

<?php
$gcal = 'https://calendar.google.com/calendar/render?' . http_build_query([
  'action' => 'TEMPLATE',
  'text' => $summit['short'] . ' — ' . $summit['edition'],
  'dates' => gmdate('Ymd\THis\Z', strtotime($summit['starts_at'])) . '/' . gmdate('Ymd\THis\Z', strtotime($summit['ends_at'])),
  'location' => $summit['venue']['query'],
  'details' => site_url(),
]);
$shareText = "I've registered for " . $summit['short'] . ' (' . $summit['date_day'] . '). Join me: ' . site_url() . '/register';
?>
      <div class="confirmed__tools">
        <span class="mono confirmed__tools-label">Add to calendar</span>
        <div class="confirmed__tools-row">
          <a class="btn btn--outline" href="<?= e($gcal) ?>" target="_blank" rel="noopener"><span class="btn__label">Google</span></a>
          <a class="btn btn--outline" href="<?= url('/register/calendar.ics') ?>" download><span class="btn__label">Apple / Outlook (.ics)</span></a>
        </div>
        <span class="mono confirmed__tools-label">Invite a friend</span>
        <div class="confirmed__tools-row">
          <a class="btn btn--outline" href="https://wa.me/?text=<?= rawurlencode($shareText) ?>" target="_blank" rel="noopener"><span class="btn__label">WhatsApp</span></a>
          <button type="button" class="btn btn--outline" data-copy="<?= e(site_url() . '/register') ?>"><span class="btn__label">Copy link</span></button>
        </div>
      </div>
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


