<?php /** @var array $stats @var array $stages @var array $recent @var array $attendance */
$pathLabel = ['onsite' => 'Onsite', 'online' => 'Online', 'initiative' => 'Initiative'];
$max = max(1, ...array_column($stages, 'count'));
?>
<section class="adm-page">
  <header class="adm-page__head">
    <div>
      <p class="eyebrow"><span class="eyebrow__dot"></span>Overview</p>
      <h1 class="adm-page__title">Registrations</h1>
    </div>
    <span class="mono adm-page__meta">Updated <?= date('j M Y, H:i') ?></span>
  </header>

  <div class="adm-stats">
    <div class="adm-stat adm-stat--lead">
      <span class="adm-stat__label mono">Total registered</span>
      <span class="adm-stat__value"><?= number_format($stats['total']) ?></span>
      <span class="adm-stat__sub mono">+<?= $stats['today'] ?> today · <?= $stats['countries'] ?> countries</span>
    </div>
    <div class="adm-stat">
      <span class="adm-stat__label mono">A · Onsite</span>
      <span class="adm-stat__value"><?= number_format($stats['onsite']) ?></span>
    </div>
    <div class="adm-stat">
      <span class="adm-stat__label mono">B · Online</span>
      <span class="adm-stat__value"><?= number_format($stats['online']) ?></span>
    </div>
    <div class="adm-stat">
      <span class="adm-stat__label mono">C · Initiative</span>
      <span class="adm-stat__value"><?= number_format($stats['initiative']) ?></span>
    </div>
    <a class="adm-stat adm-stat--attendance" href="<?= url('/admin/scanner') ?>">
      <span class="adm-stat__label mono">Checked in today</span>
      <span class="adm-stat__value"><?= number_format($attendance['today']) ?></span>
      <span class="adm-stat__sub mono"><?= number_format($attendance['total']) ?> total arrivals</span>
    </a>
  </div>

  <div class="adm-grid">
    <section class="adm-panel">
      <h2 class="adm-panel__title">Producer stage</h2>
      <ul class="adm-bars">
        <?php foreach ($stages as $s): ?>
          <li class="adm-bar">
            <span class="adm-bar__label"><?= e(ucfirst($s['label'])) ?></span>
            <span class="adm-bar__track"><span class="adm-bar__fill" style="width: <?= round($s['count'] / $max * 100) ?>%"></span></span>
            <span class="adm-bar__value mono"><?= $s['count'] ?></span>
          </li>
        <?php endforeach; ?>
      </ul>
    </section>

    <section class="adm-panel adm-panel--wide">
      <div class="adm-panel__head">
        <h2 class="adm-panel__title">Most recent</h2>
        <a class="mono adm-link" href="<?= url('/admin/registrations') ?>">All registrations →</a>
      </div>
      <?php if (!$recent): ?>
        <p class="adm-empty mono">No registrations yet.</p>
      <?php else: ?>
        <table class="adm-table">
          <thead><tr><th>Reference</th><th>Name</th><th>Path</th><th>Field</th><th>Country</th><th>When</th></tr></thead>
          <tbody>
            <?php foreach ($recent as $r): ?>
              <tr>
                <td class="mono"><?= e($r['reference']) ?></td>
                <td><?= e($r['first_name'] . ' ' . $r['last_name']) ?><br><span class="adm-muted"><?= e($r['email']) ?></span></td>
                <td><span class="adm-pill adm-pill--<?= e($r['participation']) ?>"><?= e($pathLabel[$r['participation']] ?? $r['participation']) ?></span></td>
                <td><?= e($r['field']) ?></td>
                <td><?= e($r['country']) ?></td>
                <td class="mono adm-muted"><?= e(date('j M, H:i', strtotime($r['created_at']))) ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      <?php endif; ?>
    </section>
  </div>
</section>
