<?php
/** @var array $viewer @var bool $live @var string $kind @var string $note @var array $summit @var bool $commentsOn @var array $holding */
$notice = (string) \App\Core\Session::get('watch_notice', '');
$isOrganiser = !empty($viewer['is_organiser']);
$name = trim($viewer['first_name'] . ' ' . $viewer['last_name']);
$initials = strtoupper(mb_substr((string) $viewer['first_name'], 0, 1) . mb_substr((string) $viewer['last_name'], 0, 1));
?>
<section class="watch <?= $commentsOn ? 'watch--chat' : '' ?>">
  <div class="container watch__inner">

    <header class="watch__head">
      <div class="watch__heading">
        <h1 class="watch__title"><?= e(\App\Services\StreamService::title()) ?></h1>
        <p class="mono watch__sub">
          <span class="watch__pill <?= $live ? 'is-live' : '' ?>" data-watch-pill><span class="watch__pill-dot" aria-hidden="true"></span><span data-watch-status><?= $live ? 'Live' : e($holding['label']) ?></span></span>
          <span class="watch__count" data-watch-count hidden></span>
          <span class="watch__now" data-watch-now <?= $live && $holding['now'] ? '' : 'hidden' ?>><?= e($holding['now']) ?></span>
        </p>
      </div>
      <div class="watch__viewer">
        <span class="watch__avatar mono" aria-hidden="true"><?= e($isOrganiser ? 'ORG' : ($initials ?: '•')) ?></span>
        <span class="watch__who">
          <span class="watch__name"><?= e($isOrganiser ? 'Organiser view' : $name) ?></span>
          <span class="mono watch__ref"><?= e($isOrganiser ? $viewer['email'] : $viewer['reference']) ?></span>
        </span>
        <?php if ($isOrganiser): ?>
          <a class="watch__leave mono" href="<?= url('/admin/stream') ?>">Admin</a>
        <?php else: ?>
          <form method="post" action="<?= url('/watch/leave') ?>">
            <?= csrf_field() ?>
            <button type="submit" class="watch__leave mono">Sign out</button>
          </form>
        <?php endif; ?>
      </div>
    </header>

    <?php if ($notice !== ''): ?>
      <div class="pay__alert watch__notice" role="status"><span><?= e($notice) ?></span></div>
    <?php endif; ?>

    <div class="watch__layout">
      <div class="watch__stage" data-watch
           data-source-url="<?= e(url('/watch/source')) ?>"
           data-beat-url="<?= e(url('/watch/beat')) ?>">
        <div class="watch__placeholder holding holding--<?= e($holding['state']) ?>" data-watch-placeholder
             data-holding-state="<?= e($holding['state']) ?>" data-holding-starts="<?= $holding['starts_at'] ? (int) $holding['starts_at'] : '' ?>">
          <p class="mono holding__kicker"><span class="holding__dot" aria-hidden="true"></span><span data-holding-label><?= $live ? 'Connecting…' : e($holding['label']) ?></span></p>
          <h2 class="holding__headline" data-holding-headline><?= $live ? 'One moment.' : e($holding['headline']) ?></h2>
          <p class="holding__message" data-holding-message><?= $live ? 'Loading the stream.' : e($holding['message']) ?></p>
          <p class="mono holding__countdown" data-holding-countdown <?= $holding['starts_at'] ? '' : 'hidden' ?>></p>
          <p class="mono holding__starts" data-holding-starts-text <?= $holding['starts_text'] ? '' : 'hidden' ?>><?= e($holding['starts_text']) ?></p>
          <p class="mono holding__now" data-holding-now <?= $holding['now'] ? '' : 'hidden' ?>><?= e($holding['now']) ?></p>
        </div>
        <video class="watch__video" data-watch-video playsinline controls hidden></video>
        <div class="watch__frame" data-watch-frame hidden></div>
      </div>

      <aside class="chat" data-comments
             data-comments-url="<?= e(url('/watch/comments')) ?>"
             data-comments-open="<?= $commentsOn ? '1' : '0' ?>"
             aria-labelledby="chatTitle" <?= $commentsOn ? '' : 'hidden' ?>>
        <header class="chat__head">
          <h2 class="chat__title" id="chatTitle">Live chat</h2>
          <span class="mono chat__count" data-comments-count></span>
        </header>

        <ol class="chat__list" data-comments-list>
          <li class="chat__empty" data-comments-empty>
            <span class="chat__empty-title">Share your comments</span>
            <span class="mono">Be the first to comment on this stream.</span>
          </li>
        </ol>

        <form class="chat__form" data-comments-form>
          <?= csrf_field() ?>
          <label class="sr-only" for="commentBody">Your message</label>
          <div class="chat__box">
            <textarea id="commentBody" name="body" rows="1" maxlength="<?= (int) \App\Models\Comment::MAX_LENGTH ?>"
                      placeholder="Write a message…" data-comments-input required></textarea>
            <button type="submit" class="chat__send" aria-label="Send">
              <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M22 2 11 13"/><path d="m22 2-7 20-4-9-9-4Z"/></svg>
            </button>
          </div>
          <span class="mono chat__hint" data-comments-hint>Posting as <?= e($isOrganiser ? 'Organiser' : $name) ?></span>
        </form>
      </aside>
    </div>

    <div class="prompt" data-prompt data-prompt-url="<?= e(url('/watch/prompt')) ?>" hidden role="dialog" aria-live="polite" aria-labelledby="promptQuestion">
      <?= csrf_field() ?>
      <div class="prompt__card">
        <p class="mono prompt__kicker"><span class="prompt__dot" aria-hidden="true"></span><span data-prompt-kicker>From the organisers</span></p>
        <h3 class="prompt__question" id="promptQuestion" data-prompt-question></h3>
        <div class="prompt__body" data-prompt-body></div>
        <p class="mono prompt__hint" data-prompt-hint></p>
        <button type="button" class="prompt__dismiss mono" data-prompt-dismiss aria-label="Hide">Hide</button>
      </div>
    </div>

    <p class="watch__fine mono">
      <?php if ($isOrganiser): ?>You are watching as an organiser, without a pass, so this does not take a place from anyone.<?php else: ?>This pass is yours alone. Opening it elsewhere signs this screen out.<?php endif; ?>
      Trouble? Message <a href="https://kingschat.online/user/<?= e(contact_kingschat()) ?>" target="_blank" rel="noopener">@<?= e(contact_kingschat()) ?></a>
      or email <a href="mailto:<?= e(contact_email()) ?>"><?= e(contact_email()) ?></a>.
    </p>
  </div>
</section>
