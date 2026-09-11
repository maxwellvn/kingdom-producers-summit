<?php
/** @var array $viewer @var bool $live @var string $kind @var string $note @var array $summit @var bool $commentsOn */
$notice = (string) \App\Core\Session::get('watch_notice', '');
?>
<section class="watch">
  <div class="container watch__inner">
    <header class="watch__head">
      <div>
        <p class="mono watch__status">
          <span class="watch__dot <?= $live ? 'is-live' : '' ?>" aria-hidden="true"></span>
          <span data-watch-status><?= $live ? 'Live now' : 'Not started yet' ?></span>
        </p>
        <h1 class="watch__title"><?= e(\App\Services\StreamService::title()) ?></h1>
      </div>
      <div class="watch__viewer">
        <?php if (!empty($viewer['is_organiser'])): ?>
          <span class="mono watch__ref">Organiser view</span>
          <a class="watch__leave mono" href="<?= url('/admin/stream') ?>">Back to admin</a>
        <?php else: ?>
          <span class="mono"><?= e(trim($viewer['first_name'] . ' ' . $viewer['last_name'])) ?></span>
          <span class="mono watch__ref"><?= e($viewer['reference']) ?></span>
          <form method="post" action="<?= url('/watch/leave') ?>">
            <?= csrf_field() ?>
            <button type="submit" class="watch__leave mono">Sign out</button>
          </form>
        <?php endif; ?>
      </div>
    </header>

    <?php if ($notice !== ''): ?>
      <div class="pay__alert" role="status" style="margin-bottom:1rem"><span><?= e($notice) ?></span></div>
    <?php endif; ?>

    <div class="watch__stage" data-watch
         data-source-url="<?= e(url('/watch/source')) ?>"
         data-beat-url="<?= e(url('/watch/beat')) ?>">
      <div class="watch__placeholder" data-watch-placeholder>
        <p class="mono"><?= $live ? 'Connecting…' : 'The stream has not started' ?></p>
        <p class="watch__note"><?= e($note) ?></p>
      </div>
      <video class="watch__video" data-watch-video playsinline controls hidden></video>
      <div class="watch__frame" data-watch-frame hidden></div>
    </div>

    <section class="chat" data-comments
             data-comments-url="<?= e(url('/watch/comments')) ?>"
             data-comments-open="<?= $commentsOn ? '1' : '0' ?>"
             aria-labelledby="chatTitle" <?= $commentsOn ? '' : 'hidden' ?>>
      <header class="chat__head">
        <h2 class="chat__title" id="chatTitle">Comments</h2>
        <span class="mono chat__count" data-comments-count></span>
      </header>

      <ol class="chat__list" data-comments-list>
        <li class="chat__empty mono" data-comments-empty>No comments yet. Say the first thing.</li>
      </ol>

      <form class="chat__form" data-comments-form>
        <?= csrf_field() ?>
        <label class="sr-only" for="commentBody">Your comment</label>
        <textarea id="commentBody" name="body" rows="2" maxlength="<?= (int) \App\Models\Comment::MAX_LENGTH ?>"
                  placeholder="Write a comment…" data-comments-input required></textarea>
        <div class="chat__actions">
          <span class="mono chat__hint" data-comments-hint>Posting as <?= e(trim($viewer['first_name'] . ' ' . $viewer['last_name'])) ?></span>
          <button type="submit" class="btn btn--ink chat__send"><span class="btn__label">Post</span></button>
        </div>
      </form>
    </section>

    <p class="watch__fine mono">
      <?php if (!empty($viewer['is_organiser'])): ?>You are watching as an organiser, without a pass, so this does not take a place from anyone.<?php else: ?>This pass is yours alone. Opening it elsewhere signs this screen out.<?php endif; ?>
      Trouble? Message <a href="https://kingschat.online/user/<?= e(contact_kingschat()) ?>" target="_blank" rel="noopener">@<?= e(contact_kingschat()) ?></a>
      or email <a href="mailto:<?= e(contact_email()) ?>"><?= e(contact_email()) ?></a>.
    </p>
  </div>
</section>
