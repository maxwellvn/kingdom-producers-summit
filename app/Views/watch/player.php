<?php
/** @var array $viewer @var bool $live @var string $kind @var string $note @var array $summit */
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
        <span class="mono"><?= e(trim($viewer['first_name'] . ' ' . $viewer['last_name'])) ?></span>
        <span class="mono watch__ref"><?= e($viewer['reference']) ?></span>
        <form method="post" action="<?= url('/watch/leave') ?>">
          <?= csrf_field() ?>
          <button type="submit" class="watch__leave mono">Sign out</button>
        </form>
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

    <p class="watch__fine mono">
      This pass is yours alone. Opening it elsewhere signs this screen out.
      Trouble? Message <a href="https://kingschat.online/user/<?= e(contact_kingschat()) ?>" target="_blank" rel="noopener">@<?= e(contact_kingschat()) ?></a>
      or email <a href="mailto:<?= e(contact_email()) ?>"><?= e(contact_email()) ?></a>.
    </p>
  </div>
</section>
