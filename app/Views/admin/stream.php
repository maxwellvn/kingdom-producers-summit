<?php
/** @var bool $live @var string $url @var string $kind @var string $streamTitle @var string $note
 *  @var bool $proxy @var array $watchers @var array $holding @var array $holdingStates
 *  @var string $startsAtValue @var string $headlineValue @var string $messageValue @var string $nowValue
 *  @var array $prompts @var bool $commentsOn @var int $commentCount @var array $comments @var string $flash */
$kindLabel = ['hls' => 'HLS stream (.m3u8)', 'iframe' => 'Embedded player', 'file' => 'Video file'][$kind] ?? 'nothing yet';
$state = $live ? 'live' : $holding['state'];
$stateLabel = ['live' => 'Live', 'soon' => 'Starting soon', 'paused' => 'Paused', 'ended' => 'Ended'][$state];
$stateHelp = [
    'live'   => 'Viewers are watching the video now.',
    'soon'   => 'Viewers see the "Starting soon" screen' . ($holding['starts_at'] ? ' with a countdown to ' . $holding['starts_text'] : '') . '.',
    'paused' => 'Viewers see "Back shortly". The video is off.',
    'ended'  => 'Viewers see the closing screen. The video is off.',
][$state];
$hasSource = $url !== '';
$field = 'width:100%;padding:.6rem .7rem;border:1px solid rgba(0,0,0,.25);background:#fff;font:inherit';
$label = 'display:block;margin-bottom:.35rem;font-size:.72rem;letter-spacing:1.5px;text-transform:uppercase;color:#5C5648';
?>
<section class="adm-page">
  <header class="adm-page__head">
    <div>
      <p class="eyebrow"><span class="eyebrow__dot"></span>Live</p>
      <h1 class="adm-page__title">Stream</h1>
    </div>
    <a class="adm-btn" href="<?= url('/watch') ?>" target="_blank" rel="noopener">Open the watch page ↗</a>
  </header>

  <?php if ($flash !== ''): ?>
    <div class="form__alert" role="status" style="border-color: rgba(46,160,67,.5)"><span><?= e($flash) ?></span></div>
  <?php endif; ?>

  <!-- 1. Control bar: what viewers see right now, and one button to change it. -->
  <div class="control control--<?= e($state) ?>">
    <div class="control__status">
      <span class="control__dot" aria-hidden="true"></span>
      <div>
        <p class="control__state"><?= e($stateLabel) ?></p>
        <p class="control__help"><?= e($stateHelp) ?></p>
      </div>
    </div>
    <p class="control__count mono"><?= count($watchers) ?> watching</p>
    <div class="control__actions">
      <?php $act = static function (string $action, string $labelText, string $class = 'adm-btn', bool $disabled = false, string $confirm = ''): void {
          echo '<form method="post" action="' . url('/admin/stream/state') . '"' . ($confirm !== '' ? ' onsubmit="return confirm(\'' . e($confirm) . '\')"' : '') . '>'
              . csrf_field() . '<input type="hidden" name="action" value="' . e($action) . '">'
              . '<button type="submit" class="' . e($class) . '"' . ($disabled ? ' disabled' : '') . '>' . e($labelText) . '</button></form>';
      }; ?>
      <?php if ($state === 'live'): ?>
        <?php $act('pause', 'Pause'); ?>
        <?php $act('end', 'End stream', 'adm-btn adm-btn--danger', false, 'End the stream? Viewers will see the closing screen.'); ?>
      <?php elseif ($state === 'paused'): ?>
        <?php $act('live', 'Resume · go live', 'adm-btn adm-btn--live', !$hasSource); ?>
        <?php $act('end', 'End stream', 'adm-btn adm-btn--danger', false, 'End the stream? Viewers will see the closing screen.'); ?>
      <?php elseif ($state === 'ended'): ?>
        <?php $act('soon', 'Reopen as starting soon'); ?>
        <?php $act('live', 'Go live', 'adm-btn adm-btn--live', !$hasSource); ?>
      <?php else: ?>
        <?php $act('live', 'Go live', 'adm-btn adm-btn--live', !$hasSource); ?>
        <?php $act('end', 'End'); ?>
      <?php endif; ?>
    </div>
    <?php if (!$hasSource): ?>
      <p class="control__warn mono">Add the stream link under Setup before you can go live.</p>
    <?php endif; ?>
  </div>

  <div class="adm-columns adm-columns--2" style="margin-top:1.4rem">
    <!-- 1b. Health: this box and the video relay, as bars. Green is fine, amber is near the limit, red is trouble. -->
  <div data-server-load data-status-url="<?= url('/admin/stream/load-test') ?>"
       style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:.9rem 1.4rem;margin:.8rem 0 1.6rem;padding:.9rem 1rem;background:#FBF8F0;border:1px solid rgba(27,34,66,.14)">
    <?php
    $gauges = [
        ['cpu', 'Server CPU', 'load / cores'],
        ['mem', 'Server memory', 'in use'],
        ['viewers', 'Viewers', 'of ~500 relay capacity'],
        ['relay', 'Video relay', 'response time'],
    ];
    foreach ($gauges as [$key, $name, $sub]):
        if ($key === 'relay' && !env('STREAM_RELAY_URL', '')) continue; ?>
    <div data-g="<?= $key ?>">
      <div style="display:flex;justify-content:space-between;align-items:baseline;gap:.5rem">
        <span class="mono" style="font-size:.7rem;letter-spacing:1.5px;text-transform:uppercase;color:#5C5648"><?= $name ?></span>
        <strong class="mono" data-g-value style="font-size:.85rem">–</strong>
      </div>
      <div style="height:8px;margin:.4rem 0 .25rem;background:rgba(27,34,66,.12);overflow:hidden">
        <div data-g-bar style="height:100%;width:0;background:#9E9E9E;transition:width .4s ease-out,background-color .4s ease-out"></div>
      </div>
      <span class="mono" data-g-note style="font-size:.7rem;color:#5C5648"><?= $sub ?></span>
    </div>
    <?php endforeach; ?>
  </div>

  <!-- 2. Setup: the source and the heading. Rarely changes. -->
    <div class="adm-panel">
      <h2 class="adm-panel__title" style="margin-bottom:.3rem">Setup</h2>
      <p class="adm-muted" style="margin:0 0 1rem;font-size:.9rem">Where the video comes from. Set this once; then use the buttons above on the day.</p>
      <form method="post" action="<?= url('/admin/stream') ?>" style="display:grid;gap:1rem" id="streamSettings">
        <?= csrf_field() ?>
        <label style="display:block">
          <span class="mono" style="<?= $label ?>">Stream link</span>
          <input type="url" name="stream_url" value="<?= e($url) ?>" placeholder="https://…/index.m3u8 or a YouTube link" style="<?= $field ?>">
          <span style="display:block;margin-top:.35rem;color:#5C5648;font-size:.85rem">Detected: <strong><?= e($kindLabel) ?></strong>. HLS, YouTube, Vimeo, Facebook, Twitch or a video file.</span>
        </label>
        <label style="display:block">
          <span class="mono" style="<?= $label ?>">Standard quality link (optional)</span>
          <input type="url" name="stream_url_sd" value="<?= e($urlSd ?? '') ?>" placeholder="https://…/master.m3u8 at a lower bitrate" style="<?= $field ?>">
          <span style="display:block;margin-top:.35rem;color:#5C5648;font-size:.85rem">HLS only. When set, viewers get HD and Standard buttons on the video. Their open pages refresh themselves within seconds.</span>
        </label>
        <label style="display:flex;gap:.6rem;align-items:flex-start;font-size:.92rem">
          <input type="checkbox" name="stream_proxy" value="1" <?= $proxy ? 'checked' : '' ?> style="margin-top:.25rem">
          <span>Hide the stream address from viewers. With a video relay configured the relay server carries the video; without one this server does, which costs capacity. No effect on YouTube or Vimeo.</span>
        </label>
        <label style="display:block">
          <span class="mono" style="<?= $label ?>">Heading on the watch page</span>
          <input type="text" name="stream_title" value="<?= e($streamTitle) ?>" style="<?= $field ?>">
        </label>
        <input type="hidden" name="stream_note" value="<?= e($note) ?>">
        <div><button type="submit" class="adm-btn adm-btn--dark">Save setup</button></div>
      </form>
    </div>

    <!-- 3. Holding screen: what viewers see whenever the video is off. -->
    <div class="adm-panel">
      <h2 class="adm-panel__title" style="margin-bottom:.3rem">Holding screen</h2>
      <p class="adm-muted" style="margin:0 0 1rem;font-size:.9rem">Shown whenever the video is off: before you go live, while paused, and after the end. Viewers' screens update within fifteen seconds.</p>
      <form method="post" action="<?= url('/admin/stream') ?>" style="display:grid;gap:1rem">
        <?= csrf_field() ?>
        <input type="hidden" name="stream_url" value="<?= e($url) ?>">
        <input type="hidden" name="stream_url_sd" value="<?= e($urlSd ?? '') ?>">
        <input type="hidden" name="stream_title" value="<?= e($streamTitle) ?>">
        <input type="hidden" name="stream_note" value="<?= e($note) ?>">
        <?php if ($proxy): ?><input type="hidden" name="stream_proxy" value="1"><?php endif; ?>
        <label style="display:block">
          <span class="mono" style="<?= $label ?>">Scheduled start</span>
          <input type="datetime-local" name="stream_starts_at" value="<?= e($startsAtValue) ?>" style="padding:.6rem .7rem;border:1px solid rgba(0,0,0,.25);background:#fff;font:inherit">
          <span style="display:block;margin-top:.35rem;color:#5C5648;font-size:.85rem">Shows a countdown on the "Starting soon" screen. Going live is still your button above; the countdown does not start the video.</span>
        </label>
        <label style="display:block">
          <span class="mono" style="<?= $label ?>">Headline <span style="text-transform:none;letter-spacing:0;color:#756f60">(optional)</span></span>
          <input type="text" name="stream_headline" value="<?= e($headlineValue) ?>" maxlength="120" placeholder="<?= e($holding['headline']) ?>" style="<?= $field ?>">
        </label>
        <label style="display:block">
          <span class="mono" style="<?= $label ?>">Message <span style="text-transform:none;letter-spacing:0;color:#756f60">(optional)</span></span>
          <textarea name="stream_message" rows="2" maxlength="300" placeholder="<?= e($holding['message']) ?>" style="<?= $field ?>"><?= e($messageValue) ?></textarea>
        </label>
        <label style="display:block">
          <span class="mono" style="<?= $label ?>">Now / next <span style="text-transform:none;letter-spacing:0;color:#756f60">(optional, shows while live too)</span></span>
          <input type="text" name="stream_now" value="<?= e($nowValue) ?>" maxlength="160" placeholder="Now: Opening session · Next: Masterclass, 13:00" style="<?= $field ?>">
        </label>
        <div><button type="submit" class="adm-btn adm-btn--dark">Save holding screen</button></div>
      </form>
    </div>
  </div>

  <!-- 4. Polls and questions -->
  <section id="prompts" class="adm-panel" style="margin-top:1.4rem" data-prompts data-results-url="<?= url('/admin/prompts/results') ?>">
    <h2 class="adm-panel__title" style="margin-bottom:.3rem">Polls &amp; questions</h2>
    <p class="adm-muted" style="margin:0 0 1rem;font-size:.9rem">Pops up on every viewer's screen within seconds. One at a time: posting a new one closes the last. Each person answers once. Poll results show to viewers after they vote; replies to a question come only to you.</p>
    <form method="post" action="<?= url('/admin/prompts') ?>" class="promptform">
      <?= csrf_field() ?>
      <div class="promptform__kind">
        <label><input type="radio" name="kind" value="poll" checked> Poll</label>
        <label><input type="radio" name="kind" value="question"> Open question</label>
      </div>
      <label style="display:block">
        <span class="mono" style="<?= $label ?>">Question</span>
        <input type="text" name="question" maxlength="255" required placeholder="Which session are you most looking forward to?" style="<?= $field ?>">
      </label>
      <label style="display:block" data-prompt-options>
        <span class="mono" style="<?= $label ?>">Options, one per line <span style="text-transform:none;letter-spacing:0;color:#756f60">(2 to <?= \App\Models\Prompt::MAX_OPTIONS ?>)</span></span>
        <textarea name="options" rows="3" placeholder="Masterclass&#10;Business clinic&#10;Networking" style="<?= $field ?>"></textarea>
      </label>
      <div><button type="submit" class="adm-btn adm-btn--dark">Post to viewers</button></div>
    </form>

    <div class="promptlist" data-prompt-list>
      <?php if (!$prompts): ?><p class="adm-muted mono" style="font-size:.78rem">Nothing posted yet.</p><?php endif; ?>
      <?php foreach ($prompts as $pr): ?>
        <article class="promptcard <?= $pr['status'] === 'open' ? 'is-open' : '' ?>" data-prompt-id="<?= (int) $pr['id'] ?>">
          <header class="promptcard__head">
            <span class="adm-pill adm-pill--<?= $pr['status'] === 'open' ? 'paid' : 'none' ?> mono"><?= $pr['status'] === 'open' ? 'Open' : 'Closed' ?></span>
            <span class="mono promptcard__kind"><?= e($pr['kind']) ?> · <span data-prompt-answers><?= (int) $pr['answers'] ?></span> answers</span>
            <div class="promptcard__actions">
              <?php if ($pr['status'] === 'open'): ?>
                <form method="post" action="<?= url('/admin/prompts/close') ?>"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $pr['id'] ?>"><button type="submit" class="adm-btn" style="padding:.3rem .7rem;font-size:.7rem">Close</button></form>
              <?php endif; ?>
              <form method="post" action="<?= url('/admin/prompts/close') ?>" onsubmit="return confirm('Remove this and every answer to it?')"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $pr['id'] ?>"><input type="hidden" name="delete" value="1"><button type="submit" class="adm-btn" style="padding:.3rem .7rem;font-size:.7rem;color:#b4232b">Remove</button></form>
            </div>
          </header>
          <p class="promptcard__q"><?= e((string) $pr['question']) ?></p>
          <div class="promptcard__results" data-prompt-results></div>
        </article>
      <?php endforeach; ?>
    </div>
  </section>

