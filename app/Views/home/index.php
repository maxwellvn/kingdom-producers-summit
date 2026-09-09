<?php /** @var array $summit */ ?>

<!-- ============ HERO ============ -->
<section class="hero" id="top">
  <div class="hero__bg" aria-hidden="true">
    <?php for ($panel = 0; $panel < 6; $panel++): ?>
      <div class="hero__panel" data-parallax="<?= $panel % 2 === 0 ? '35' : '-35' ?>" style="background-image:url('<?= e(asset('img/producers-hero-v3.jpg')) ?>')"></div>
    <?php endfor; ?>
  </div>
  <span class="hero__vertical" aria-hidden="true">PRODUCERS</span>

  <div class="container hero__grid">
    <div class="hero__copy">
      <p class="eyebrow eyebrow--pill hero__eyebrow">
        The Loveworld Kingdom Producers Summit
      </p>

      <h1 class="hero__title">
        <span class="hero__line">From</span>
        <span class="hero__line">Consumers</span>
        <span class="hero__line hero__line--accent">to Producers</span>
      </h1>

      <p class="hero__lede">
        A summit and a working initiative for people who are done
        consuming — and ready to
        <em class="produce-trigger" data-video-trigger
            data-video-src="<?= e(asset('media/cwe-tradefair.mp4')) ?>"
            tabindex="0" role="button" aria-haspopup="dialog">produce</em>.
      </p>

      <p class="hero__date mono">
        <span class="hero__date-dot" aria-hidden="true"></span>
        <?= e($summit['date_text']) ?> &middot; <?= e($summit['city']) ?>
      </p>

      <div class="hero__actions">
        <a href="<?= url('/register') ?>?mode=onsite" class="btn btn--stamp">
          <span class="btn__label">Register to attend</span>
          <span class="btn__arrow" aria-hidden="true"><?= icon_arrow() ?></span>
        </a>
        <a href="<?= url('/register') ?>?mode=initiative" class="btn btn--outline">
          <span class="btn__label">Join the initiative</span>
        </a>
      </div>
    </div>

    <div class="hero__collage">
      <figure class="poster">
        <img class="poster__crest" src="<?= e(asset('img/crest.png')) ?>" alt="Loveworld Consulate United Kingdom crest" width="1040" height="764">
        <span class="poster__edition">September<br>Edition</span>
        <span class="poster__rule" aria-hidden="true"></span>
        <span class="poster__loc mono">London · United Kingdom <b>· 2026</b></span>

        <div class="collage__note">
          <span class="hand">Next<br>level</span>
        </div>
      </figure>
    </div>
  </div>

  <div class="hero__scroll mono" aria-hidden="true">
    <span>Scroll</span>
    <span class="hero__scroll-line"></span>
  </div>
</section>

<!-- ============ SUPPORTING MINISTRIES ============ -->
<aside class="supporters" aria-labelledby="supportersTitle">
  <div class="supporters__inner container">
    <div class="supporters__heading">
      <h2 class="supporters__title" id="supportersTitle">Supporting Ministries</h2>
    </div>
    <div class="supporters__list">
    <?php foreach ($summit['partners'] as $partner): ?>
      <div class="supporters__org">
        <img src="<?= e(asset('img/crest.png')) ?>" alt="" width="44" height="32">
        <span><?= e($partner) ?></span>
      </div>
    <?php endforeach; ?>
    </div>
  </div>
</aside>

<!-- ============ STATEMENT ============ -->
<section class="statement section" id="summit">
  <div class="container statement__grid">
    <h2 class="statement__title" data-split>
      A London gathering for people ready to build.
    </h2>
    <figure class="statement__image" data-reveal>
      <img src="<?= e(asset('img/producers-summit-v3.jpg')) ?>" alt="A diverse group of producers discussing a project during a public summit" width="1448" height="1086" loading="lazy">
    </figure>
    <div class="statement__body" data-reveal>
      <p>
        The Loveworld Kingdom Producers Summit brings together makers, founders,
        creatives, engineers, students and ministers around one conviction:
        strong communities are produced, not consumed.
      </p>
      <p>
        Producers at every stage share methods, capital pathways and case studies,
        then leave with a practical plan for what they will build next.
      </p>
    </div>
  </div>
</section>

<!-- ============ PATHWAYS ============ -->
<section class="pathways section section--ink" id="pathways">
  <div class="container">
    <div class="section__head" data-reveal>
      <h2 class="section__title">Choose how you will take part.</h2>
      <p class="section__lede">Attend in London, follow the programme online, or join the ongoing producer initiative.</p>
    </div>

    <div class="pathways__grid">
      <article class="path" data-reveal data-reveal-delay="0">
        <h3 class="path__title">Attend in London</h3>
        <p class="path__copy">Join the sessions, producer showcases, masterclasses and working rooms in person.</p>
        <a href="<?= url('/register') ?>?mode=onsite" class="path__cta">Register to attend <span class="path__cta-icon" aria-hidden="true"><?= icon_arrow() ?></span></a>
      </article>

      <article class="path" data-reveal data-reveal-delay="0.1">
        <h3 class="path__title">Attend online</h3>
        <p class="path__copy">Join the live stream from Rainham, Essex — programme, session recordings and practical producer resources.</p>
        <a href="<?= url('/register') ?>?mode=online" class="path__cta">Attend online <span class="path__cta-icon" aria-hidden="true"><?= icon_arrow() ?></span></a>
      </article>

      <article class="path" data-reveal data-reveal-delay="0.2">
        <h3 class="path__title">Join the initiative</h3>
        <p class="path__copy">Build a producer profile and access methods, case studies, mentoring and opportunities.</p>
        <a href="<?= url('/register') ?>?mode=initiative" class="path__cta">Become a producer <span class="path__cta-icon" aria-hidden="true"><?= icon_arrow() ?></span></a>
      </article>
    </div>
  </div>
