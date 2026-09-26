<?php /** @var array $archives @var array<string,string> $tables @var string $flash @var string $edition @var string $confirm */ ?>
<section class="adm-page">
  <header class="adm-page__head">
    <div>
      <p class="eyebrow"><span class="eyebrow__dot"></span>Editions</p>
      <h1 class="adm-page__title">Archive <span class="adm-page__title-sub"><?= count($archives) ?> edition<?= count($archives) === 1 ? '' : 's' ?></span></h1>
    </div>
  </header>

  <?php if ($flash !== ''): ?>
    <div class="form__alert" role="status"><span><?= e($flash) ?></span></div>
  <?php endif; ?>

  <?php if ($archives): ?>
  <section class="adm-panel adm-panel--flush" style="margin-bottom:1.25rem">
    <div class="adm-table-wrap">
      <table class="adm-table">
        <thead><tr><th>Edition</th><th>Registrations</th><th>Check-ins</th><th>Watch passes</th><th>Commitments</th><th>Archived</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($archives as $a): $c = $a['counts']; ?>
          <tr>
            <td data-label="Edition"><a class="adm-link" href="<?= url('/admin/archives/view?slug=' . rawurlencode($a['slug'])) ?>"><strong><?= e($a['label']) ?></strong></a><br><span class="adm-muted mono"><?= e($a['slug']) ?><?= $a['cleared'] ? '' : ' · live data kept' ?></span></td>
            <td data-label="Registrations" class="mono"><?= number_format((int) ($c['registrations'] ?? 0)) ?></td>
            <td data-label="Check-ins" class="mono"><?= number_format((int) ($c['attendances'] ?? 0)) ?></td>
            <td data-label="Watch passes" class="mono"><?= number_format((int) ($c['watch_passes'] ?? 0)) ?></td>
            <td data-label="Commitments" class="mono"><?= number_format((int) ($c['commitments'] ?? 0)) ?></td>
            <td data-label="Archived" class="mono"><?= date('j M Y, H:i', strtotime((string) $a['created_at'])) ?><?= $a['created_by'] !== '' ? '<br><span class="adm-muted">' . e($a['created_by']) . '</span>' : '' ?></td>
            <td><a class="adm-btn" href="<?= url('/admin/archives/view?slug=' . rawurlencode($a['slug'])) ?>">Open</a></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </section>
  <?php endif; ?>

  <form method="post" action="<?= url('/admin/archives') ?>" class="adm-form">
    <?= csrf_field() ?>
    <section class="adm-panel adm-form__section">
      <header class="adm-form__head">
        <h2 class="adm-panel__title">Archive the current edition</h2>
        <p>Copies everything from <strong><?= e($edition) ?></strong> into an archive you can open, search and export here. Admin users, payment and stream settings stay as they are.</p>
      </header>
      <div class="adm-field">
        <label class="adm-field__label" for="archive-label">Name</label>
        <input id="archive-label" type="text" name="label" required maxlength="120" placeholder="London 2026" autocomplete="off">
        <p class="adm-field__hint">Includes <?= e(implode(', ', array_map('strtolower', $tables))) ?>.</p>
      </div>
      <label class="adm-check">
        <input type="checkbox" name="clear" value="1" data-archive-clear>
        <span><strong>Start a new edition</strong>After the copy is checked, empty the summit registrations, check-ins, watch passes, chat, polls, emails and analytics so registration starts from zero. Initiative members and their commitments stay, because the Initiative carries on between editions.</span>
      </label>
      <div class="adm-field adm-field--danger" data-archive-confirm hidden>
        <label class="adm-field__label" for="archive-confirm">Type <?= e($confirm) ?> to empty the live tables</label>
        <input id="archive-confirm" type="text" name="confirm" autocomplete="off" spellcheck="false">
      </div>
    </section>
    <div class="adm-form__actions"><button type="submit" class="adm-btn adm-btn--dark adm-btn--lg"><?= ph('archive') ?> Archive</button></div>
  </form>
</section>
<script>
(function () {
  var box = document.querySelector('[data-archive-clear]'), row = document.querySelector('[data-archive-confirm]');
  if (!box || !row) return;
  box.addEventListener('change', function () { row.hidden = !box.checked; });
})();
</script>
