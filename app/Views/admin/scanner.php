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
    </aside>
  </div>
</section>

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
