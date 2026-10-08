<?php
/** @var array<string, array{summit: array, stats: array, stages: array, attendance: array}> $events */
$sum = static fn (string $k): int => array_sum(array_map(static fn (array $e) => $e['stats'][$k], $events));
$places = $sum('onsite') + $sum('online');
?>
<style>
  .status-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 1.25rem; }
  @media (max-width: 860px) { .status-grid { grid-template-columns: 1fr; } }
  .status-event__head { display: flex; justify-content: space-between; align-items: baseline; gap: .5rem 1rem; flex-wrap: wrap; margin-bottom: 1.1rem; }
  .status-event__when { font-size: .8rem; color: var(--a-text-3); }
  .status-figs { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 1px; margin: 0 0 1.4rem; background: var(--a-border); border: 1px solid var(--a-border); border-radius: var(--a-r-sm); overflow: hidden; }
  .status-figs > div { padding: .8rem .9rem; background: var(--a-surface); }
  .status-figs dt { font-size: .75rem; color: var(--a-text-2); }
  .status-figs dd { margin: .3rem 0 0; font-size: 1.35rem; font-weight: 600; letter-spacing: -.03em; font-variant-numeric: tabular-nums; color: var(--a-text); }
  .status-live { display: inline-flex; align-items: center; gap: .45rem; }
  .status-live::before { content: ""; width: 6px; height: 6px; border-radius: 50%; background: var(--a-accent); animation: status-pulse 2s var(--a-ease) infinite; }
  @keyframes status-pulse { 50% { opacity: .35; } }
  @media (prefers-reduced-motion: reduce) { .status-live::before { animation: none; } }
  @media (max-width: 480px) { .status-figs { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
</style>
<section class="adm-page">
  <header class="adm-page__head">
    <div>
      <p class="eyebrow"><img src="<?= asset('img/crest.png') ?>" alt="" width="22" height="16"> <?= e((string) config('app.name')) ?></p>
      <h1 class="adm-page__title">Registration status</h1>
    </div>
    <span class="mono adm-page__meta status-live">Updated <?= date('j M Y, H:i') ?> · refreshes every minute</span>
  </header>

  <div class="adm-stats">
    <div class="adm-stat adm-stat--lead">
      <span class="adm-stat__label mono">Summit places, both events</span>
      <span class="adm-stat__value"><?= number_format($places) ?></span>
      <span class="adm-stat__sub mono">+<?= $sum('today') ?> today</span>
    </div>
    <div class="adm-stat">
      <span class="adm-stat__label mono">Onsite</span>
      <span class="adm-stat__value"><?= number_format($sum('onsite')) ?></span>
    </div>
    <div class="adm-stat">
      <span class="adm-stat__label mono">Online</span>
      <span class="adm-stat__value"><?= number_format($sum('online')) ?></span>
    </div>
    <div class="adm-stat">
      <span class="adm-stat__label mono">Initiative members</span>
      <span class="adm-stat__value"><?= number_format($sum('initiative')) ?></span>
    </div>
  </div>

  <div class="status-grid">
    <?php foreach ($events as $slug => $ev): $s = $ev['stats']; $max = max(1, ...array_column($ev['stages'], 'count')); ?>
      <section class="adm-panel" aria-labelledby="ev-<?= e($slug) ?>">
        <div class="status-event__head">
          <h2 class="adm-panel__title" id="ev-<?= e($slug) ?>"><?= e(\App\Core\Events::label($slug)) ?></h2>
          <span class="mono status-event__when"><?= e(trim(($ev['summit']['date_day'] ?? '') . ' · ' . ($ev['summit']['place'] ?? ''), ' ·')) ?></span>
        </div>
        <dl class="status-figs">
          <div><dt>Summit places</dt><dd><?= number_format($s['onsite'] + $s['online']) ?></dd></div>
          <div><dt>Onsite</dt><dd><?= number_format($s['onsite']) ?></dd></div>
          <div><dt>Online</dt><dd><?= number_format($s['online']) ?></dd></div>
          <div><dt>New today</dt><dd><?= number_format($s['today']) ?></dd></div>
          <div><dt>Countries</dt><dd><?= number_format($s['countries']) ?></dd></div>
          <div><dt>Checked in</dt><dd><?= number_format($ev['attendance']['total']) ?></dd></div>
        </dl>
        <h3 class="adm-panel__title" style="font-size:.88rem;margin-bottom:.9rem">Producer stage</h3>
        <ul class="adm-bars">
          <?php foreach ($ev['stages'] as $st): ?>
            <li class="adm-bar">
              <span class="adm-bar__label"><?= e(ucfirst($st['label'])) ?></span>
              <span class="adm-bar__track"><span class="adm-bar__fill" style="width: <?= round($st['count'] / $max * 100) ?>%"></span></span>
              <span class="adm-bar__value mono"><?= $st['count'] ?></span>
            </li>
          <?php endforeach; ?>
        </ul>
      </section>
    <?php endforeach; ?>
  </div>
</section>
