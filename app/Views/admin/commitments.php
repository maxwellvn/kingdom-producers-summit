<?php /** @var array $rows @var array<string,string> $items @var string $link */ ?>
<section class="adm-page">
  <header class="adm-page__head">
    <div>
      <p class="eyebrow"><span class="eyebrow__dot"></span>The Commitment</p>
      <h1 class="adm-page__title"><?= count($rows) ?> <span class="adm-page__title-sub">card<?= count($rows) === 1 ? '' : 's' ?></span></h1>
    </div>
    <div style="display:flex;flex-wrap:wrap;gap:.5rem;justify-content:flex-end">
      <a class="adm-btn" href="<?= url('/admin/commitments.csv') ?>">Export CSV ↓</a>
    </div>
  </header>

  <div class="adm-columns adm-columns--2" style="margin-bottom:1.4rem">
    <section class="adm-panel">
      <h2 class="adm-panel__title">Scan to commit</h2>
      <p class="adm-muted" style="margin:.3rem 0 .9rem">Put this on the screen at the end of the session. It opens <a href="<?= e($link) ?>" target="_blank" rel="noopener"><?= e($link) ?></a>.</p>
      <img src="<?= url('/admin/commitments/qr.png') ?>" alt="QR code for the commitment page" width="280" height="280" style="display:block;background:#fff;border:1px solid rgba(27,34,66,.16);max-width:100%">
      <p style="margin:.8rem 0 0"><a class="adm-btn" href="<?= url('/admin/commitments/qr.png') ?>" download="commitment-qr.png">Download PNG</a></p>
    </section>
    <section class="adm-panel">
      <h2 class="adm-panel__title">The four</h2>
      <ol style="margin:.6rem 0 0;padding-left:1.2rem;display:grid;gap:.4rem">
        <?php foreach ($items as $text): ?><li><?= e($text) ?></li><?php endforeach; ?>
      </ol>
    </section>
  </div>

  <section class="adm-panel">
    <?php if (!$rows): ?>
      <p class="adm-muted">No cards yet.</p>
    <?php else: ?>
    <div class="adm-table-wrap">
      <table class="adm-table">
        <thead><tr><th>Name</th><th>Contact</th><th>Commitments</th><th>When</th></tr></thead>
        <tbody>
        <?php foreach ($rows as $r): ?>
          <tr>
            <td data-label="Name"><?= e(trim(($r['title'] ?? '') . ' ' . $r['first_name'] . ' ' . $r['last_name'])) ?></td>
            <td data-label="Contact"><?= e($r['email'] ?? '') ?><?php if ($r['kingschat']): ?><br><span class="adm-muted">@<?= e($r['kingschat']) ?></span><?php endif; ?></td>
            <td data-label="Commitments" class="mono"><?= implode(' · ', array_map(static fn ($k) => str_pad((string) (array_search($k, array_keys($items), true) + 1), 2, '0', STR_PAD_LEFT), array_filter(array_keys($items), static fn ($k) => (int) $r[$k] === 1))) ?></td>
            <td data-label="When" class="mono"><?= date('j M, H:i', strtotime((string) $r['created_at'])) ?></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php endif; ?>
  </section>
</section>
