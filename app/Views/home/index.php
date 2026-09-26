<?php
/**
 * @var array $summit
 */
?>

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
        <em class="produce-trigger">produce</em>.
      </p>

      <dl class="hero__date" aria-label="Summit date, time and place">
        <div><dt class="mono">Date</dt><dd><?= e($summit['date_day']) ?></dd></div>
        <?php if ($summit['time'] !== ''): ?><div><dt class="mono">Time</dt><dd><?= e($summit['time']) ?></dd></div><?php endif; ?>
        <div><dt class="mono">Place</dt><dd><?= e($summit['city']) ?></dd></div>
      </dl>
    </div>

    <div class="hero__collage">
      <figure class="poster">
        <img class="poster__crest" src="<?= e(asset('img/crest.png')) ?>" alt="Loveworld Consulate United Kingdom crest" width="1040" height="764">
        <span class="poster__edition"><?= e(trim((string) preg_replace('/\s*Edition.*$/i', '', (string) $summit['edition'])) ?: $summit['place']) ?><br>Edition</span>
        <span class="poster__rule" aria-hidden="true"></span>
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

<!-- ============ STATEMENT ============ -->
<section class="statement section" id="summit">
  <div class="container statement__grid">
    <h2 class="statement__title" data-split>
      A gathering for people ready to build.
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
        For one afternoon in <?= e($summit['place']) ?>, producers at every stage share methods,
        capital pathways and case studies in one room — then leave with a practical
        plan for what they will build next.
      </p>
    </div>
  </div>
</section>

<!-- ============ PATHWAYS ============ -->
<section class="pathways section section--ink" id="pathways">
  <div class="container">
    <div class="section__head" data-reveal>
      <h2 class="section__title">The work happens in the room.</h2>
      <p class="section__lede">The room is in <?= e($summit['place']) ?>. The livestream carries the main sessions only. Everything you take part in yourself, the workshops, the mentoring, the clinic and the networking, happens in the room.</p>
    </div>

    <div class="pathways__grid">
      <article class="path" data-reveal data-reveal-delay="0">
        <div class="path__head">
          <h3 class="path__title">
            Attend in <?= e($summit['place']) ?>
            <span class="path__meta mono">In the room</span>
          </h3>
          <p class="path__cost">
            <span class="mono">Your place is confirmed on registration</span>
          </p>
        </div>
        <div class="path__copy">
          <p>You are worked with, not spoken to. You leave with a plan for what you will build next and the people to build it with.</p>
          <ul class="path__list path__list--yes">
            <li>Onsite-only masterclasses and hands-on workshops</li>
            <li>Live mentoring, the idea and business clinic, and a pitch slot for selected delegates</li>
            <li>Lunch, networking and the opportunity and resource hub</li>
            <li>Resource pack, certificate of participation and priority access to what comes next</li>
          </ul>
        </div>
        <a href="<?= url('/register') ?>?mode=onsite" class="path__cta path__cta--onsite">Register to attend onsite <span class="path__cta-icon" aria-hidden="true"><?= icon_arrow() ?></span></a>
      </article>

      <article class="path" data-reveal data-reveal-delay="0.1">
        <div class="path__head">
          <h3 class="path__title">
            Attend online
            <span class="path__meta mono">Unlimited places</span>
          </h3>
          <p class="path__cost">
            <span class="mono">Your place is confirmed on registration</span>
          </p>
        </div>
        <div class="path__copy">
          <p>A livestream of the main sessions only. You can follow the day from anywhere, but you take no part in the room. Choose this if you cannot travel to <?= e($summit['place']) ?>.</p>
          <ul class="path__list path__list--no">
            <li>1-to-1 facilitator engagement</li>
            <li>Working rooms and feedback on your suggestions</li>
          </ul>
        </div>
        <a href="<?= url('/register') ?>?mode=online" class="path__cta path__cta--online">Register to attend online <span class="path__cta-icon" aria-hidden="true"><?= icon_arrow() ?></span></a>
      </article>
    </div>
  </div>
</section>

