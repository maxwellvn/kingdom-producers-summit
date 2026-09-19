<?php /** @var string $name @var array<string,string> $items */ ?>
<section class="reg">
  <div class="container pay">
    <div class="pay__card">
      <p class="mono pay__kicker"><span class="pay__dot is-live" aria-hidden="true"></span> Received</p>
      <h1 class="pay__title"><?= $name !== '' ? 'Thank you, ' . e(explode(' ', $name)[0]) . '.' : 'Thank you.' ?></h1>
      <p class="pay__lede">Your commitment is in. We will pray over it together. Keep these four in front of you this year:</p>
      <ol class="commit__list commit__list--plain">
        <?php foreach ($items as $text): ?><li><?= e($text) ?></li><?php endforeach; ?>
      </ol>
      <p class="pay__fine mono"><a href="<?= url('/') ?>">Back to the summit</a></p>
    </div>
  </div>
</section>