</section>

<section id="comments" style="margin-top:1.4rem">
  <div class="adm-panel">
    <h2 class="adm-panel__title" style="margin-bottom:1rem">Comments</h2>

    <form method="post" action="<?= url('/admin/comments') ?>" style="display:grid;gap:1rem">
      <?= csrf_field() ?>
      <label style="display:flex;gap:.6rem;align-items:flex-start">
        <input type="checkbox" name="comments_enabled" value="1" <?= $commentsOn ? 'checked' : '' ?>>
        <span><strong>Open the comment board</strong> — only people signed in to the watch page can post.
          Closed by default, and closing it hides the board and refuses new comments.</span>
      </label>
      <div><button type="submit" class="adm-btn adm-btn--dark">Save</button></div>
    </form>

    <p class="adm-muted" style="margin:1.2rem 0 .6rem">
      <?= (int) $commentCount ?> comment(s) on the board.
      <?= $commentsOn ? 'The board is open.' : 'The board is closed.' ?>
    </p>

    <?php if ($commentCount > 0): ?>
      <form method="post" action="<?= url('/admin/comments/delete') ?>" style="margin-bottom:1rem">
        <?= csrf_field() ?>
        <input type="hidden" name="all" value="1">
        <button type="submit" class="adm-btn">Clear every comment</button>
      </form>

      <div class="adm-table-wrap">
        <table class="adm-table adm-table--comments">
          <thead><tr><th>Time</th><th>Name</th><th>Reference</th><th>Comment</th><th></th></tr></thead>
          <tbody>
            <?php foreach ($comments as $c): ?>
              <tr>
                <td class="mono adm-muted"><?= e(date('d M H:i', strtotime((string) $c['created_at']))) ?></td>
                <td><?= e((string) $c['author_name']) ?></td>
                <td class="mono"><?= e((string) $c['reference']) ?></td>
                <td><?= e((string) $c['body']) ?></td>
                <td>
                  <form method="post" action="<?= url('/admin/comments/delete') ?>">
                    <?= csrf_field() ?>
                    <input type="hidden" name="id" value="<?= (int) $c['id'] ?>">
                    <button type="submit" class="adm-btn">Remove</button>
                  </form>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
  </div>
