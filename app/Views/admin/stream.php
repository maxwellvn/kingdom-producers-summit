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

        <label style="display:block">
          <span class="mono" style="display:block;margin-bottom:.35rem;font-size:.72rem;letter-spacing:1.5px;text-transform:uppercase;color:#5C5648">Line shown while it is off</span>
          <input type="text" name="stream_note" value="<?= e($note) ?>" style="width:100%;padding:.6rem .7rem;border:1px solid rgba(0,0,0,.25);background:#fff">
        </label>

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

  <div class="adm-panel" style="margin-top:1.4rem">
    <h2 class="adm-panel__title">Watching now</h2>
    <div class="adm-table-wrap">
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

    <div class="loadtest" data-loadtest-out <?= $loadTest ? '' : 'hidden' ?>>
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

