<?php /** @var array $summit @var string $updated */ ?>
<section class="legal section">
  <div class="container legal__inner">
    <header class="legal__head" data-reveal>
      <p class="eyebrow"><span class="eyebrow__dot"></span>Privacy</p>
      <h1 class="legal__title">How we handle your details</h1>
      <p class="legal__lede">
        This notice covers the Loveworld Kingdom Producers Summit and the Kingdom Producers initiative.
        Last updated <?= e($updated) ?>.
      </p>
    </header>

    <div class="legal__body" data-reveal>
      <h2>Who is responsible</h2>
      <p>
        <?= e($summit['office']) ?> is the data controller for the information described here.
        Write to <a href="mailto:<?= e(contact_email()) ?>"><?= e(contact_email()) ?></a>
        or message <a href="https://kingschat.online/user/<?= e(contact_kingschat()) ?>" target="_blank" rel="noopener">@<?= e(contact_kingschat()) ?></a>
        with any question about your data.
      </p>

      <h2>What we collect, and why</h2>
      <p>When you register we ask for:</p>
      <ul>
        <li><strong>Your name and contact details</strong> — to confirm your place, send your reference and access pass, and reach you about the summit.</li>
        <li><strong>Your church, zone and group</strong> — so organisers know which part of the Loveworld community you belong to.</li>
        <li><strong>Your field, stage and interests</strong> — to shape the programme and put you with the right people.</li>
        <li><strong>Your age group</strong> — for planning and safeguarding.</li>
        <li><strong>Contributions</strong> — if you choose to support the programme we record the amount and how it was given. We never see or store card details: card payments happen on Stripe's or Revolut's own pages, and Espees in your Espees wallet.</li>
      </ul>
      <p>
        Onsite attendees may also tell us about <strong>dietary or access requirements</strong>. These can reveal
        health or religious beliefs, so we ask for them only if you choose to give them, we use them solely to make
        the day work for you, and you can leave them blank.
      </p>

      <h2>Our lawful basis</h2>
      <ul>
        <li><strong>Performing our agreement with you</strong> — administering your registration, any contribution you choose to make, and your access to the summit.</li>
        <li><strong>Legitimate interests</strong> — running the initiative, keeping the event secure and understanding how the site is used.</li>
        <li><strong>Your consent</strong> — for optional marketing about other programmes, and for dietary or access requirements. You may withdraw either at any time.</li>
      </ul>

      <h2>Who else sees it</h2>
      <p>
        We do not sell your details or share them for anyone else's marketing. They are handled by our own
        organisers, and by the services we use to run the event: our email provider, KingsChat where you give us a
        username, and Stripe or Revolut if you choose to contribute. Those providers act on our instructions and are based in the UK or the
        European Economic Area, or covered by approved safeguards where they are not.
      </p>

      <h2>How long we keep it</h2>
      <ul>
        <li><strong>Registrations</strong> — for the edition you registered for and two years afterwards, so we can support you through the initiative, then deleted.</li>
        <li><strong>Contribution records</strong> — six years, as tax law requires.</li>
        <li><strong>Website traffic</strong> — six months, and it never identifies you: see below.</li>
        <li><strong>Sign-in attempts</strong> — one day.</li>
      </ul>

      <h2>Website measurement</h2>
      <p>
        We count visits ourselves rather than using an outside analytics service. We do not store your address:
        it is turned into a one-way code with a value that changes daily, which lets us count a visitor once
        within a day and nothing more. Nothing is stored on your device for this, no profile is built, and
        nothing is shared. If you decline analytics in the cookie banner, we stop counting your visits entirely.
      </p>

      <h2>If you are under 18</h2>
      <p>
        You are welcome at the summit. If you are under 16, please register with a parent or guardian, and ask them
        to email us so we have their agreement on record. We will remove a young person's details on request from
        them or their parent.
      </p>

      <h2>Your rights</h2>
      <p>You can ask us to:</p>
      <ul>
        <li>give you a copy of what we hold about you;</li>
        <li>correct anything wrong;</li>
        <li>delete your details;</li>
        <li>stop using them for a particular purpose, or object to our using them at all;</li>
        <li>stop sending you marketing, at any time and without giving a reason.</li>
      </ul>
      <p>
        Email <a href="mailto:<?= e(contact_email()) ?>"><?= e(contact_email()) ?></a> with your registration
        reference and we will answer within one month. If you are unhappy with how we have handled it, you can
        complain to the Information Commissioner's Office at
        <a href="https://ico.org.uk/make-a-complaint/" target="_blank" rel="noopener">ico.org.uk</a>,
        or by calling 0303 123 1113.
      </p>

      <h2>Keeping it safe</h2>
      <p>
        The site runs over an encrypted connection, passwords are stored only as one-way hashes, access to
        registrations is limited to organisers who sign in, and your access pass is a signed code rather than
        your personal details.
      </p>
    </div>
  </div>
</section>
