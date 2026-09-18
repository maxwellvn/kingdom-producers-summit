<?php
/** @var bool $live @var string $url @var string $kind @var string $streamTitle @var string $note
 *  @var bool $proxy @var array $watchers @var array $audiences @var array $templates
 *  @var array $counts @var string $flash */
$kindLabel = ['hls' => 'HLS stream (.m3u8)', 'iframe' => 'Embedded player', 'file' => 'Video file'][$kind] ?? '—';
?>
<section class="adm-page">
  <header class="adm-page__head">
    <div>
      <p class="eyebrow"><span class="eyebrow__dot"></span>Live</p>
      <h1 class="adm-page__title">Stream</h1>
    </div>
    <p class="adm-page__meta mono"><?= $live ? 'Live · ' . count($watchers) . ' watching' : 'Off' ?></p>
  </header>

  <?php if ($flash !== ''): ?>
    <div class="form__alert" role="status" style="border-color: rgba(46,160,67,.5)"><span><?= e($flash) ?></span></div>
  <?php endif; ?>

  <div class="adm-columns" style="grid-template-columns: minmax(0, 1.1fr) minmax(0, .9fr)">
    <div class="adm-panel">
      <h2 class="adm-panel__title">The stream</h2>
      <form method="post" action="<?= url('/admin/stream') ?>" style="display:grid;gap:1rem">
        <?= csrf_field() ?>

        <label style="display:flex;gap:.6rem;align-items:center">
          <input type="checkbox" name="stream_enabled" value="1" <?= $live ? 'checked' : '' ?>>
          <span><strong>Open the watch page</strong> — registrants can sign in and watch</span>
        </label>

        <label style="display:block">
          <span class="mono" style="display:block;margin-bottom:.35rem;font-size:.72rem;letter-spacing:1.5px;text-transform:uppercase;color:#5C5648">Stream link</span>
          <input type="url" name="stream_url" value="<?= e($url) ?>" placeholder="https://…/index.m3u8 or a YouTube link"
                 style="width:100%;padding:.6rem .7rem;border:1px solid rgba(0,0,0,.25);background:#fff">
          <span style="display:block;margin-top:.35rem;color:#5C5648;font-size:.85rem">
            Detected: <strong><?= e($kindLabel) ?></strong>. HLS, YouTube, Vimeo, Facebook, Twitch or a video file.
          </span>
        </label>

        <label style="display:flex;gap:.6rem;align-items:flex-start">
          <input type="checkbox" name="stream_proxy" value="1" <?= $proxy ? 'checked' : '' ?>>
          <span>Serve an HLS stream through this site, so the real address never reaches the browser.
            Has no effect on YouTube or Vimeo, which are fetched by their own player.</span>
        </label>

        <label style="display:block">
          <span class="mono" style="display:block;margin-bottom:.35rem;font-size:.72rem;letter-spacing:1.5px;text-transform:uppercase;color:#5C5648">Heading on the watch page</span>
          <input type="text" name="stream_title" value="<?= e($streamTitle) ?>" style="width:100%;padding:.6rem .7rem;border:1px solid rgba(0,0,0,.25);background:#fff">
        </label>

        <fieldset style="border:1px solid rgba(0,0,0,.15);padding:1.1rem 1.2rem 1.2rem;margin:0">
          <legend class="mono" style="padding:0 .6rem;font-size:.75rem;letter-spacing:2px;text-transform:uppercase">Holding screen — before, between and after</legend>
          <p class="adm-muted" style="margin:0 0 .9rem;font-size:.9rem">What people signed in to the watch page see whenever no video is playing. Changes appear on their screens within fifteen seconds, no refresh needed.</p>

          <div style="display:grid;gap:.5rem;margin-bottom:1rem">
            <?php foreach ($holdingStates as $key => $def): ?>
              <label style="display:flex;gap:.6rem;align-items:flex-start">
                <input type="radio" name="stream_state" value="<?= e($key) ?>" <?= $holding['state'] === $key ? 'checked' : '' ?> style="margin-top:.25rem">
                <span><strong><?= e($def['label']) ?></strong> <span class="adm-muted">— "<?= e($def['headline']) ?>"</span></span>
              </label>
            <?php endforeach; ?>
          </div>

          <label style="display:block;margin-bottom:.9rem">
            <span class="mono" style="display:block;margin-bottom:.35rem;font-size:.72rem;letter-spacing:1.5px;text-transform:uppercase;color:#5C5648">Starts at <span style="text-transform:none;letter-spacing:0;color:#756f60">(optional; shows a countdown while "Starting soon")</span></span>
            <input type="datetime-local" name="stream_starts_at" value="<?= e($startsAtValue) ?>" style="padding:.6rem .7rem;border:1px solid rgba(0,0,0,.25);background:#fff">
          </label>

          <label style="display:block;margin-bottom:.9rem">
            <span class="mono" style="display:block;margin-bottom:.35rem;font-size:.72rem;letter-spacing:1.5px;text-transform:uppercase;color:#5C5648">Headline <span style="text-transform:none;letter-spacing:0;color:#756f60">(blank uses the default for the state)</span></span>
            <input type="text" name="stream_headline" value="<?= e($headlineValue) ?>" maxlength="120" placeholder="<?= e($holding['headline']) ?>" style="width:100%;padding:.6rem .7rem;border:1px solid rgba(0,0,0,.25);background:#fff">
          </label>

          <label style="display:block;margin-bottom:.9rem">
            <span class="mono" style="display:block;margin-bottom:.35rem;font-size:.72rem;letter-spacing:1.5px;text-transform:uppercase;color:#5C5648">Message</span>
            <textarea name="stream_message" rows="2" maxlength="300" placeholder="<?= e($holding['message']) ?>" style="width:100%;padding:.6rem .7rem;border:1px solid rgba(0,0,0,.25);background:#fff;font:inherit"><?= e($messageValue) ?></textarea>
          </label>

          <label style="display:block">
            <span class="mono" style="display:block;margin-bottom:.35rem;font-size:.72rem;letter-spacing:1.5px;text-transform:uppercase;color:#5C5648">Now / next <span style="text-transform:none;letter-spacing:0;color:#756f60">(shown while live too, e.g. "Now: Opening session · Next: Masterclass, 13:00")</span></span>
            <input type="text" name="stream_now" value="<?= e($nowValue) ?>" maxlength="160" style="width:100%;padding:.6rem .7rem;border:1px solid rgba(0,0,0,.25);background:#fff">
          </label>
        </fieldset>

        <div>
          <button type="submit" class="adm-btn adm-btn--dark">Save</button>
          <a class="adm-btn" href="<?= url('/watch') ?>" target="_blank" rel="noopener">Open the watch page</a>
        </div>
      </form>
    </div>

    <div class="adm-panel">
      <h2 class="adm-panel__title">Tell people</h2>
      <p class="adm-muted">Live-now and starting-soon messages, and anything scheduled ahead of the day, live under <a href="<?= url('/admin/notifications') ?>">Notifications</a>.</p>
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
        <table class="adm-table" style="min-width:36rem">
          <thead><tr><th>Request</th><th>Count</th><th>Errors</th><th>Typical ms</th><th>Slow ms (p95)</th><th>Worst ms</th><th>MB</th></tr></thead>
          <tbody data-lt-rows></tbody>
        </table>
      </div>
      <p class="adm-muted loadtest__verdict" data-lt-verdict></p>
    </div>
  </div>
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
