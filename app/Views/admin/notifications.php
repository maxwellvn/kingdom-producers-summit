<?php /** @var string $flash @var array $audiences @var array $templates @var array $placeholders @var array $counts @var array $queue @var string $summitStart */
$field = 'width:100%;padding:.6rem .7rem;border:1px solid rgba(0,0,0,.25);background:#fff';
$label = 'display:block;margin-bottom:.35rem;font-size:.72rem;letter-spacing:1.5px;text-transform:uppercase;color:#5C5648';
$start = strtotime($summitStart) ?: time();
$suggest = [
    'One week before' => $start - 7 * 86400,
    'The day before, 6pm' => strtotime('yesterday 18:00', $start),
    'On the morning, 8am' => strtotime('today 08:00', $start),
    'One hour before' => $start - 3600,
];
?>
<section class="adm-page">
  <header class="adm-page__head">
    <div>
      <p class="eyebrow"><span class="eyebrow__dot"></span>Notifications</p>
      <h1 class="adm-page__title">Tell <span class="adm-page__title-sub">people</span></h1>
    </div>
  </header>

  <?php if ($flash !== ''): ?>
    <div class="form__alert" role="status" style="border-color: rgba(46,160,67,.5);margin-bottom:1.2rem"><span><?= e($flash) ?></span></div>
  <?php endif; ?>

  <div class="adm-columns">
    <div class="adm-panel">
      <h2 class="adm-panel__title">Write a message</h2>
      <form method="post" action="<?= url('/admin/notifications') ?>" style="display:grid;gap:1rem">
        <?= csrf_field() ?>

        <label style="display:block">
          <span class="mono" style="<?= $label ?>">Who</span>
          <select name="audience" style="<?= $field ?>">
            <?php foreach ($audiences as $value => $text): ?>
              <option value="<?= e($value) ?>"><?= e($text) ?> (<?= number_format($counts[$value] ?? 0) ?>)</option>
            <?php endforeach; ?>
          </select>
        </label>

        <label style="display:block">
          <span class="mono" style="<?= $label ?>">Ready-made message</span>
          <select name="template" data-announce-template style="<?= $field ?>">
            <option value="">Write my own</option>
            <?php foreach ($templates as $key => $t): ?>
              <option value="<?= e($key) ?>" data-subject="<?= e($t['subject']) ?>" data-body="<?= e($t['body']) ?>"><?= e($t['label']) ?></option>
            <?php endforeach; ?>
          </select>
        </label>

        <label style="display:block">
          <span class="mono" style="<?= $label ?>">Subject</span>
          <input type="text" name="subject" data-announce-subject style="<?= $field ?>">
        </label>

        <label style="display:block">
          <span class="mono" style="<?= $label ?>">Message</span>
          <textarea name="body" rows="9" data-announce-body style="<?= $field ?>;font:inherit"></textarea>
        </label>
        <div style="color:#5C5648;font-size:.85rem">
          <p style="margin:0 0 .4rem">Wrap a part in <code>{online_only}…{/online_only}</code> or <code>{onsite_only}…{/onsite_only}</code> and only that group sees it. <code>{timing_lead}</code> and <code>{timing_detail}</code> say where we are in time when the message goes out ("It's tomorrow", "It starts in 3 hours", "We're live now"). <code>{qr_url}</code> opens an onsite person's own QR pass and <code>{qr_image_url}</code> is the QR image itself; both are blank for online and initiative. <code>{watch_url}</code> is a personal sign-in link and is blank for onsite people; give them <code>{directions_url}</code>. In emails, a line ending in a link becomes a button.</p>
          Click to insert:
          <?php foreach ($placeholders as $ph): ?>
            <button type="button" class="adm-btn" data-insert="{<?= e($ph) ?>}" style="padding:.15rem .5rem;font-size:.7rem;margin:.15rem .1rem 0 0">{<?= e($ph) ?>}</button>
          <?php endforeach; ?>
        </div>

        <div style="display:flex;gap:1.2rem;flex-wrap:wrap">
          <label style="display:flex;gap:.5rem;align-items:center"><input type="checkbox" name="by_email" value="1" checked> <span>Email</span></label>
          <label style="display:flex;gap:.5rem;align-items:center"><input type="checkbox" name="by_kingschat" value="1" checked> <span>KingsChat</span></label>
        </div>

        <fieldset style="border:1px solid rgba(0,0,0,.15);padding:1rem 1.2rem;display:grid;gap:.6rem">
          <legend class="mono" style="padding:0 .5rem;font-size:.72rem;letter-spacing:1.5px;text-transform:uppercase">When</legend>
          <label style="display:flex;gap:.5rem;align-items:center"><input type="radio" name="when" value="now" checked> <span>Send now</span></label>
          <label style="display:flex;gap:.5rem;align-items:center"><input type="radio" name="when" value="later"> <span>Send later, at</span></label>
          <input type="datetime-local" name="send_at" data-send-at style="<?= $field ?>;max-width:18rem" min="<?= date('Y-m-d\TH:i') ?>">
          <div style="display:flex;gap:.4rem;flex-wrap:wrap">
            <?php foreach ($suggest as $text => $ts): ?>
              <button type="button" class="adm-btn" data-when="<?= date('Y-m-d\TH:i', $ts) ?>" style="padding:.2rem .6rem;font-size:.72rem"><?= e($text) ?></button>
            <?php endforeach; ?>
          </div>
          <span style="color:#5C5648;font-size:.85rem">Times are UK time. Scheduled messages go out within a minute of the chosen time.</span>
        </fieldset>

        <button type="submit" class="adm-btn adm-btn--dark" onclick="return confirm('Queue this for everyone in the chosen group?')">Queue it</button>
      </form>
    </div>

    <div class="adm-panel">
      <h2 class="adm-panel__title">Scheduled and sent</h2>
      <?php if (!$queue): ?>
        <p class="adm-muted">Nothing yet.</p>
      <?php else: ?>
      <div class="adm-table-wrap">
        <table class="adm-table">
          <thead><tr><th>When</th><th>Who</th><th>Subject</th><th>Status</th><th></th></tr></thead>
          <tbody>
          <?php foreach ($queue as $a): ?>
            <tr>
              <td class="mono"><?= e(date('D j M, H:i', strtotime($a['send_at']))) ?></td>
              <td><?= e($audiences[$a['audience']] ?? $a['audience']) ?></td>
              <td><?= e($a['subject']) ?><br><span class="adm-muted" style="font-size:.8rem"><?= $a['by_email'] ? 'Email' : '' ?><?= $a['by_email'] && $a['by_kingschat'] ? ' + ' : '' ?><?= $a['by_kingschat'] ? 'KingsChat' : '' ?> · <?= e($a['created_by']) ?></span></td>
              <td><?php if ($a['sent_at'] === null): ?><span class="adm-pill adm-pill--claimed">Scheduled</span>
                  <?php elseif ($a['result'] === 'sending'): $p = \App\Services\Announcer::progress((int) $a['id']); ?><span class="adm-pill adm-pill--claimed">Sending</span><br><span class="adm-muted mono" style="font-size:.8rem"><?= $p['sent'] ?> of <?= $p['total'] ?> sent<?= $p['failed'] ? ', ' . $p['failed'] . ' failed' : '' ?> · continues every minute, waits out mail limits</span>
                  <?php else: ?><span class="adm-pill adm-pill--paid">Sent</span><br><span class="adm-muted" style="font-size:.8rem"><?= e((string) $a['result']) ?></span><?php endif; ?></td>
              <td><?php if ($a['sent_at'] === null): ?>
                <form method="post" action="<?= url('/admin/notifications/cancel') ?>"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $a['id'] ?>"><button type="submit" class="adm-btn" style="padding:.25rem .6rem;font-size:.75rem">Cancel</button></form>
              <?php endif; ?></td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <?php endif; ?>
    </div>
  </div>
</section>

<script>
(function () {
  var picker = document.querySelector('[data-announce-template]');
  var subject = document.querySelector('[data-announce-subject]');
  var body = document.querySelector('[data-announce-body]');
  var sendAt = document.querySelector('[data-send-at]');
  picker.addEventListener('change', function () {
    var o = picker.options[picker.selectedIndex];
    subject.value = o.getAttribute('data-subject') || '';
    body.value = o.getAttribute('data-body') || '';
  });
  document.querySelectorAll('[data-insert]').forEach(function (b) {
    b.addEventListener('click', function () {
      var s = body.selectionStart, t = b.getAttribute('data-insert');
      body.setRangeText(t, s, body.selectionEnd, 'end'); body.focus();
    });
  });
  document.querySelectorAll('[data-when]').forEach(function (b) {
    b.addEventListener('click', function () {
      sendAt.value = b.getAttribute('data-when');
      document.querySelector('input[name="when"][value="later"]').checked = true;
    });
  });
  sendAt.addEventListener('input', function () { document.querySelector('input[name="when"][value="later"]').checked = true; });
})();
</script>
