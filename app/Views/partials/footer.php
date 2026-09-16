<?php $summit = $summit ?? config('app.summit'); ?>
<footer class="footer" id="footer">
  <section class="container footer__newsletter" aria-labelledby="newsletter-title">
    <div class="footer__newsletter-copy">
      <span class="footer__kicker mono">Producer dispatches</span>
      <h2 id="newsletter-title">Follow what gets built next.</h2>
    </div>
    <form class="footer__newsletter-form" data-newsletter novalidate>
      <label class="sr-only" for="newsletter-email">Email address</label>
      <div class="footer__newsletter-field">
        <input id="newsletter-email" type="email" name="email" autocomplete="email" placeholder="Email address" required>
        <button class="btn btn--paper" type="submit"><span class="btn__label">Join updates</span><span class="btn__arrow" aria-hidden="true"><?= icon_arrow() ?></span></button>
      </div>
      <p class="footer__newsletter-note mono" data-newsletter-note>Occasional summit updates.</p>
    </form>
  </section>

  <div class="container footer__grid">
    <div class="footer__col footer__col--brand">
      <span class="footer__kicker mono"><?= e(config('app.summit.edition')) ?></span>
      <p class="footer__office"><?= e($summit['office']) ?></p>
      <?php $fv = (array) config('app.summit.venue'); ?>
      <p class="footer__venue mono"><?= e($fv['unit']) ?>, <?= e($fv['name']) ?>, <?= e($fv['street']) ?>, <?= e($fv['town']) ?> <?= e($fv['postcode']) ?></p>
      <p class="footer__motto mono"><?= e($summit['motto']) ?><br>Working together for a stronger international community.</p>
    </div>

    <div class="footer__col">
      <h4 class="footer__heading mono">Summit</h4>
      <a href="<?= url('/') ?>#summit">About the summit</a>
      <a href="<?= url('/') ?>#pathways">Ways to join</a>
      <a href="<?= url('/') ?>#getting-there">Getting there</a>
      <a href="<?= url('/') ?>#faq">Questions</a>
      <a href="<?= url('/register') ?>">Register</a>
    </div>

    <div class="footer__col">
      <h4 class="footer__heading mono">Initiative</h4>
      <a href="<?= url('/about') ?>">Kingdom Producers</a>
      <a href="<?= url('/register') ?>?mode=initiative">Join the initiative</a>
    </div>

    <div class="footer__col">
      <h4 class="footer__heading mono">Contact</h4>
      <a href="mailto:<?= e(contact_email()) ?>"><?= e(contact_email()) ?></a>
      <a href="https://kingschat.online/user/<?= e(contact_kingschat()) ?>" target="_blank" rel="noopener">KingsChat @<?= e(contact_kingschat()) ?></a>
      <a href="<?= url('/privacy') ?>">Privacy notice</a>
    </div>
  </div>

  <div class="container footer__bottom">
    <span class="mono">© <?= date('Y') ?> <?= e($summit['organiser']) ?>. All rights reserved.</span>
    <a class="mono footer__made" href="https://movortech.com" target="_blank" rel="noopener noreferrer">Made by Movor</a>
  </div>

  <div class="footer__flag-wordmark" aria-hidden="true">Kingdom Producers</div>

  <div class="footer__flag-stage" aria-hidden="true">
    <video class="footer__flag-video" autoplay muted loop playsinline preload="metadata">
      <source src="<?= e(asset('media/union-jack-wind-loop-higgsfield-v1.mp4')) ?>" type="video/mp4">
    </video>
  </div>
</footer>
