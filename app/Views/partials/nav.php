<?php
$current = \App\Core\Url::currentPath();
$crest = is_file(BASE_PATH . '/public/assets/img/crest.png') ? asset('img/crest.png') : null;
?>
<header class="nav" id="nav">
  <div class="nav__inner container">
    <a href="<?= url('/') ?>" class="brand" aria-label="Loveworld Consulate United Kingdom — home">
      <?php if ($crest): ?>
        <img class="brand__crest" src="<?= $crest ?>" alt="" width="44" height="44">
      <?php else: ?>
        <span class="brand__mono" aria-hidden="true">
          <svg viewBox="0 0 44 44" width="44" height="44" fill="none">
            <path d="M22 41s-16-9.6-16-22.4A9 9 0 0 1 22 12a9 9 0 0 1 16 6.6C38 31.4 22 41 22 41Z" stroke="currentColor" stroke-width="1.6"/>
            <path d="M22 12v29M6 18.6h32" stroke="currentColor" stroke-width="1.2" opacity=".55"/>
          </svg>
        </span>
      <?php endif; ?>
      <span class="brand__text">
        <span class="brand__line brand__line--top">The Loveworld Consulate</span>
        <span class="brand__line brand__line--bottom">United Kingdom</span>
      </span>
    </a>

    <div class="nav__cta">
      <a href="<?= url('/register') ?>" class="btn btn--ink <?= str_starts_with($current, '/register') ? 'is-active' : '' ?>">
        <span class="btn__label">Register</span>
        <span class="btn__arrow" aria-hidden="true"><?= icon_arrow() ?></span>
      </a>
    </div>

    <button class="nav__burger" id="burger" aria-label="Open menu" aria-expanded="false" aria-controls="mobileMenu">
      <span></span><span></span>
    </button>
  </div>
</header>

<div class="mobile-menu" id="mobileMenu" aria-hidden="true">
  <div class="mobile-menu__inner">
    <div class="mobile-menu__meta mono">
      <span>Navigate</span>
      <span><?= e(config('app.summit.edition')) ?></span>
    </div>
    <nav class="mobile-menu__links" aria-label="Mobile navigation">
      <a href="<?= url('/') ?>#summit" class="mobile-menu__link">The Summit</a>
      <a href="<?= url('/') ?>#pathways" class="mobile-menu__link">Ways to Join</a>
      <a href="<?= url('/about') ?>" class="mobile-menu__link">The Initiative</a>
      <a href="<?= url('/sponsor') ?>" class="mobile-menu__link">Sponsor</a>
    </nav>
    <div class="mobile-menu__action">
      <p class="mono">Registration is open</p>
      <a href="<?= url('/register') ?>" class="btn btn--ink">
        <span class="btn__label">Register now</span>
        <span class="btn__arrow" aria-hidden="true"><?= icon_arrow('icon-arrow icon-arrow--menu') ?></span>
      </a>
    </div>
  </div>
</div>
