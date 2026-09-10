<?php /** @var string $target */ ?>
<section class="adm-page">
  <header class="adm-page__head">
    <div>
      <p class="eyebrow"><span class="eyebrow__dot"></span>KingsChat</p>
      <h1 class="adm-page__title">Connecting <span class="adm-page__title-sub">KingsChat</span></h1>
    </div>
  </header>

  <p class="adm-muted" id="kcStatus">Finishing the connection…</p>

  <noscript>
    <p class="adm-muted">This step needs JavaScript. Enable it and try connecting again.</p>
  </noscript>
</section>

<script>
// KingsChat returns the tokens in the URL fragment, which never reaches the
// server. Read them here and hand them over as a normal query.
(function () {
  var fragment = window.location.hash.replace(/^#/, '');
  var status = document.getElementById('kcStatus');
  if (!fragment) {
    status.textContent = 'No authorisation details came back. Start the connection again.';
    return;
  }
  var params = new URLSearchParams(fragment);
  var accessToken = params.get('access_token');
  if (!accessToken) {
    status.textContent = 'KingsChat did not return an access token. Start the connection again.';
    return;
  }
  var query = new URLSearchParams({
    access_token: accessToken,
    refresh_token: params.get('refresh_token') || '',
    expires_in_millis: params.get('expires_in_millis') || ''
  });
  window.location.replace(<?= json_encode($target, JSON_UNESCAPED_SLASHES) ?> + '?' + query.toString());
})();
</script>
