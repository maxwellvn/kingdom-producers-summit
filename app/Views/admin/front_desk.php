<?php /** @var bool $express @var string $openToken @var string $flash */ ?>
<section class="adm-page">
  <header class="adm-page__head">
    <div>
      <p class="eyebrow"><span class="eyebrow__dot"></span>Event day</p>
      <h1 class="adm-page__title">Front desk</h1>
    </div>
    <a class="adm-btn" href="<?= url('/admin/scanner') ?>">Open the access scanner</a>
  </header>

  <?php if ($flash !== ''): ?>
    <div class="form__alert" role="status"><span><?= e($flash) ?></span></div>
  <?php endif; ?>

  <section id="event-day" class="adm-panel eventday">
        <div class="eventday__grid">
      <div class="eventday__item">
        <div>
          <strong>Express registration</strong>
          <span class="mono eventday__state <?= $express ? 'is-on' : '' ?>"><?= $express ? 'On' : 'Off' ?></span>
          <p>Walk-ins at the desk fill in name, contact and consent only. Everything else is skipped.</p>
        </div>
        <form method="post" action="<?= url('/admin/event-day') ?>">
          <?= csrf_field() ?>
          <input type="hidden" name="action" value="<?= $express ? 'express_off' : 'express_on' ?>">
          <button type="submit" class="adm-btn <?= $express ? '' : 'adm-btn--solid' ?>"><?= $express ? 'Turn off' : 'Turn on' ?></button>
        </form>
      </div>
      <div class="eventday__item">
        <div>
          <strong>Open watch link</strong>
          <span class="mono eventday__state <?= $openToken !== '' ? 'is-on' : '' ?>"><?= $openToken !== '' ? 'Active' : 'Off' ?></span>
          <p>Anyone with the link enters the stream with just a name and email; unregistered people are added as online registrants.</p>
          <?php if ($openToken !== ''): ?>
            <?php $openUrl = site_url() . '/watch?open=' . $openToken; ?>
            <p class="eventday__link"><input type="text" readonly value="<?= e($openUrl) ?>" onclick="this.select()" aria-label="Open watch link"> <button type="button" class="adm-btn" data-copy="<?= e($openUrl) ?>">Copy</button></p>
          <?php endif; ?>
        </div>
        <form method="post" action="<?= url('/admin/event-day') ?>" style="display:flex;gap:.4rem;flex-wrap:wrap">
          <?= csrf_field() ?>
          <?php if ($openToken !== ''): ?>
            <button type="submit" name="action" value="open_link_new" class="adm-btn" onclick="return confirm('Make a new link? The old one stops working.')">New link</button>
            <button type="submit" name="action" value="open_link_off" class="adm-btn">Revoke</button>
          <?php else: ?>
            <button type="submit" name="action" value="open_link_new" class="adm-btn adm-btn--solid">Create link</button>
          <?php endif; ?>
        </form>
      </div>
    </div>
  </section>
</section>
<script>
  document.querySelectorAll('[data-copy]').forEach(function (b) {
    b.addEventListener('click', function () {
      var t = b.getAttribute('data-copy'), done = function () { b.textContent = 'Copied'; setTimeout(function () { b.textContent = 'Copy'; }, 1500); };
      if (navigator.clipboard && navigator.clipboard.writeText) { navigator.clipboard.writeText(t).then(done); }
      else { var i = b.previousElementSibling; i.select(); document.execCommand('copy'); done(); }
    });
  });
</script>
