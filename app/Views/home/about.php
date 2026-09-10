<?php /** @var array $summit */ ?>

<section class="about-hero section">
  <div class="container about-hero__grid">
    <div data-reveal>
      <p class="eyebrow"><span class="eyebrow__dot"></span>The Initiative</p>
      <h1 class="about-hero__title" data-split>Loveworld<br>Kingdom<br>Producers</h1>
    </div>
    <div class="about-hero__copy" data-reveal>
      <p class="about-hero__lede">
        An initiative of the Loveworld Consulate UK to identify, equip and develop
        100 young people per edition into measurable Kingdom Producers.
      </p>
      <p>
        It goes beyond attending a summit. Participants enter a structured 30, 60 and 90 day
        production journey: first working out what they can produce, then developing and testing it,
        then launching something tangible. A product, a service, a solution, an enterprise
        or a piece of intellectual property.
      </p>
      <p>
        The summit in Rainham is the starting point. What it grows into is a permanent Kingdom Producers
        ecosystem carrying mentorship, networks, marketplace access, expert support, investment
        opportunities and collaboration.
      </p>
      <dl class="statement__facts mono">
        <div><dt>Per edition</dt><dd>100 producers</dd></div>
        <div><dt>Principle</dt><dd><?= e($summit['motto']) ?></dd></div>
      </dl>
    </div>
  </div>
</section>

<section class="pillars section section--ink">
  <div class="container">
    <div class="section__head" data-reveal>
      <p class="eyebrow eyebrow--light"><span class="eyebrow__dot"></span>What the initiative does</p>
      <h2 class="section__title">Four things it gives you</h2>
    </div>
    <div class="pillars__grid">
      <?php
      $pillars = [
        ['Register',  'A list of every producer, by field and by city, so people can find each other.'],
        ['Resources', 'One place for the playbooks, templates, case files and session recordings.'],
        ['Rhythm',    'Cohorts, mentoring and showcases running through the year.'],
        ['Reach',     'Essex first, then other UK cities, then the wider Loveworld community.'],
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

<section class="perks section" id="journey">
  <div class="container">
    <div class="section__head" data-reveal>
      <p class="eyebrow"><span class="eyebrow__dot"></span>What you do in the initiative</p>
      <h2 class="section__title">A 30, 60 and 90 day journey.</h2>
      <p class="section__lede">You are not left to work it out alone. Each stage has guidance, people and something you are expected to have made by the end of it.</p>
    </div>
    <div class="perks__grid">
      <?php
      $journey = [
        ['Discover what you can produce', 'Start from the skills, ideas and opportunities you already have, and learn how to turn them into something of value.'],
        ['Learn the practical ground', 'Business, entrepreneurship, technology, innovation and intellectual property, taught as things you apply rather than things you note down.'],
        ['Get mentorship and access', 'Expert guidance, and a network of producers and professionals who have already built what you are building.'],
        ['Run your own 30-60-90 plan', 'Write your producers plan, work it, and be accountable to it through the journey.'],
        ['Take it to market', 'Showcase, pitch, collaborate, reach markets and grow what you have produced.'],
        ['Stay in the network', 'The Kingdom Producers network continues after the edition ends, with mentorship, resources, opportunities and collaboration.'],
      ];
      foreach ($journey as $i => [$name, $desc]): ?>
        <article class="perk" data-reveal>
          <span class="perk__num mono"><?= str_pad((string) ($i + 1), 2, '0', STR_PAD_LEFT) ?></span>
          <h3 class="perk__title"><?= e($name) ?></h3>
          <p class="perk__desc"><?= e($desc) ?></p>
        </article>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<section class="statement statement--plain section" id="become">
  <div class="container statement__grid">
    <h2 class="statement__title" data-split>From consumer to producer.</h2>
    <div class="statement__body" data-reveal>
      <p>
        The goal is to develop you into a creator of value: a builder of enterprises, an owner of
        intellectual property, a developer of solutions, a producer of goods and services, an employer
        of people and a contributor to the prosperity of nations.
      </p>
      <p>
        In the end you do not simply become someone who produces. You become someone able to create
        opportunities, mentor others and produce other producers.
      </p>
      <dl class="statement__facts mono">
        <div><dt>Per edition</dt><dd>100 producers</dd></div>
        <div><dt>Across the UK</dt><dd>1,000 producers</dd></div>
        <div><dt>Globally</dt><dd>10,000 producers</dd></div>
      </dl>
      <p>
        The long-term vision is a permanent ecosystem where Kingdom Producers create, build, innovate,
        own, produce, employ, trade and multiply, building enterprises and solutions that advance the
        Kingdom and reach nations.
      </p>
    </div>
  </div>
</section>

<section class="cta section section--stamp">
  <div class="container cta__inner">
    <p class="eyebrow eyebrow--light" data-reveal><span class="eyebrow__dot"></span>Free to join</p>
    <h2 class="cta__title" data-split>Put your name<br>on the register.</h2>
    <div class="cta__actions" data-reveal>
      <a href="<?= url('/register') ?>?mode=initiative" class="btn btn--paper"><span class="btn__label">Join the initiative</span><span class="btn__arrow" aria-hidden="true"><?= icon_arrow() ?></span></a>
    </div>
  </div>
</section>