</section>

<!-- ============ PROCESS ============ -->
<section class="process section" id="process">
  <div class="container">
    <div class="section__head process__head" data-reveal>
      <h2 class="section__title">From first idea to lasting work.</h2>
      <p class="section__lede">The summit and portal support four practical stages of producing.</p>
    </div>

    <ol class="process__steps">
      <?php
      $steps = [
        ['Emerge',   'Name what you can produce. Define the problem, the skill and the people you intend to serve.'],
        ['Build',    'Make a working first version. Test it with real people and learn from what happens.'],
        ['Establish','Create the structure, systems, stewardship and capital that allow good work to last.'],
        ['Multiply', 'Teach the method, mentor others and reproduce what works in new people and places.'],
      ];
      foreach ($steps as [$name, $desc]): ?>
        <li class="step" data-reveal>
          <h3 class="step__title"><?= e($name) ?></h3>
          <p class="step__desc"><?= e($desc) ?></p>
        </li>
      <?php endforeach; ?>
    </ol>
  </div>
</section>

<!-- ============ PORTAL ============ -->
<section class="portal section section--paper-dark" id="portal">
  <div class="container portal__grid">
    <div class="portal__copy" data-reveal>
      <h2 class="section__title">A working library for producers.</h2>
      <p class="section__lede">
        Members receive practical knowledge, documented experience and direct routes to people and opportunities.
      </p>
      <a href="<?= url('/register') ?>?mode=initiative" class="btn btn--ink">
        <span class="btn__label">Register for portal access</span>
        <span class="btn__arrow" aria-hidden="true"><?= icon_arrow() ?></span>
      </a>
    </div>

    <ul class="portal__index" data-reveal>
      <?php
      $index = [
        ['Methods',      'Practical playbooks, session notes and tools for each stage of producing.'],
        ['Case studies', 'Documented producer journeys, decisions and lessons from different fields.'],
        ['Capital',      'Grant, investment and trade pathways explained in useful terms.'],
        ['Network',      'Find producers, mentoring, cohorts, showcases and current opportunities.'],
      ];
      foreach ($index as [$label, $desc]): ?>
        <li class="index__row">
          <span class="index__label"><?= e($label) ?></span>
          <span class="index__desc"><?= e($desc) ?></span>
        </li>
      <?php endforeach; ?>
    </ul>
  </div>
</section>

<!-- ============ FAQ ============ -->
<section class="faq section" id="faq">
  <div class="container faq__grid">
    <div class="faq__head" data-reveal>
      <h2 class="section__title">Questions before you register.</h2>
    </div>

    <div class="faq__list" data-reveal>
      <?php
      $faqs = [
        ['Who is the summit for?', 'Anyone who wants to move from consuming to producing: founders, creatives, engineers, students, teachers, ministers and professionals of every age and field. You do not need a business yet.'],
        ['When exactly is it?', 'The London date is being finalised and will be announced to registered people first. Register now and you will hear before anyone else.'],
        ['Is there a cost to attend?', 'Details of any fee will be shared with the date. Registering your intention now costs nothing and does not commit you.'],
        ['I cannot travel to London. Can I still take part?', 'Yes. Choose "Attend online" to join the live stream from Rainham, Essex and receive the programme and recordings, or "Join the initiative" to become a member with portal access.'],
        ['What is the Kingdom Producers portal?', 'A members-only portal containing methods, case studies, capital pathways, a producer directory, session recordings and opportunities. Members are notified as it opens.'],
        ['How is my data used?', 'Only to administer the summit and the initiative. We do not sell or share your details. You can ask us to remove them at any time.'],
      ];
      foreach ($faqs as $i => [$q, $a]): ?>
        <details class="faq__item" <?= $i === 0 ? 'open' : '' ?>>
          <summary class="faq__q">
            <span class="faq__text"><?= e($q) ?></span>
            <span class="faq__icon" aria-hidden="true"></span>
          </summary>
          <div class="faq__a"><p><?= e($a) ?></p></div>
        </details>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- ============ CTA ============ -->
<section class="cta section section--stamp" id="cta">
  <div class="container cta__inner">
    <div class="cta__copy" data-reveal>
      <p class="cta__status mono">Registration is open</p>
      <h2 class="cta__title">Start with what you can produce.</h2>
    </div>
    <div class="cta__actions" data-reveal>
      <p class="cta__text">Register for the London summit and take your next practical step.</p>
      <a href="<?= url('/register') ?>" class="btn btn--paper">
        <span class="btn__label">Register now</span>
        <span class="btn__arrow" aria-hidden="true"><?= icon_arrow() ?></span>
      </a>
      <span class="cta__note mono">Takes about three minutes.</span>
    </div>
  </div>
</section>
