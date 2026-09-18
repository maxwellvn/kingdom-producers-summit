<?php /** @var array $summit @var string $shareUrl @var string $shareText */ ?>
<section class="reg">
  <div class="container pay">
    <div class="pay__card" data-share data-url="<?= e($shareUrl) ?>" data-text="<?= e($shareText) ?>" data-title="<?= e($summit['short']) ?>">
      <p class="mono pay__kicker"><span class="pay__dot" aria-hidden="true"></span> Share the summit</p>
      <h1 class="pay__title">Bring someone with you.</h1>
      <p class="pay__lede">Registration is free, onsite and online. The link below is copied already; send it wherever you like.</p>
      <div class="sponsor__espees sponsor__espees--inline" style="align-self:stretch">
        <span class="mono">Registration link</span>
        <strong class="sponsor__code" id="shareLink" style="font-size:clamp(1.1rem,3vw,1.5rem);letter-spacing:0;text-transform:none;overflow-wrap:anywhere"><?= e($shareUrl) ?></strong>
        <div style="display:flex;gap:.6rem;flex-wrap:wrap">
          <button type="button" class="confirmed__copy mono" data-copy="#shareLink">Copy link</button>
          <button type="button" class="confirmed__copy mono" data-share-open hidden>Share…</button>
        </div>
      </div>
      <p class="form__fine mono" data-share-status aria-live="polite"></p>
      <p class="form__fine mono"><a href="<?= url('/') ?>">Back to the summit</a></p>
    </div>
  </div>
</section>
<script>
(function () {
  var card = document.querySelector('[data-share]');
  var status = card.querySelector('[data-share-status]');
  var openBtn = card.querySelector('[data-share-open]');
  var data = { title: card.dataset.title, text: card.dataset.text, url: card.dataset.url };
  var copy = function () {
    if (!navigator.clipboard) return Promise.reject();
    return navigator.clipboard.writeText(data.url);
  };
  var share = function () {
    return navigator.share(data).then(function () { status.textContent = 'Shared. Thank you.'; })
      .catch(function () { /* dismissed */ });
  };
  copy().then(function () { status.textContent = 'Link copied.'; }).catch(function () {});
  if (navigator.share) {
    openBtn.hidden = false;
    openBtn.addEventListener('click', share);
    // Browsers only open the share sheet from a tap, so the first tap anywhere on the card does it.
    var once = function (e) { card.removeEventListener('click', once); if (e.target.closest('button, a')) return; copy().catch(function () {}); share(); };
    card.addEventListener('click', once);
  }
})();
</script>
