<?php /** @var array $summit */ ?>

<section class="about-hero section">
  <div class="container about-hero__grid">
    <div data-reveal>
      <p class="eyebrow"><span class="eyebrow__dot"></span>The Initiative</p>
      <h1 class="about-hero__title" data-split>Loveworld<br>Kingdom<br>Producers</h1>
    </div>
    <div class="about-hero__copy" data-reveal>
      <p class="about-hero__lede">
        A long-term initiative of the Loveworld Consulate UK to grow a body of producers —
        people who make, build, publish, manufacture, teach and multiply — across every field and every age.
      </p>
      <p>
        The London summit is the opening moment. The initiative is the work that continues after it:
        a register of producers, a portal that holds the method, and a rhythm of cohorts, mentoring
        and showcases that moves people from <em>Emerge</em> to <em>Multiply</em>.
      </p>
      <dl class="statement__facts mono">
        <div><dt>Target</dt><dd>100 → 1,000 UK → 10,000 global</dd></div>
        <div><dt>Principle</dt><dd><?= e($summit['motto']) ?></dd></div>
      </dl>
    </div>
  </div>
</section>

<section class="pillars section section--ink">
  <div class="container">
    <div class="section__head" data-reveal>
      <p class="eyebrow eyebrow--light"><span class="eyebrow__dot"></span>What the initiative does</p>
      <h2 class="section__title">Four commitments</h2>
    </div>
    <div class="pillars__grid">
      <?php
      $pillars = [
        ['Register',  'A living register of Kingdom Producers by field, stage, city and country — so producers can be found, counted and connected.'],
        ['Repository','A detailed, organised repository in the portal: playbooks, case files, capital pathways, templates and session archives.'],
        ['Rhythm',    'Cohorts, mentoring windows and showcases through the year, organised around the producer\'s path.'],
        ['Reach',     'From the London edition outward — to other UK cities, then to the global Loveworld community.'],
      ];
      foreach ($pillars as $i => [$name, $desc]): ?>
        <article class="pillar" data-reveal>
          <span class="pillar__num mono"><?= str_pad((string) ($i + 1), 2, '0', STR_PAD_LEFT) ?></span>
          <h3 class="pillar__title"><?= e($name) ?></h3>
          <p><?= e($desc) ?></p>
        </article>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<section class="cta section section--stamp">
  <div class="container cta__inner">
    <p class="eyebrow eyebrow--light" data-reveal><span class="eyebrow__dot"></span>Membership is free</p>
    <h2 class="cta__title" data-split>Put your name<br>on the register.</h2>
    <div class="cta__actions" data-reveal>
      <a href="<?= url('/register') ?>?mode=initiative" class="btn btn--paper"><span class="btn__label">Join the initiative</span><span class="btn__arrow" aria-hidden="true"><?= icon_arrow() ?></span></a>
    </div>
  </div>
</section>
