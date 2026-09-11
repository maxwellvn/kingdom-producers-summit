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
      <form method="post" action="<?= url('/admin/stream/announce') ?>" style="display:grid;gap:1rem">
        <?= csrf_field() ?>

        <label style="display:block">
          <span class="mono" style="display:block;margin-bottom:.35rem;font-size:.72rem;letter-spacing:1.5px;text-transform:uppercase;color:#5C5648">Who</span>
          <select name="audience" style="width:100%;padding:.6rem .7rem;border:1px solid rgba(0,0,0,.25);background:#fff">
            <?php foreach ($audiences as $value => $label): ?>
              <option value="<?= e($value) ?>"><?= e($label) ?> (<?= number_format($counts[$value] ?? 0) ?>)</option>
            <?php endforeach; ?>
          </select>
        </label>

        <label style="display:block">
          <span class="mono" style="display:block;margin-bottom:.35rem;font-size:.72rem;letter-spacing:1.5px;text-transform:uppercase;color:#5C5648">Ready-made message</span>
          <select name="template" data-announce-template style="width:100%;padding:.6rem .7rem;border:1px solid rgba(0,0,0,.25);background:#fff">
            <option value="">Write my own</option>
            <?php foreach ($templates as $key => $template): ?>
              <option value="<?= e($key) ?>"
                      data-subject="<?= e($template['subject']) ?>"
                      data-body="<?= e($template['body']) ?>"><?= e($template['label']) ?></option>
            <?php endforeach; ?>
          </select>
        </label>

        <label style="display:block">
          <span class="mono" style="display:block;margin-bottom:.35rem;font-size:.72rem;letter-spacing:1.5px;text-transform:uppercase;color:#5C5648">Subject</span>
          <input type="text" name="subject" data-announce-subject style="width:100%;padding:.6rem .7rem;border:1px solid rgba(0,0,0,.25);background:#fff">
        </label>

        <label style="display:block">
          <span class="mono" style="display:block;margin-bottom:.35rem;font-size:.72rem;letter-spacing:1.5px;text-transform:uppercase;color:#5C5648">Message</span>
          <textarea name="body" rows="7" data-announce-body style="width:100%;padding:.6rem .7rem;border:1px solid rgba(0,0,0,.25);background:#fff;font:inherit"></textarea>
          <span style="display:block;margin-top:.35rem;color:#5C5648;font-size:.85rem">
            You can use {first_name}, {reference}, {watch_url} and {summit_date}.
          </span>
        </label>

        <div style="display:flex;gap:1.2rem;flex-wrap:wrap">
          <label style="display:flex;gap:.5rem;align-items:center"><input type="checkbox" name="by_email" value="1" checked> <span>Email</span></label>
          <label style="display:flex;gap:.5rem;align-items:center"><input type="checkbox" name="by_kingschat" value="1" checked> <span>KingsChat</span></label>
        </div>

        <button type="submit" class="adm-btn adm-btn--dark"
                onsubmit="return true"
                onclick="return confirm('Send this to everyone in the chosen group?')">Send it</button>
      </form>
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
</script>
