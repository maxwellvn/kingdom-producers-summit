<?php
$current = \App\Core\Url::currentPath();
$chooser = \App\Core\Events::isChooser($current);
$other = \App\Core\Events::active() === 'ireland' ? 'manchester' : 'ireland';
$crest = is_file(BASE_PATH . '/public/assets/img/crest.png') ? asset('img/crest.png') : null;
?>
<header class="nav" id="nav">
  <div class="nav__inner container">
    <a href="<?= e(\App\Core\Url::base() . '/') ?>" class="brand" aria-label="Loveworld Kingdom Producers Summit — choose your city">
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

    <?php if ($chooser): ?>
    <span class="nav__edition"><i aria-hidden="true"></i>Manchester and Ireland</span>
    <?php else: ?>
    <nav class="nav__links" aria-label="Main">
      <a class="nav__link" href="<?= url('/') ?>#summit">The Summit</a>
      <a class="nav__link" href="<?= url('/') ?>#pathways">Ways to join</a>
      <a class="nav__link<?= $current === '/about' ? ' is-active' : '' ?>" href="<?= url('/about') ?>">The Initiative</a>
      <a class="nav__link<?= str_starts_with($current, '/sponsor') ? ' is-active' : '' ?>" href="<?= url('/sponsor') ?>">Sponsor</a>
    </nav>
    <nav class="city-switch" aria-label="Choose event" data-city-switch>
      <span class="city-switch__thumb" aria-hidden="true"></span>
      <?php foreach (['manchester' => 'Manchester', 'ireland' => 'Ireland'] as $slug => $label): $on = \App\Core\Events::active() === $slug; ?>
        <a class="city-switch__opt<?= $on ? ' is-active' : '' ?>" data-city="<?= e($slug) ?>" href="<?= e(\App\Core\Url::base() . '/' . $slug . '/') ?>"<?= $on ? ' aria-current="true"' : '' ?>><?= e($label) ?></a>
      <?php endforeach; ?>
    </nav>

    <div class="nav__cta">
      <a href="<?= url('/register') ?>" class="btn btn--ink <?= str_starts_with($current, '/register') ? 'is-active' : '' ?>">
        <span class="btn__label">Register</span>
        <span class="btn__arrow" aria-hidden="true"><?= icon_arrow() ?></span>
      </a>
    </div>

    <button class="nav__burger" id="burger" aria-label="Open menu" aria-expanded="false" aria-controls="mobileMenu">
      <span></span><span></span>
    </button>
    <?php endif; ?>
  </div>
</header>

<?php if (!$chooser): ?>
<div class="mobile-menu" id="mobileMenu" aria-hidden="true">
  <div class="mobile-menu__inner">
    <div class="mobile-menu__meta mono">
      <span>Navigate</span>
      <span><?= e(config('app.summit.edition')) ?></span>
    </div>
    <a class="mobile-menu__switch" href="<?= e(\App\Core\Url::base() . '/') ?>">
      <span class="mono">You are viewing <?= e((string) config('app.summit.place')) ?></span>
      <span class="mobile-menu__switch-go">Change city <span aria-hidden="true">→</span></span>
    </a>
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
<?php endif; ?>