</section>

<section id="live-log" style="margin-top:1.4rem" data-livelog data-log-url="<?= url('/admin/stream/log') ?>">
  <div class="adm-panel">
    <div style="display:flex;flex-wrap:wrap;justify-content:space-between;align-items:center;gap:.6rem 1rem;margin-bottom:.8rem">
      <h2 class="adm-panel__title" style="margin:0">Live log <span class="livelog__dot" data-log-dot aria-hidden="true"></span></h2>
      <div style="display:flex;gap:.6rem;align-items:center">
        <label class="mono" style="display:flex;gap:.4rem;align-items:center;font-size:.72rem;letter-spacing:.08em;text-transform:uppercase;color:#5C5648"><input type="checkbox" data-log-follow checked> Follow</label>
        <form method="post" action="<?= url('/admin/stream/log/clear') ?>" onsubmit="return confirm('Clear the live log?')">
          <?= csrf_field() ?>
          <button type="submit" class="adm-btn" style="padding:.3rem .7rem;font-size:.7rem">Clear</button>
        </form>
      </div>
    </div>
    <p class="adm-muted" style="margin:0 0 .8rem;max-width:70ch">Sign-ins, sign-outs, takeovers, refused attempts, comments, stream switches and load-test progress, as they happen. Refreshes every two seconds.</p>
    <ol class="livelog" data-log-list aria-live="polite"><li class="livelog__empty mono" data-log-empty>Nothing yet.</li></ol>
  </div>
