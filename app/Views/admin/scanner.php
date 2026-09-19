<?php /** @var array $attendance */ ?>
<section class="adm-page adm-scan" data-access-scanner data-endpoint="<?= e(url('/admin/check-in')) ?>">
  <header class="adm-page__head adm-scan__head">
    <div>
      <p class="eyebrow"><span class="eyebrow__dot"></span>Attendance desk</p>
      <h1 class="adm-page__title">Confirm access</h1>
      <p class="adm-scan__intro">Scan an attendee’s pass. Each registration can be checked in once.</p>
    </div>
    <div class="adm-scan__count">
      <span class="mono">Checked in today</span>
      <strong data-attendance-count><?= number_format($attendance['today']) ?></strong>
    </div>
  </header>

  <div class="adm-scan__layout">
    <section class="adm-scan__camera" aria-labelledby="scanner-camera-title">
      <div class="adm-scan__camera-head">
        <div>
          <span class="mono">Live camera</span>
          <h2 id="scanner-camera-title">Place the QR inside the frame</h2>
        </div>
        <span class="adm-scan__live mono" data-scanner-live><i></i><b>Ready</b></span>
      </div>
      <div id="accessQrReader" class="adm-scan__reader"></div>
      <div class="adm-scan__controls">
        <button class="adm-btn adm-btn--solid" type="button" data-scanner-start>Allow camera access</button>
        <button class="adm-btn adm-btn--dark" type="button" data-scanner-stop disabled>Stop</button>
        <label class="adm-btn adm-btn--upload">
          Scan photo
          <input type="file" accept="image/*" capture="environment" data-scanner-file>
        </label>
      </div>
      <p class="adm-scan__help mono" data-scanner-help>Allow camera access when your browser asks.</p>
      <a class="adm-scan__external mono" href="<?= e(url('/admin/scanner')) ?>" target="_blank" rel="noopener">Open camera desk in a new tab ↗</a>
    </section>

    <aside class="adm-scan__desk">
      <div class="adm-scan__result" data-scan-result data-state="idle" aria-live="polite">
        <span class="adm-scan__result-mark" aria-hidden="true">01</span>
        <p class="mono" data-result-label>Waiting for a pass</p>
        <h2 data-result-name>No attendee scanned</h2>
        <dl>
          <div><dt class="mono">Reference</dt><dd data-result-reference>—</dd></div>
          <div><dt class="mono">Path</dt><dd data-result-path>—</dd></div>
          <div><dt class="mono">Time</dt><dd data-result-time>—</dd></div>
        </dl>
      </div>

      <form class="adm-scan__manual" data-manual-checkin>
        <?= csrf_field() ?>
        <label for="manualAccessCode">Enter a reference instead</label>
        <div>
          <input id="manualAccessCode" name="token" type="text" autocomplete="off" placeholder="KPS26-XXXXXX" maxlength="512">
          <button class="adm-btn adm-btn--dark" type="submit">Confirm</button>
        </div>
        <p class="mono">Use this when a camera is unavailable.</p>
      </form>

      <form class="adm-scan__manual" data-name-search data-search-url="<?= url('/admin/check-in/search') ?>" onsubmit="return false">
        <label for="scanNameSearch">Or find them by name</label>
        <div>
          <input id="scanNameSearch" type="search" autocomplete="off" placeholder="Surname, first name, email or reference" maxlength="80">
        </div>
        <ul data-search-results style="list-style:none;margin:.6rem 0 0;padding:0;display:grid;gap:.4rem"></ul>
      </form>
    </aside>
  </div>
</section>

<dialog data-scan-modal style="width:min(92vw,28rem);padding:0;border:0;background:transparent">
  <div data-modal-card style="padding:2rem 1.6rem;text-align:center;color:var(--paper);background:var(--stamp)">
    <div data-modal-mark style="font-size:4rem;line-height:1">✓</div>
    <p class="mono" data-modal-label style="margin:.6rem 0 .3rem;text-transform:uppercase;letter-spacing:.08em;opacity:.8"></p>
    <h2 data-modal-name style="margin:0 0 1.2rem;font-size:1.8rem;line-height:1.05"></h2>
    <button type="button" class="adm-btn adm-btn--solid" data-modal-close autofocus>OK</button>
  </div>
