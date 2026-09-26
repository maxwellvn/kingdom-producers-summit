<?php /** @var array<string,string> $fields @var array<string,string> $values @var string $flash */
$groups = [
    'The edition' => ['edition', 'place', 'city', 'artwork', 'starts_at', 'ends_at', 'show_time'],
    'Venue'       => ['venue.name', 'venue.unit', 'venue.street', 'venue.town', 'venue.region', 'venue.postcode'],
    'Getting there' => ['travel.train', 'travel.bus', 'travel.car'],
];
$hints = [
    'edition'   => 'Shown across the site and in emails, e.g. "Manchester Edition 2026".',
    'place'     => 'The town people travel to, used in lines such as "the room is in Manchester".',
    'city'      => 'The place line in the hero, e.g. "Manchester, United Kingdom".',
    'artwork'   => 'The halftone prints and accent colours the site wears.',
    'starts_at' => 'Leave blank and the site says "Date to be announced".',
    'ends_at'   => 'Used for calendar files. Blank means six hours after the start.',
    'venue.name' => 'Leave blank and the site says "Venue to be announced" and hides directions.',
    'travel.train' => 'Each travel line shows only when it has text.',
];
$local = static fn (string $v): string => $v !== '' && strtotime($v) !== false ? date('Y-m-d\TH:i', strtotime($v)) : '';
?>
<section class="adm-page">
  <header class="adm-page__head">
    <div>
      <p class="eyebrow"><span class="eyebrow__dot"></span>Editions</p>
      <h1 class="adm-page__title">Current edition <span class="adm-page__title-sub"><?= e($values['edition'] ?? '') ?></span></h1>
    </div>
  </header>

  <?php if ($flash !== ''): ?>
    <div class="form__alert" role="status"><span><?= e($flash) ?></span></div>
  <?php endif; ?>

  <p class="adm-muted" style="margin:-.75rem 0 1.25rem">Everything here goes live as soon as you save: the site, the emails and the calendar files.</p>

  <form method="post" action="<?= url('/admin/edition') ?>" class="adm-form">
    <?= csrf_field() ?>
    <?php foreach ($groups as $legend => $keys): ?>
      <section class="adm-panel adm-form__section">
        <header class="adm-form__head"><h2 class="adm-panel__title"><?= e($legend) ?></h2></header>
        <div class="adm-form__grid<?= $legend === 'Getting there' ? '' : ' adm-form__grid--2' ?>">
        <?php foreach ($keys as $key): $name = str_replace('.', '__', $key); $value = (string) ($values[$key] ?? ''); ?>
          <div class="adm-field">
            <?php if ($key !== 'show_time'): ?><label class="adm-field__label" for="f-<?= e($name) ?>"><?= e($fields[$key]) ?><?= in_array($key, ['edition', 'place', 'artwork'], true) ? '' : ' <span class="adm-field__opt">Optional</span>' ?></label><?php endif; ?>
            <?php if (str_starts_with($key, 'travel.')): ?>
              <textarea id="f-<?= e($name) ?>" name="<?= e($name) ?>" rows="3" maxlength="600"><?= e($value) ?></textarea>
            <?php elseif ($key === 'show_time'): ?>
              <label class="adm-check"><input type="checkbox" name="<?= e($name) ?>" value="1" <?= ($value === '' || $value === '1') ? 'checked' : '' ?>><span><strong>Show the start time</strong>Untick while the time is not settled: the site, emails and calendar show the day only.</span></label>
            <?php elseif ($key === 'artwork'): ?>
              <select id="f-<?= e($name) ?>" name="<?= e($name) ?>">
                <?php foreach (array_keys((array) config('app.artwork')) as $set): ?>
                  <option value="<?= e($set) ?>" <?= ($value ?: 'london') === $set ? 'selected' : '' ?>><?= e(ucfirst($set)) ?></option>
                <?php endforeach; ?>
              </select>
            <?php elseif (in_array($key, ['starts_at', 'ends_at'], true)): ?>
              <input id="f-<?= e($name) ?>" type="datetime-local" name="<?= e($name) ?>" value="<?= e($local($value)) ?>">
            <?php else: ?>
              <input id="f-<?= e($name) ?>" type="text" name="<?= e($name) ?>" value="<?= e($value) ?>" maxlength="160" <?= in_array($key, ['edition', 'place'], true) ? 'required' : '' ?>>
            <?php endif; ?>
            <?php if (isset($hints[$key])): ?><p class="adm-field__hint"><?= e($hints[$key]) ?></p><?php endif; ?>
          </div>
        <?php endforeach; ?>
        </div>
      </section>
    <?php endforeach; ?>
    <div class="adm-form__actions"><button type="submit" class="adm-btn adm-btn--dark adm-btn--lg"><?= ph('floppy-disk') ?> Save edition</button></div>
  </form>
</section>