</section>

<section id="watching" style="margin-top:1.4rem">
  <div class="adm-panel" style="margin-top:1.4rem">
    <h2 class="adm-panel__title">Watching now</h2>
    <div class="adm-table-wrap" style="max-height:22rem;overflow-y:auto">
      <table class="adm-table">
        <thead><tr><th>Name</th><th>Reference</th><th>Email</th><th>Device</th><th>Since</th></tr></thead>
        <tbody>
          <?php if (!$watchers): ?>
            <tr><td colspan="5" class="adm-muted">Nobody is watching at the moment.</td></tr>
          <?php else: ?>
            <?php foreach ($watchers as $w): ?>
              <tr>
                <td><?= e(trim(($w['first_name'] ?? '') . ' ' . ($w['last_name'] ?? '')) ?: 'Guest') ?></td>
                <td class="mono"><?= e($w['reference'] ?? '—') ?></td>
                <td><?= e($w['email'] ?? '—') ?></td>
                <td class="mono"><?= e($w['device']) ?></td>
                <td class="mono adm-muted"><?= e(date('H:i', strtotime($w['last_seen_at']))) ?></td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</section>

<section id="load-test" style="margin-top:1.4rem" data-loadtest data-status-url="<?= url('/admin/stream/load-test') ?>">
  <div class="adm-panel">
    <h2 class="adm-panel__title" style="margin-bottom:.4rem">Load test</h2>
    <p class="adm-muted" style="margin:0 0 1rem;max-width:70ch">
      Simulates people on the watch page, each with their own sign-in, doing what the real player does: playlist and video
      segment every six seconds when the stream passes through this server, comment poll every seven, heartbeat every
      twenty-five. Test viewers are created and removed automatically. Run it with the stream switched on and set up as
      it will be on the day, otherwise it measures only the light traffic.
    </p>

    <form method="post" action="<?= url('/admin/stream/load-test') ?>" style="display:flex;flex-wrap:wrap;gap:.8rem 1.2rem;align-items:flex-end">
      <?= csrf_field() ?>
      <label style="display:block">
        <span class="mono" style="display:block;margin-bottom:.35rem;font-size:.72rem;letter-spacing:1.5px;text-transform:uppercase;color:#5C5648">Viewers</span>
        <input type="number" name="viewers" value="50" min="1" max="<?= \App\Services\LoadTester::MAX_VIEWERS ?>" required style="width:8rem;padding:.6rem .7rem;border:1px solid rgba(0,0,0,.25);background:#fff">
      </label>
      <label style="display:block">
        <span class="mono" style="display:block;margin-bottom:.35rem;font-size:.72rem;letter-spacing:1.5px;text-transform:uppercase;color:#5C5648">Seconds</span>
        <input type="number" name="seconds" value="60" min="10" max="<?= \App\Services\LoadTester::MAX_SECONDS ?>" required style="width:8rem;padding:.6rem .7rem;border:1px solid rgba(0,0,0,.25);background:#fff">
      </label>
      <button type="submit" class="adm-btn adm-btn--dark" <?= $loadTestRunning ? 'disabled' : '' ?>>Start test</button>
    </form>
    <?php if ($loadTestRunning): ?>
      <form method="post" action="<?= url('/admin/stream/load-test/stop') ?>" style="margin-top:.8rem">
        <?= csrf_field() ?>
        <button type="submit" class="adm-btn">Stop and clean up</button>
      </form>
    <?php endif; ?>

    <div class="loadtest" data-loadtest-out>
      <p class="mono loadtest__phase" data-lt-phase></p>
      <div class="loadtest__grid">
        <div class="loadtest__stat"><span class="mono">Signed in</span><strong data-lt="signed_in">—</strong></div>
        <div class="loadtest__stat"><span class="mono">Requests / s</span><strong data-lt="rps">—</strong></div>
        <div class="loadtest__stat"><span class="mono">Errors</span><strong data-lt="errors">—</strong></div>
        <div class="loadtest__stat"><span class="mono">Served</span><strong data-lt="mbps">—</strong></div>
        <div class="loadtest__stat"><span class="mono">Server load</span><strong data-lt="load">—</strong></div>
        <div class="loadtest__stat"><span class="mono">Memory used</span><strong data-lt="mem">—</strong></div>
      </div>
      <div class="adm-table-wrap">
        <table class="adm-table adm-table--loadtest">
          <thead><tr><th>Request</th><th>Count</th><th>Errors</th><th>Typical ms</th><th>Slow ms (p95)</th><th>Worst ms</th><th>MB</th></tr></thead>
          <tbody data-lt-rows></tbody>
        </table>
      </div>
      <p class="adm-muted loadtest__verdict" data-lt-verdict></p>
    </div>
  </div>
