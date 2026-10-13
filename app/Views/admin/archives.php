<?php /** @var array $archives @var array<string,string> $tables @var string $flash @var string $edition @var string $confirm */
$event = \App\Core\Events::label();
$others = array_map([\App\Core\Events::class, 'label'], array_diff(\App\Core\Events::SLUGS, [\App\Core\Events::active()]));
?>
<section class="adm-page">
  <header class="adm-page__head">
    <div>
      <p class="eyebrow"><span class="eyebrow__dot"></span>Editions</p>
      <h1 class="adm-page__title">Archive <span class="adm-page__title-sub"><?= e($event) ?> · <?= count($archives) ?> past edition<?= count($archives) === 1 ? '' : 's' ?></span></h1>
    </div>
  </header>
  <p class="adm-muted" style="margin:-1rem 0 1.25rem;max-width:70ch">Each event keeps its own archive. You are looking at <?= e($event) ?>'s<?= $others ? '; switch to ' . e(implode(' or ', $others)) . ' at the top to see theirs' : '' ?>.</p>

  <?php if ($flash !== ''): ?>
    <div class="form__alert" role="status"><span><?= e($flash) ?></span></div>
  <?php endif; ?>

  <?php if ($archives): ?>
  <section class="adm-panel adm-panel--flush" style="margin-bottom:1.25rem">
    <div class="adm-table-wrap">
      <table class="adm-table">
        <thead><tr><th>Edition</th><th>Registrations</th><th>Check-ins</th><th>Exhibitors</th><th>Sponsorships</th><th>Archived</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($archives as $a): $c = $a['counts']; ?>
          <tr>
            <td data-label="Edition"><a class="adm-link" href="<?= url('/admin/archives/view?slug=' . rawurlencode($a['slug'])) ?>"><strong><?= e($a['label']) ?></strong></a><br><span class="adm-muted mono"><?= $a['cleared'] ? 'Started a new edition' : 'Snapshot only, live data kept' ?></span></td>
            <td data-label="Registrations" class="mono"><?= number_format((int) ($c['registrations'] ?? 0)) ?></td>
            <td data-label="Check-ins" class="mono"><?= number_format((int) ($c['attendances'] ?? 0)) ?></td>
            <td data-label="Exhibitors" class="mono"><?= number_format((int) ($c['exhibitors'] ?? 0)) ?></td>
            <td data-label="Sponsorships" class="mono"><?= number_format((int) ($c['sponsorships'] ?? 0)) ?></td>
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
        <h2 class="adm-panel__title">Archive <?= e($edition) ?></h2>
        <p>Copies <?= e($event) ?>'s data into an archive you can open, search and export here. <?= $others ? e(implode(' and ', $others)) . ' is not touched.' : '' ?></p>
      </header>
      <div class="adm-field">
        <label class="adm-field__label" for="archive-label">Name</label>
        <input id="archive-label" type="text" name="label" required maxlength="120" placeholder="<?= e($event) ?> 2026" autocomplete="off">
        <p class="adm-field__hint">Short and dated works best, e.g. "<?= e($event) ?> 2026".</p>
      </div>
      <label class="adm-check">
        <input type="checkbox" name="clear" value="1" data-archive-clear>
        <span><strong>Also start a new edition</strong>After the copy is checked, <?= e($event) ?>'s registrations start from zero.
          <span class="arch-outcome">
            <span><b>Emptied</b>Summit places, check-ins, watch passes, exhibitors, sponsorships, live chat, polls, emails, stream log</span>
            <span><b>Kept</b>Initiative members, commitments and site analytics, which carry on between editions</span>
          </span>
        </span>
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