<!-- ============ ONSITE DELEGATE BENEFITS ============ -->
<section class="perks section" id="onsite-benefits">
  <div class="container">
    <div class="section__head" data-reveal>
      <p class="eyebrow"><span class="eyebrow__dot"></span>Onsite delegates only</p>
      <h2 class="section__title">What a place gets you.</h2>
      <p class="section__lede">Everything below happens in the room in <?= e($summit['place']) ?>. None of it is carried on the livestream.</p>
    </div>

    <div class="perks__grid">
      <?php
      $perks = [
        ['Exclusive masterclasses', 'Practical, onsite-only sessions on entrepreneurship, monetising your skills, intellectual property, branding, technology, funding and scaling.'],
        ['Live mentoring and Q&A', 'Put your own situation to speakers, facilitators and industry professionals, and get an answer you can act on.'],
        ['Hands-on workshops', 'Work in small groups on business models, brands, product concepts and a plan you leave with.'],
        ['Idea and business clinic', 'Bring a skill, an idea, a project or a running business and get specific guidance on the next step.'],
        ['Pitch opportunity', 'Selected delegates pitch their idea or business for recognition, mentorship and potential support.'],
        ['Lunch and networking', 'Eat with entrepreneurs, mentors, professionals, collaborators and other Kingdom Producers.'],
        ['Opportunity and resource hub', 'Business, career, training, volunteering and partnership opportunities, gathered in one place.'],
        ['Exclusive resource pack', 'Templates, guides and working materials given only to onsite delegates.'],
        ['Certificate of participation', 'An official Kingdom Producers Summit certificate in your name.'],
        ['“My Opportunity” corner', 'Record your 90-day vision and commitment at the video booth. Your recording is sent to you afterwards as a reminder to act on it. “Write the vision, and make it plain…” Habakkuk 2:2 (KJV).'],
        ['Exclusive meet and greet', 'Selected opportunities to meet the speakers and facilitators in person.'],
        ['Post-summit community and priority access', 'Join the Kingdom Producers network, and get early consideration for future masterclasses, mentorships, internships, projects and business opportunities.'],
      ];
      foreach ($perks as $i => [$name, $desc]): ?>
        <article class="perk" data-reveal>
          <span class="perk__num mono"><?= str_pad((string) ($i + 1), 2, '0', STR_PAD_LEFT) ?></span>
          <h3 class="perk__title"><?= e($name) ?></h3>
          <p class="perk__desc"><?= e($desc) ?></p>
        </article>
      <?php endforeach; ?>
    </div>

    <div class="perks__foot" data-reveal>
      <p class="mono">Your place is confirmed on registration</p>
      <a href="<?= url('/register') ?>?mode=onsite" class="btn btn--ink"><span class="btn__label">Register to attend onsite</span><span class="btn__arrow" aria-hidden="true"><?= icon_arrow() ?></span></a>
    </div>
  </div>
</section>

<!-- ============ INITIATIVE ============ -->
<section class="initiative section" id="initiative">
  <div class="container initiative__grid">
    <div class="initiative__intro" data-reveal>
      <p class="eyebrow"><span class="eyebrow__dot"></span>100 producers per edition</p>
      <h2 class="section__title">The initiative</h2>
      <p class="section__lede">The summit is one afternoon. The initiative is what follows it: a structured 30, 60 and 90 day production journey that takes you from working out what you can produce, to developing and testing it, to launching something real.</p>
      <p>That might be a product, a service, a solution, an enterprise or a piece of intellectual property. Everyone in the United Kingdom is welcome to join, whether or not you come to <?= e($summit['place']) ?>.</p>
      <div class="initiative__actions">
        <a href="<?= url('/register') ?>?mode=initiative" class="btn btn--ink"><span class="btn__label">Join the initiative</span><span class="btn__arrow" aria-hidden="true"><?= icon_arrow() ?></span></a>
        <a href="<?= url('/about') ?>" class="initiative__more">Read the full initiative <span aria-hidden="true"><?= icon_arrow() ?></span></a>
      </div>
    </div>
    <div class="initiative__benefits" data-reveal>
      <h3>What you get from it</h3>
      <ul>
        <li>Work out what you can produce from the skills, ideas and opportunities you already have</li>
        <li>Learn the practical ground: business, entrepreneurship, technology, innovation and intellectual property</li>
        <li>Mentorship and expert guidance, plus a network of producers and professionals</li>
        <li>Build and run your own 30, 60 and 90 day producers plan</li>
        <li>Showcase, pitch, collaborate, reach markets and grow what you produce</li>
        <li>Stay in the Kingdom Producers network instead of leaving after a one-day event</li>
      </ul>
    </div>
  </div>