</section>

<script>
// Picking a ready-made message fills the fields, which stay editable.
(function () {
  var picker = document.querySelector('[data-announce-template]');
  if (!picker) return;
  var subject = document.querySelector('[data-announce-subject]');
  var body = document.querySelector('[data-announce-body]');
  picker.addEventListener('change', function () {
    var option = picker.options[picker.selectedIndex];
    if (!option.value) { subject.value = ''; body.value = ''; return; }
    subject.value = option.getAttribute('data-subject') || '';
    body.value = option.getAttribute('data-body') || '';
  });
})();

// Polls & questions: toggle the options box, and keep results fresh while the page is open.
(function () {
  var root = document.querySelector('[data-prompts]');
  if (!root) return;
  var opts = root.querySelector('[data-prompt-options]');
  root.querySelectorAll('input[name="kind"]').forEach(function (r) {
    r.addEventListener('change', function () { opts.hidden = r.value !== 'poll' || !r.checked; });
  });
  var url = root.getAttribute('data-results-url');
  function paint(list) {
    list.forEach(function (p) {
      var card = root.querySelector('[data-prompt-id="' + p.id + '"]'); if (!card) return;
      card.querySelector('[data-prompt-answers]').textContent = p.answers;
      var out = card.querySelector('[data-prompt-results]'); out.innerHTML = '';
      if (p.kind === 'poll') {
        var total = p.tally.total || 0;
        p.options.forEach(function (label, i) {
          var n = p.tally.counts[i] || 0, pct = total ? Math.round(100 * n / total) : 0;
          out.insertAdjacentHTML('beforeend', '<div class="promptcard__bar"><div class="promptcard__track"><span class="promptcard__fill" style="width:' + pct + '%"></span><span class="promptcard__lbl">' + label + '</span></div><span class="promptcard__pct">' + pct + '% · ' + n + '</span></div>');
        });
      } else {
        if (!p.replies.length) out.innerHTML = '<p class="adm-muted mono" style="font-size:.74rem">No replies yet.</p>';
        p.replies.forEach(function (r) { out.insertAdjacentHTML('beforeend', '<p class="promptcard__reply"><span class="mono">' + r.at + ' · ' + r.author + '</span>' + r.text + '</p>'); });
      }
    });
  }
  function tick() {
    fetch(url, { headers: { Accept: 'application/json' } }).then(function (r) { return r.json(); }).then(function (d) { if (d && d.ok) paint(d.prompts || []); }).catch(function () {}).then(function () { setTimeout(tick, 4000); });
  }
  tick();
})();

