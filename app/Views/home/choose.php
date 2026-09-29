<?php
/**
 * @var array<string, array> $events each event's summit block, keyed by slug
 */
$date = (string) ($events['manchester']['date_day'] ?? '');
$copy = [
    'manchester' => ['label' => 'Manchester', 'country' => 'England, United Kingdom', 'note' => 'Halls, mills and the old town hall.'],
    'ireland'    => ['label' => 'Ireland', 'country' => 'Dublin, Cork, Galway and beyond', 'note' => 'Stone bridges, spires and the long river.'],
];
?>

<section class="choose" id="choose">
  <div class="container choose__head">
    <p class="eyebrow eyebrow--pill"><?= e($date) ?></p>
    <h1 class="choose__title">Two cities.<br>One day.<br><span>Choose yours.</span></h1>
    <p class="choose__lede">
      The Loveworld Kingdom Producers Summit runs in Manchester and across Ireland at the same time.
      Each has its own venue, its own room and its own live stream, so pick where you will be.
    </p>
  </div>

  <div class="container choose__grid">
    <?php foreach ($events as $slug => $ev): ?>
      <?php
        $v = (array) ($ev['venue'] ?? []);
        $hasVenue = trim((string) ($v['name'] ?? '')) !== '';
        $venue = $hasVenue
            ? implode(', ', array_filter([$v['name'] ?? '', trim(($v['town'] ?? '') . ' ' . ($v['postcode'] ?? ''))]))
            : 'Venue to be announced';
        $art = (array) config('app.artwork.' . ($ev['artwork'] ?? $slug));
        $print = (string) ($art['summit'] ?? '');
        $c = $copy[$slug];
        $time = trim((string) ($ev['time'] ?? ''));
      ?>
      <a class="choose__panel choose__panel--<?= e($slug) ?>" href="<?= e(url('/' . $slug)) ?>" style="--print:url('<?= e(asset('img/' . $print)) ?>')" data-choose>
        <span class="choose__print" aria-hidden="true"></span>
        <span class="choose__body">
          <span class="choose__tag mono"><?= e($c['country']) ?></span>
          <span class="choose__city"><?= e($c['label']) ?></span>
          <span class="choose__note"><?= e($c['note']) ?></span>
          <span class="choose__facts">
            <span class="choose__fact"><i class="ph ph-map-pin" aria-hidden="true"></i><?= e($venue) ?></span>
            <span class="choose__fact"><i class="ph ph-calendar-blank" aria-hidden="true"></i><?= e((string) ($ev['date_day'] ?? '')) ?><?= $time !== '' ? ', ' . e($time) : '' ?></span>
            <span class="choose__modes mono"><span>Onsite</span><span>Online</span></span>
          </span>
          <span class="choose__cta btn btn--ink">
            <span class="btn__label">Enter <?= e($c['label']) ?></span>
            <span class="btn__arrow" aria-hidden="true"><?= icon_arrow() ?></span>
          </span>
        </span>
      </a>
    <?php endforeach; ?>
  </div>

  <p class="container choose__foot mono">Same programme. Same crest. Different city. You can switch at any time from the menu.</p>
</section>