</dialog>
<style>
  dialog[data-scan-modal]::backdrop { background: rgba(0,0,0,.55); }
  dialog[data-scan-modal][data-state="duplicate"] [data-modal-card] { background: #9a6b10; }
  dialog[data-scan-modal][data-state="invalid"] [data-modal-card] { background: #602128; }
  [data-search-results] li { display: flex; justify-content: space-between; align-items: center; gap: .6rem; padding: .5rem .6rem; border: 1px solid rgba(27,34,66,.2); background: #fff; }
  [data-search-results] li small { display: block; opacity: .7; }
</style>
<script src="<?= asset('js/html5-qrcode.min.js') ?>" onerror="window.__qrLibFailed = true"></script>
<script>
(function () {
  var root = document.querySelector('[data-access-scanner]');
  if (!root) return;

  var reader = null;
  var locked = false;
  var start = root.querySelector('[data-scanner-start]');
  var stop = root.querySelector('[data-scanner-stop]');
  var file = root.querySelector('[data-scanner-file]');
  var help = root.querySelector('[data-scanner-help]');
  var manual = root.querySelector('[data-manual-checkin]');
  var result = root.querySelector('[data-scan-result]');
  var live = root.querySelector('[data-scanner-live] b');
  var csrf = manual.querySelector('[name="_token"]').value;

  if (navigator.permissions && navigator.permissions.query) {
    navigator.permissions.query({name: 'camera'}).then(function (permission) {
      var reflectPermission = function () {
        if (permission.state === 'denied') {
          live.textContent = 'Blocked';
          help.textContent = 'Camera is blocked for this site. Open the browser site settings, set Camera to Allow, then reload this page.';
        } else if (permission.state === 'granted') {
          start.textContent = 'Start camera';
          help.textContent = 'Camera permission is ready. Press Start camera.';
        } else {
          start.textContent = 'Allow camera access';
          help.textContent = 'Press the button; your browser will ask to use the camera.';
        }
      };
      reflectPermission();
      permission.addEventListener('change', reflectPermission);
    }).catch(function () {});
  }

  function paint(data) {
    result.dataset.state = data.status || 'invalid';
    root.querySelector('[data-result-label]').textContent = data.message || 'Unable to confirm access.';
    root.querySelector('[data-result-name]').textContent = data.name || 'Pass not accepted';
    root.querySelector('[data-result-reference]').textContent = data.reference || '—';
    root.querySelector('[data-result-path]').textContent = data.participation || '—';
    root.querySelector('[data-result-time]').textContent = data.checked_in_at || '—';
    if (data.status === 'checked_in') {
      var count = root.querySelector('[data-attendance-count]');
      count.textContent = String((parseInt(count.textContent.replace(/,/g, ''), 10) || 0) + 1);
    }
    showModal(data);
  }

  // A big, unmissable verdict after every scan. Closes itself after a moment or on tap.
  var modal = document.querySelector('[data-scan-modal]'), modalTimer = null;
  function showModal(data) {
    if (!modal || typeof modal.showModal !== 'function') return;
    var state = data.status || 'invalid';
    modal.dataset.state = state;
    modal.querySelector('[data-modal-mark]').textContent = state === 'checked_in' ? '✓' : state === 'duplicate' ? '↺' : '✕';
    modal.querySelector('[data-modal-label]').textContent = data.message || 'Unable to confirm access.';
    modal.querySelector('[data-modal-name]').textContent = data.name || 'Pass not accepted';
    if (!modal.open) modal.showModal();
    window.clearTimeout(modalTimer);
    modalTimer = window.setTimeout(function () { if (modal.open) modal.close(); }, state === 'checked_in' ? 2500 : 4000);
  }
  if (modal) {
    modal.querySelector('[data-modal-close]').addEventListener('click', function () { modal.close(); });
    modal.addEventListener('click', function (e) { if (e.target === modal) modal.close(); });
  }

  // Name search: type, pick, check in.
  var search = root.querySelector('[data-name-search]');
  if (search) {
    var box = search.querySelector('input'), list = search.querySelector('[data-search-results]'), searchTimer = null, seq = 0;
    function esc(t) { return String(t).replace(/[&<>"']/g, function (c) { return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]; }); }
    function render(people) {
      list.innerHTML = people.length ? people.map(function (p) {
        return '<li><span>' + esc(p.name) + '<small class="mono">' + esc(p.reference) + ' · ' + esc(p.participation) + (p.checked_in_at ? ' · in at ' + esc(p.checked_in_at) : '') + '</small></span>'
          + (p.checked_in_at ? '<span class="mono" style="font-size:.7rem;opacity:.7">Checked in</span>'
             : '<button type="button" class="adm-btn adm-btn--dark" style="padding:.25rem .6rem;font-size:.75rem" data-ref="' + esc(p.reference) + '">Check in</button>') + '</li>';
      }).join('') : (box.value.trim().length >= 2 ? '<li><span class="mono" style="opacity:.7">No one matches.</span></li>' : '');
    }
    box.addEventListener('input', function () {
      window.clearTimeout(searchTimer);
      var q = box.value.trim(); if (q.length < 2) { list.innerHTML = ''; return; }
      searchTimer = window.setTimeout(function () {
        var mine = ++seq;
        fetch(search.dataset.searchUrl + '?q=' + encodeURIComponent(q), {headers: {Accept: 'application/json'}})
          .then(function (r) { return r.json(); })
          .then(function (d) { if (mine === seq) render(d.people || []); })
          .catch(function () {});
      }, 250);
    });
    list.addEventListener('click', function (e) {
      var b = e.target.closest('button[data-ref]'); if (!b) return;
      b.disabled = true;
      submitCode(b.dataset.ref);
      window.setTimeout(function () { box.dispatchEvent(new Event('input')); }, 1600);
    });
  }

  function cameraFailure(error) {
    var name = error && error.name ? error.name : '';
    var raw = String(error && error.message ? error.message : error || '') + ' ' + String(error || '');
    var denied = name === 'NotAllowedError' || /permission|denied|notallowed/i.test(raw);
    var missing = name === 'NotFoundError' || /not found|no camera/i.test(raw);
    var busy = name === 'NotReadableError' || /could not start|track start|notreadable/i.test(raw);
    var message = 'The camera could not start. You can scan a photo or enter the reference.';
    var generic = /failed to load/i.test(raw);
    if (generic) message = String(error.message);
    if (denied) message = 'Camera permission is blocked. Allow camera access in your browser settings, then try again.';
    if (missing) message = 'No camera was found on this device. Scan a photo or enter the reference.';
    if (busy) message = 'The camera is being used by another app. Close it there, then try again.';
    help.textContent = message;
    paint({status: 'invalid', message: message});
  }

  function submitCode(code) {
    if (locked) return;
    locked = true;
    result.dataset.state = 'loading';
    live.textContent = 'Validating';
    root.querySelector('[data-result-label]').textContent = 'Checking pass…';

    fetch(root.dataset.endpoint, {
      method: 'POST',
      headers: {'Accept': 'application/json', 'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8', 'X-Requested-With': 'XMLHttpRequest'},
      body: new URLSearchParams({_token: csrf, token: code}).toString()
    }).then(function (response) {
      return response.json().then(function (data) { return {ok: response.ok, data: data}; });
    }).then(function (payload) {
      paint(payload.data);
    }).catch(function () {
      paint({status: 'invalid', message: 'The attendance desk could not be reached.'});
    }).finally(function () {
      live.textContent = reader && start.disabled ? 'Scanning' : 'Ready';
      window.setTimeout(function () { locked = false; }, 1400);
    });
  }

  // On a slow mobile connection the library can still be downloading when the button is tapped.
  function waitForLibrary() {
    return new Promise(function (resolve, reject) {
      var waited = 0;
      (function tick() {
        if (window.Html5Qrcode) return resolve();
        if (window.__qrLibFailed || waited >= 30000) return reject(new Error('Camera scanner failed to load. Check your connection and reload the page, or scan a photo.'));
        start.textContent = 'Loading scanner…';
        waited += 300;
        window.setTimeout(tick, 300);
      })();
    });
  }

  start.addEventListener('click', function () {
    start.dataset.label = start.textContent;
    start.textContent = 'Requesting camera';
    live.textContent = 'Connecting';
    root.classList.add('is-connecting');
    if (!window.isSecureContext || !navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
      cameraFailure(new Error('Camera access requires HTTPS or localhost.'));
      start.textContent = start.dataset.label || 'Start camera';
      root.classList.remove('is-connecting');
      return;
    }

    // This direct browser API call is intentional: it guarantees that the
    // permission prompt is tied to the user's click before the QR library starts.
    var deviceId = '';
    navigator.mediaDevices.getUserMedia({video: {facingMode: {ideal: 'environment'}}, audio: false})
    .then(function (permissionStream) {
      var track = permissionStream.getVideoTracks()[0];
      deviceId = track && track.getSettings ? track.getSettings().deviceId : '';
      permissionStream.getTracks().forEach(function (item) { item.stop(); });
      return waitForLibrary();
    }).then(function () {
      reader = reader || new Html5Qrcode('accessQrReader');
      return reader.start(
        deviceId || {facingMode: {ideal: 'environment'}},
        {fps: 10, qrbox: function (w, h) { var size = Math.floor(Math.min(w, h) * .68); return {width: size, height: size}; }},
        submitCode,
        function () {}
      );
    }).then(function () {
      start.disabled = true;
      stop.disabled = false;
      start.textContent = 'Camera active';
      live.textContent = 'Scanning';
      help.textContent = 'Camera active. Hold the pass steady inside the frame.';
      root.classList.remove('is-connecting');
      root.classList.add('is-scanning');
    }).catch(function (error) {
      start.textContent = 'Start camera';
      live.textContent = 'Unavailable';
      root.classList.remove('is-connecting');
      cameraFailure(error);
    });
  });

  stop.addEventListener('click', function () {
    if (!reader) return;
    reader.stop().then(function () {
      start.disabled = false;
      stop.disabled = true;
      start.textContent = start.dataset.label || 'Start camera';
      live.textContent = 'Ready';
      root.classList.remove('is-scanning');
    });
  });

  manual.addEventListener('submit', function (event) {
    event.preventDefault();
    var input = manual.querySelector('[name="token"]');
    if (input.value.trim()) submitCode(input.value.trim());
  });

  file.addEventListener('change', function () {
    var selected = file.files && file.files[0];
    if (!selected || !window.Html5Qrcode) return;
    var scan = function () {
      reader = reader || new Html5Qrcode('accessQrReader');
      live.textContent = 'Reading photo';
      help.textContent = 'Reading QR code from the selected image.';
      return reader.scanFile(selected, true).then(function (decodedText) {
        submitCode(decodedText);
        help.textContent = 'QR code found. Confirming access.';
      }).catch(function () {
        live.textContent = 'Ready';
        help.textContent = 'No QR code was found in that photo. Try a closer, sharper image.';
        paint({status: 'invalid', message: 'No QR code was found in that photo.'});
      }).finally(function () { file.value = ''; });
    };
    if (reader && start.disabled) {
      reader.stop().then(function () {
        start.disabled = false;
        stop.disabled = true;
        root.classList.remove('is-scanning');
        return scan();
      });
    } else {
      scan();
    }
  });

  window.addEventListener('pagehide', function () {
    if (reader && start.disabled) reader.stop().catch(function () {});
  });
})();
</script>