// Live log: append new events as they arrive; stay pinned to the bottom unless the reader scrolls up.
(function () {
  var root = document.querySelector('[data-livelog]');
  if (!root) return;
  var list = root.querySelector('[data-log-list]'), empty = root.querySelector('[data-log-empty]');
  var follow = root.querySelector('[data-log-follow]'), dot = root.querySelector('[data-log-dot]');
  var url = root.getAttribute('data-log-url'), last = 0, MAX = 400;
  function add(ev) {
    if (empty && empty.parentNode) empty.remove();
    var li = document.createElement('li');
    li.className = 'livelog__item livelog__item--' + ev.kind;
    li.innerHTML = '<span class="livelog__at mono">' + ev.at + '</span><span class="livelog__kind mono">' + ev.kind + '</span><span class="livelog__detail">' + ev.detail + '</span>';
    list.appendChild(li);
    while (list.children.length > MAX) list.removeChild(list.firstChild);
    last = Math.max(last, ev.id);
  }
  function tick() {
    fetch(url + '?after=' + last, { headers: { Accept: 'application/json' } })
      .then(function (r) { return r.json(); })
      .then(function (d) {
        var evs = (d && d.events) || [];
        if (evs.length) { evs.forEach(add); if (follow.checked) list.scrollTop = list.scrollHeight; dot.classList.add('is-on'); setTimeout(function () { dot.classList.remove('is-on'); }, 600); }
      })
      .catch(function () {})
      .then(function () { setTimeout(tick, 2000); });
  }
  tick();
})();