</section>

<!-- ============ PROCESS ============ -->
<section class="process section" id="process">
  <div class="container">
    <div class="section__head process__head" data-reveal>
      <h2 class="section__title">From first idea to lasting work.</h2>
      <p class="section__lede">Start from the option that best describes where you are today.</p>
    </div>

    <ol class="process__steps">
      <?php
      $steps = [
        ['Nothing yet', 'You do not yet have a clear idea, product, service, project, business or creative work. Start here and discover what you can produce.'],
        ['Getting started', 'You have an idea or have started something, and want help deciding and taking the next practical steps.'],
        ['Growing', 'You already have something active and want to improve it, reach more people or make it sustainable.'],
        ['Scaling', 'What you do is established and you want to scale it or extend its reach.'],
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

<!-- ============ FAQ ============ -->
<section class="venue section" id="getting-there">
  <div class="container venue__grid">
    <div class="venue__copy" data-reveal>
      <p class="eyebrow"><span class="eyebrow__dot"></span>Getting there</p>
      <h2 class="section__title">Find us in <?= e($summit['place']) ?>.</h2>
      <?php $v = (array) $summit['venue']; $mapQuery = rawurlencode((string) $v['query']); $hasVenue = App\Services\Edition::hasVenue(); ?>
      <?php if ($hasVenue): ?>
      <address class="venue__address">
        <strong><?= e($v['name']) ?></strong><br>
        <?php if (trim($v['unit'] . $v['street']) !== ''): ?><?= e(implode(', ', array_filter([$v['unit'], $v['street']]))) ?><br><?php endif; ?>
        <?= e(implode(', ', array_filter([$v['town'], $v['region']]))) ?> <span class="mono"><?= e($v['postcode']) ?></span>
      </address>
      <div class="venue__maps">
        <a class="btn btn--ink" href="https://www.google.com/maps/dir/?api=1&destination=<?= $mapQuery ?>" target="_blank" rel="noopener"><span class="btn__label">Directions on Google Maps</span><span class="btn__arrow" aria-hidden="true"><?= icon_arrow() ?></span></a>
        <a class="btn btn--outline" href="https://maps.apple.com/?daddr=<?= $mapQuery ?>" target="_blank" rel="noopener"><span class="btn__label">Apple Maps</span></a>
        <a class="btn btn--outline" href="https://citymapper.com/directions?endaddress=<?= $mapQuery ?>" target="_blank" rel="noopener"><span class="btn__label">Citymapper</span></a>
      </div>
      <?php else: ?>
      <div class="venue__tba">
        <span class="venue__tba-icon"><?= ph('map-pin-area') ?></span>
        <div>
          <strong>Venue to be announced</strong>
          <span><?= e($summit['city']) ?></span>
        </div>
      </div>
      <p class="venue__tba-note">Register now and we will email you the venue, directions and travel notes as soon as they are confirmed.</p>
      <?php endif; ?>
      <p class="venue__everyone"><strong>The summit is for everyone.</strong> Whatever your age, your field or how far along you are, if you want to move from consuming to producing, this day is for you. Come as you are.</p>
    </div>

    <dl class="venue__how" data-reveal>
      <?php if (!$hasVenue): ?>
      <div class="venue__how-lead">
        <dt><?= ph('envelope-simple') ?> First to know</dt>
        <dd>Everyone registered hears the venue, date and travel notes first, by email and KingsChat.</dd>
        <a class="btn btn--ink" href="<?= url('/register') ?>"><span class="btn__label">Register now</span><span class="btn__arrow" aria-hidden="true"><?= icon_arrow() ?></span></a>
      </div>
      <?php endif; ?>
      <?php foreach (['train' => 'By train', 'bus' => 'By bus', 'car' => 'By car'] as $how => $label): ?>
        <?php if ($hasVenue && trim((string) ($summit['travel'][$how] ?? '')) !== ''): ?>
      <div>
        <dt class="mono"><?= $label ?></dt>
        <dd><?= e($summit['travel'][$how]) ?><?= $how === 'car' && $v['postcode'] !== '' ? ' Use the postcode ' . e($v['postcode']) . ' for satnav.' : '' ?></dd>
      </div>
        <?php endif; ?>
      <?php endforeach; ?>
      <div>
        <dt class="mono">Arriving</dt>
        <dd><?= App\Services\Edition::hasDate() ? (App\Services\Edition::showsTime() ? 'Doors open before the ' . e($summit['date_text']) . ' start.' : 'We look forward to seeing you on ' . e($summit['date_day']) . '.') : 'The date and start time will be announced by email.' ?> Bring your QR pass on your phone or printed, and give your name at the desk if you have neither.</dd>
      </div>
    </dl>
  </div>
</section>

<section class="faq section" id="faq">
  <div class="container faq__grid">
    <div class="faq__head" data-reveal>
      <h2 class="section__title">Questions before you register.</h2>
    </div>

    <div class="faq__list" data-reveal>
      <?php
      $faqs = [
        ['Who is the summit for?', 'Everyone. Anyone who wants to move from consuming to producing: founders, creatives, engineers, students, teachers, ministers and professionals of every age and field. You do not need a business yet, and you do not need to be a member of anything.'],
        ['Where exactly is the venue?', App\Services\Edition::hasVenue() ? venue_line() . '. See Getting there above for directions and one-tap map links.' : 'The venue in ' . $summit['place'] . ' will be announced soon. Everyone registered hears first, by email.'],
        ['When exactly is it?', App\Services\Edition::hasDate() ? 'The summit is on ' . $summit['date_day'] . ($summit['time'] !== '' ? ' at ' . $summit['time'] : '') . ' in ' . $summit['city'] . '.' : 'The date will be announced soon. Everyone registered hears first, by email.'],
        ['Is there a cost to attend?', 'No. Your place is confirmed the moment you register. Those who wish to support the programme can do so after registering; it is entirely optional and does not affect your place.'],
        ['Should I attend onsite or online?', 'Attend onsite if you want to be worked with: the 1-to-1 facilitator engagement, working rooms, networking and product showcases only happen in the room, and only part of the programme is livestreamed. Choose online if travelling to ' . $summit['place'] . ' is genuinely not possible for you.'],
        ['I cannot travel to ' . $summit['place'] . '. Can I still take part?', 'Yes. Choose "Attend online" for the livestreamed sessions, or use the separate "Join the initiative" path and take part in the 30, 60 and 90 day production journey from wherever you are.'],
        ['What is the Kingdom Producers portal?', 'A portal for initiative participants containing methods, case studies, capital pathways, a producer directory, summit resources and opportunities. Participants are notified as it opens.'],
        ['What happens after the summit?', 'Each edition develops 100 young people into working producers through a 30, 60 and 90 day journey, supported by mentorship, networks, marketplace access and expert help. The aim is 1,000 producers across the United Kingdom and 10,000 globally.'],
        ['How is my data used?', 'Only to administer the summit and the initiative. We do not sell or share your details. You can ask us to remove them at any time.'],
      ];
      foreach ($faqs as $i => [$q, $a]): ?>
        <details class="faq__item" name="faq" <?= $i === 0 ? 'open' : '' ?>>
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
      <p class="cta__text">Register for the summit and take your next practical step.</p>
      <a href="<?= url('/register') ?>" class="btn btn--paper">
        <span class="btn__label">Register now</span>
        <span class="btn__arrow" aria-hidden="true"><?= icon_arrow() ?></span>
      </a>
      <span class="cta__note mono">Takes about three minutes.</span>
    </div>
  </div>
</section>
