<?php
/** @var bool $configured @var bool $connected @var array $status @var string $sender @var string $clientId
 *  @var string $redirect @var string $authorize @var string $flash */
?>
<section class="adm-page">
  <header class="adm-page__head">
    <div>
      <p class="eyebrow"><span class="eyebrow__dot"></span>Notifications</p>
      <h1 class="adm-page__title">KingsChat</h1>
    </div>
  </header>

  <?php if ($flash !== ''): ?>
    <div class="form__alert" role="status" style="border-color: rgba(46,160,67,.5)">
      <span><?= e($flash) ?></span>
    </div>
  <?php endif; ?>

  <p class="adm-muted" style="margin-bottom:1.4rem;max-width:70ch">
    Registrants who give a KingsChat username are messaged alongside their email.
    An organiser signs in once as the sending account to authorise it; no password is stored here.
  </p>

  <div style="display:flex;flex-direction:column;gap:1.6rem;max-width:720px">

    <fieldset style="border:1px solid rgba(0,0,0,.15);padding:1.4rem">
      <legend class="mono" style="padding:0 .6rem;font-size:.8rem;letter-spacing:2px;text-transform:uppercase">Connection</legend>

      <?php if ($connected): ?>
        <dl class="mono" style="display:grid;grid-template-columns:auto 1fr;gap:.35rem 1rem;margin:0 0 1.2rem;font-size:.8rem">
          <dt style="color:#756f60">Token</dt>
          <dd style="margin:0"><?= $status['expired'] ? 'Lapsed' : 'Valid until ' . e(date('D d M, H:i', (int) $status['expires_at'])) ?> — renewed automatically 45 minutes before it lapses, and on demand when a message is sent.</dd>
          <dt style="color:#756f60">Last renewed</dt>
          <dd style="margin:0"><?= $status['last_refresh'] !== '' ? e($status['last_refresh']) : 'Not yet since connecting' ?></dd>
          <dt style="color:#756f60">Last message</dt>
          <dd style="margin:0"><?= $status['last_sent'] !== '' ? e($status['last_sent']) : 'None sent yet' ?></dd>
          <?php if ($status['last_error'] !== ''): ?>
            <dt style="color:#b4232b">Last problem</dt>
            <dd style="margin:0;color:#b4232b"><?= e($status['last_error']) ?></dd>
          <?php endif; ?>
        </dl>
      <?php elseif (!empty($status['last_error'])): ?>
        <p class="mono" style="margin:0 0 1rem;font-size:.8rem;color:#b4232b">Last problem: <?= e($status['last_error']) ?></p>
      <?php endif; ?>
      <p style="margin:0 0 1rem">
        <strong style="font-size:1.05rem"><?= $connected ? 'Connected' : 'Not connected' ?></strong>
        <?php if ($connected): ?>
          <span class="adm-muted"> — sending as <?= e($sender) ?></span>
        <?php endif; ?>
      </p>

      <div style="display:flex;flex-wrap:wrap;gap:.6rem;align-items:center">
        <?php if ($configured): ?>
          <a class="adm-btn adm-btn--dark" href="<?= e($authorize) ?>"><?= $connected ? 'Reconnect' : 'Connect KingsChat' ?></a>
        <?php else: ?>
          <span class="adm-muted">Set KINGSCHAT_CLIENT_ID before connecting.</span>
        <?php endif; ?>

        <?php if ($connected): ?>
          <form method="post" action="<?= url('/admin/kingschat/disconnect') ?>" onsubmit="return confirm('Disconnect KingsChat? Registrants will only be emailed.')">
            <?= csrf_field() ?>
            <button type="submit" class="adm-btn">Disconnect</button>
          </form>
        <?php endif; ?>
      </div>

      <dl class="adm-kv" style="margin-top:1.2rem">
        <div><dt class="mono">Application</dt><dd><?= e($clientId) ?></dd></div>
        <div><dt class="mono">Return address</dt><dd><?= e($redirect) ?></dd></div>
      </dl>
    </fieldset>

    <fieldset style="border:1px solid rgba(0,0,0,.15);padding:1.4rem">
      <legend class="mono" style="padding:0 .6rem;font-size:.8rem;letter-spacing:2px;text-transform:uppercase">Send a test</legend>
      <form method="post" action="<?= url('/admin/kingschat/test') ?>" style="display:flex;flex-wrap:wrap;gap:.6rem;align-items:center">
        <?= csrf_field() ?>
        <input type="text" name="recipient" placeholder="username" value="maxwellvn"
               style="flex:1 1 14rem;padding:.6rem .7rem;border:1px solid rgba(0,0,0,.25);background:#fff">
        <button type="submit" class="adm-btn adm-btn--dark" <?= $connected ? '' : 'disabled' ?>>Send test message</button>
      </form>
      <p class="adm-muted" style="margin-top:.7rem;font-size:.9rem">
        Any KingsChat username can be reached. Leave off the @.
      </p>
    </fieldset>
  </div>
</section>