// Load test: poll the runner's progress while it is going, and show the result when it is done.
(function () {
  var root = document.querySelector('[data-loadtest]');
  if (!root) return;
  var out = root.querySelector('[data-loadtest-out]');
  var url = root.getAttribute('data-status-url');
  var wasRunning = <?= $loadTestRunning ? 'true' : 'false' ?>;
  function fmt(v) { return v === null || v === undefined ? '—' : v; }
  function paint(d) {
    out.hidden = false;
    if (!d.state) {
      root.querySelector('[data-lt-phase]').textContent = 'No test has run yet on this server.';
      return false;
    }
    var s = d.state; var r = s.results || {}; var srv = s.server || d.server || {};
    var phase = { starting: 'Starting…', registering: 'Creating test viewers…', signing_in: 'Signing viewers in…', running: 'Running', done: s.stopped ? 'Stopped' : 'Finished' }[s.phase] || s.phase || '';
    root.querySelector('[data-lt-phase]').textContent = phase + (s.elapsed ? ' · ' + s.elapsed + 's of ' + s.seconds + 's' : '') + (s.note ? ' · ' + s.note : '') + (s.error ? ' · ' + s.error : '');
    root.querySelector('[data-lt="signed_in"]').textContent = fmt(s.signed_in) + ' / ' + fmt(s.viewers);
    root.querySelector('[data-lt="rps"]').textContent = fmt(r.rps);
    root.querySelector('[data-lt="errors"]').textContent = r.total ? r.errors + ' (' + r.error_pct + '%)' : '—';
    root.querySelector('[data-lt="mbps"]').textContent = r.mbps !== undefined ? r.mbps + ' MB/s' : '—';
    root.querySelector('[data-lt="load"]').textContent = srv.load1 !== undefined ? srv.load1 + ' on ' + srv.cores + ' core' + (srv.cores === 1 ? '' : 's') : '—';
    root.querySelector('[data-lt="mem"]').textContent = srv.mem_used_pct !== null && srv.mem_used_pct !== undefined ? srv.mem_used_pct + '%' : 'n/a';
    var rows = root.querySelector('[data-lt-rows]'); rows.innerHTML = '';
    Object.keys(r.by || {}).forEach(function (k) {
      var b = r.by[k]; var tr = document.createElement('tr');
      tr.innerHTML = '<td class="mono">' + k + '</td><td>' + b.count + '</td><td' + (b.errors ? ' style="color:#b4232b;font-weight:600"' : '') + '>' + b.errors + '</td><td>' + b.p50 + '</td><td>' + b.p95 + '</td><td>' + b.max + '</td><td>' + b.mb + '</td>';
      rows.appendChild(tr);
    });
    var v = root.querySelector('[data-lt-verdict]');
    if (s.phase === 'done' && r.total) {
      var seg = (r.by || {}).segment;
      var verdict = r.error_pct < 1 && (!seg || seg.p95 < 4000)
        ? 'Held up: under 1% errors' + (seg ? ' and video segments arrived in good time' : '') + '. This many viewers is fine.'
        : r.error_pct < 5 ? 'Strained: some requests failed or video arrived late. Expect buffering at this number.'
        : 'Overloaded: ' + r.error_pct + '% of requests failed. Fewer viewers, or move the video off this server (YouTube, Vimeo, or an HLS host with the proxy switched off).';
      v.textContent = verdict + (srv.load1 > srv.cores ? ' The server itself was saturated (load ' + srv.load1 + ' on ' + srv.cores + ' cores).' : '');
    } else { v.textContent = ''; }
    return d.running;
  }
  function poll() {
    fetch(url, { headers: { Accept: 'application/json' } }).then(function (r) { return r.json(); }).then(function (d) {
      var running = paint(d);
      if (running) { setTimeout(poll, 2000); } else if (wasRunning) { setTimeout(function () { location.reload(); }, 1500); }
    }).catch(function () { setTimeout(poll, 4000); });
  }
  poll();
})();
</script>
<script>
(function () {
  var root = document.querySelector('[data-server-load]'); if (!root) return;
  var url = root.getAttribute('data-status-url');
  var COLOR = { good: '#2E7D32', warn: '#C77700', bad: '#B3261E', off: '#9E9E9E' };
  // pct: 0-100 fill. level: good / warn / bad.
  function set(key, pct, level, value, note) {
    var g = root.querySelector('[data-g="' + key + '"]'); if (!g) return;
    g.querySelector('[data-g-bar]').style.width = Math.max(0, Math.min(100, pct)) + '%';
    g.querySelector('[data-g-bar]').style.backgroundColor = COLOR[level];
    g.querySelector('[data-g-value]').textContent = value;
    g.querySelector('[data-g-value]').style.color = level === 'good' ? '' : COLOR[level];
    if (note) g.querySelector('[data-g-note]').textContent = note;
  }
  function level(pct, warnAt, badAt) { return pct >= badAt ? 'bad' : pct >= warnAt ? 'warn' : 'good'; }
  var WORD = { good: 'fine', warn: 'getting busy', bad: 'overloaded' };
  function tick() {
    fetch(url, { headers: { Accept: 'application/json' } }).then(function (r) { return r.json(); }).then(function (d) {
      var s = d.server || {};
      var cpu = Math.round(100 * s.load1 / (s.cores || 1));
      set('cpu', cpu, level(cpu, 70, 100), cpu + '%', 'load ' + s.load1 + ' on ' + s.cores + ' cores · ' + WORD[level(cpu, 70, 100)]);
      if (s.mem_used_pct != null) set('mem', s.mem_used_pct, level(s.mem_used_pct, 75, 90), s.mem_used_pct + '%', 'in use · ' + WORD[level(s.mem_used_pct, 75, 90)]);
      // ponytail: 500 is the relay's rough ceiling at 720p on its 1 Gbps port; lower the bitrate to raise it.
      var vp = Math.round(100 * d.watching / 500);
      set('viewers', vp, level(vp, 80, 100), d.watching, 'of ~500 relay capacity · ' + WORD[level(vp, 80, 100)]);
      if (d.relay) {
        if (!d.relay.ok) set('relay', 100, 'bad', 'DOWN', 'not answering · video will stop');
        else { var rp = Math.min(100, Math.round(d.relay.ms / 15)); set('relay', rp, level(d.relay.ms, 800, 2000), d.relay.ms + ' ms', 'response time · ' + WORD[level(d.relay.ms, 800, 2000)]); }
      }
    }).catch(function () {}).then(function () { setTimeout(tick, 5000); });
  }
  tick();
})();
</script>
