/* =====================================================================
   Producers Summit — motion & interactions
   GSAP for scroll reveals + text split; vanilla for nav, menu, form.
   Respects prefers-reduced-motion.
   ===================================================================== */
(function () {
  'use strict';

  var reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  document.documentElement.classList.add('js');

  /* ---------- Opening ident, cookie preferences + newsletter ---------- */
  var intro = document.getElementById('siteIntro');
  var cookieBanner = document.querySelector('[data-cookie-banner]');
  var introTimer = null;

  function storedCookieChoice() {
    try { return localStorage.getItem('producers_cookie_choice'); } catch (e) { return null; }
  }

  function showCookieBanner() {
    if (cookieBanner && !storedCookieChoice()) {
      cookieBanner.hidden = false;
      window.requestAnimationFrame(function () {
        window.requestAnimationFrame(function () {
          cookieBanner.classList.add('is-in');
        });
      });
    }
  }

  function closeIntro() {
    if (!intro || !document.documentElement.classList.contains('intro-pending')) {
      showCookieBanner();
      return;
    }
    window.clearTimeout(introTimer);
    intro.classList.add('is-leaving');
    window.setTimeout(function () {
      document.documentElement.classList.remove('intro-pending');
      intro.hidden = true;
      var player = intro.querySelector('video');
      if (player) player.pause();
      showCookieBanner();
    }, reduce ? 0 : 800);
  }

  if (intro && document.documentElement.classList.contains('intro-pending')) {
    var introPlayer = intro.querySelector('video');
    if (introPlayer) introPlayer.play().catch(function () {});
    introTimer = window.setTimeout(closeIntro, reduce ? 500 : 5900);
    // End with the clip rather than always sitting on the full timer.
    if (introPlayer) introPlayer.addEventListener('ended', closeIntro);
    var skipIntro = intro.querySelector('[data-intro-skip]');
    if (skipIntro) skipIntro.addEventListener('click', closeIntro);
  } else {
    if (intro) intro.hidden = true;
    showCookieBanner();
  }

  if (cookieBanner) {
    var customizeBtn = cookieBanner.querySelector('[data-cookie-customize]');
    var prefsPanel = cookieBanner.querySelector('.cookie-banner__prefs');

    function readToggles() {
      var prefs = { necessary: true };
      cookieBanner.querySelectorAll('[data-cookie-toggle]').forEach(function (input) {
        prefs[input.getAttribute('data-cookie-toggle')] = input.checked;
      });
      return prefs;
    }

    function dismissCookieBanner() {
      cookieBanner.classList.remove('is-in');
      cookieBanner.classList.add('is-out');
      window.setTimeout(function () { cookieBanner.hidden = true; }, reduce ? 0 : 420);
    }

    if (customizeBtn && prefsPanel) {
      customizeBtn.addEventListener('click', function () {
        var open = prefsPanel.classList.toggle('is-open');
        customizeBtn.setAttribute('aria-expanded', open ? 'true' : 'false');
      });
    }

    function saveConsent(prefs, action) {
      try { localStorage.setItem('producers_cookie_choice', JSON.stringify(prefs)); } catch (e) {}
      // Declining analytics must actually stop the counting, not just the banner.
      applyAnalyticsChoice(prefs.analytics !== false);
      var endpoint = cookieBanner.getAttribute('data-consent-endpoint');
      if (!endpoint) return;
      try {
        fetch(endpoint, {
          method: 'POST',
          headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
          body: JSON.stringify({
            action: action,
            preferences: !!prefs.preferences,
            analytics: !!prefs.analytics,
            marketing: !!prefs.marketing
          }),
          keepalive: true
        }).catch(function () {});
      } catch (e) {}
    }

    cookieBanner.querySelectorAll('[data-cookie-choice]').forEach(function (button) {
      button.addEventListener('click', function () {
        var choice = button.getAttribute('data-cookie-choice');
        var prefs;
        if (choice === 'accepted') {
          prefs = { necessary: true, preferences: true, analytics: true, marketing: true };
        } else if (choice === 'rejected') {
          prefs = { necessary: true, preferences: false, analytics: false, marketing: false };
        } else {
          prefs = readToggles();
        }
        saveConsent(prefs, choice);
        dismissCookieBanner();
      });
    });
  }

  var newsletter = document.querySelector('[data-newsletter]');
  if (newsletter) {
    newsletter.addEventListener('submit', function (event) {
      event.preventDefault();
      var email = newsletter.querySelector('input[type="email"]');
      var note = newsletter.querySelector('[data-newsletter-note]');
      if (!email || !email.checkValidity()) {
        if (email) email.reportValidity();
        return;
      }
      if (note) note.textContent = 'Opening your email app to complete signup.';
      window.location.href = 'mailto:unitedkingdom@loveworldconsulate.org?subject=' + encodeURIComponent('Producer dispatch signup') + '&body=' + encodeURIComponent('Please add ' + email.value + ' to the Producer Dispatches mailing list.');
    });
  }

  document.querySelectorAll('.footer__flag-video').forEach(function (video) {
    var startVideo = function () { video.play().catch(function () {}); };
    if (video.readyState >= 2) startVideo();
    else video.addEventListener('canplay', startVideo, { once: true });
    document.addEventListener('visibilitychange', function () { if (!document.hidden) startVideo(); });
  });

  /* ---------- A note for anyone registering under 18 ---------- */
  (function () {
    var note = document.querySelector('[data-under-18-note]');
    if (!note) return;
    var bands = Array.prototype.slice.call(document.querySelectorAll('input[name="age_band"]'));
    function sync() {
      var chosen = bands.filter(function (b) { return b.checked; })[0];
      note.hidden = !chosen || chosen.value !== 'under18';
    }
    bands.forEach(function (b) { b.addEventListener('change', sync); });
    sync();
  })();

  /* ---------- Honour an analytics refusal ---------- */
  function analyticsAllowed() {
    try {
      var stored = localStorage.getItem('producers_cookie_choice');
      if (!stored) return true; // Nothing declined yet.
      var prefs = JSON.parse(stored);
      return prefs.analytics !== false;
    } catch (e) { return true; }
  }

  function applyAnalyticsChoice(allowed) {
    document.documentElement.classList.toggle('no-analytics', !allowed);
    var url = document.body.getAttribute('data-analytics-choice');
    if (!url) return;
    fetch(url, {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: 'analytics=' + (allowed ? '1' : '0'),
      keepalive: true
    }).catch(function () {});
  }

  applyAnalyticsChoice(analyticsAllowed());

  /* ---------- Presence: keep the live counts honest ---------- */
  (function () {
    if (document.body.classList.contains('page-admin')) return;

    var context = document.body.getAttribute('data-presence') || 'site';
    var url = document.body.getAttribute('data-presence-url');
    if (!url || !analyticsAllowed()) return;

    function beat() {
      if (document.hidden) return;
      var body = new URLSearchParams({ path: location.pathname, context: context });
      fetch(url, { method: 'POST', body: body, keepalive: true }).catch(function () {});
    }

    beat();
    var timer = window.setInterval(beat, 30000);
    document.addEventListener('visibilitychange', function () { if (!document.hidden) beat(); });
    window.addEventListener('pagehide', function () {
      window.clearInterval(timer);
      var leave = document.body.getAttribute('data-presence-leave');
      if (leave && navigator.sendBeacon) navigator.sendBeacon(leave);
    });
  })();

  /* ---------- The stream page ---------- */
  (function () {
    var stage = document.querySelector('[data-watch]');
    if (!stage) return;

    var sourceUrl = stage.getAttribute('data-source-url');
    var beatUrl = stage.getAttribute('data-beat-url');
    var video = stage.querySelector('[data-watch-video]');
    var frame = stage.querySelector('[data-watch-frame]');
    var placeholder = stage.querySelector('[data-watch-placeholder]');
    var statusLabel = document.querySelector('[data-watch-status]');
    var loaded = false;
    var hls = null;

    // The holding screen: state, headline, message, countdown, now/next.
    var hold = {
      label: placeholder.querySelector('[data-holding-label]'),
      headline: placeholder.querySelector('[data-holding-headline]'),
      message: placeholder.querySelector('[data-holding-message]'),
      countdown: placeholder.querySelector('[data-holding-countdown]'),
      startsText: placeholder.querySelector('[data-holding-starts-text]'),
      now: placeholder.querySelector('[data-holding-now]')
    };
    var startsAt = parseInt(placeholder.getAttribute('data-holding-starts') || '0', 10) || 0;
    var nowLine = document.querySelector('[data-watch-now]');
    var pill = document.querySelector('[data-watch-pill]');
    // The header pill only ever says Live; the holding screen carries its own state.
    function setLive(on) {
      if (pill) { pill.classList.toggle('is-live', !!on); pill.hidden = !on; }
    }

    function setState(state) {
      placeholder.className = placeholder.className.replace(/holding--\w+/g, '').trim() + ' holding--' + state;
      placeholder.setAttribute('data-holding-state', state);
    }
    function say(message, note, state) {
      placeholder.hidden = false;
      video.hidden = true;
      frame.hidden = true;
      if (hold.label) hold.label.textContent = message;
      if (hold.headline) hold.headline.textContent = note ? message : hold.headline.textContent;
      if (hold.message && note) hold.message.textContent = note;
      if (state) setState(state);
    }
    function showHolding(h) {
      placeholder.hidden = false;
      video.hidden = true;
      frame.hidden = true;
      setState(h.state || 'soon');
      if (hold.label) hold.label.textContent = h.label || '';
      if (hold.headline) hold.headline.textContent = h.headline || '';
      if (hold.message) hold.message.textContent = h.message || '';
      startsAt = h.starts_at ? parseInt(h.starts_at, 10) : 0;
      if (hold.startsText) { hold.startsText.textContent = h.starts_text || ''; hold.startsText.hidden = !h.starts_text; }
      if (hold.now) { hold.now.textContent = h.now || ''; hold.now.hidden = !h.now; }
      tickCountdown();
    }
    function tickCountdown() {
      if (!hold.countdown) return;
      if (!startsAt) { hold.countdown.hidden = true; return; }
      var left = startsAt - Math.floor(Date.now() / 1000);
      if (left <= 0) { hold.countdown.textContent = 'Any moment now'; hold.countdown.hidden = false; return; }
      var d = Math.floor(left / 86400), hrs = Math.floor(left % 86400 / 3600), m = Math.floor(left % 3600 / 60), sec = left % 60;
      var pad = function (n) { return (n < 10 ? '0' : '') + n; };
      hold.countdown.textContent = (d ? d + 'd ' : '') + pad(hrs) + ':' + pad(m) + ':' + pad(sec);
      hold.countdown.hidden = false;
    }
    window.setInterval(tickCountdown, 1000);
    tickCountdown();

    function stop(message, note) {
      if (hls) { hls.destroy(); hls = null; }
      video.removeAttribute('src');
      loaded = false;
      say(message, note);
    }

    function play(data) {
      if (loaded) return;
      loaded = true;

      if (data.kind === 'iframe') {
        frame.innerHTML = '';
        var iframe = document.createElement('iframe');
        iframe.src = data.source;
        iframe.allow = 'autoplay; fullscreen; picture-in-picture';
        iframe.allowFullscreen = true;
        iframe.title = 'Live stream';
        frame.appendChild(iframe);
        frame.hidden = false;
        placeholder.hidden = true;
        return;
      }

      placeholder.hidden = true;
      video.hidden = false;

      if (data.kind === 'hls') {
        // Safari plays HLS natively; everything else needs the library.
        if (video.canPlayType('application/vnd.apple.mpegurl')) {
          video.src = data.source;
        } else if (window.Hls && window.Hls.isSupported()) {
          hls = new window.Hls({ lowLatencyMode: true });
          hls.loadSource(data.source);
          hls.attachMedia(video);
        } else {
          stop('This browser cannot play the stream', 'Try Chrome, Safari or Edge.');
          return;
        }
      } else {
        video.src = data.source;
      }

      video.play().catch(function () { /* a viewer gesture will start it */ });
    }

    function check() {
      fetch(sourceUrl, { headers: { Accept: 'application/json' } })
        .then(function (r) { return r.json().then(function (d) { return { status: r.status, body: d }; }); })
        .then(function (res) {
          if (res.status === 409) {
            stop('Signed out', 'Your pass was opened on another device.');
            window.setTimeout(function () { location.reload(); }, 2500);
            return;
          }
          if (res.status === 403) { location.reload(); return; }

          var data = res.body;
          if (!data.ok) return;
          if (!data.live) {
            if (hls) { hls.destroy(); hls = null; }
            video.removeAttribute('src');
            loaded = false;
            setLive(false);
            showHolding(data.holding || { state: 'soon', label: 'Starting soon', headline: 'We are about to begin.', message: '' });
            if (typeof data.watching === 'number') showWatching(data.watching);
            return;
          }
          if (statusLabel) statusLabel.textContent = 'Live';
          setLive(true);
          if (nowLine) { nowLine.textContent = data.now || ''; nowLine.hidden = !data.now; }
          if (typeof data.watching === 'number') showWatching(data.watching);
          if (loaded) return; // already playing: only the now/next line was refreshed
          play(data);
        })
        .catch(function () {});
    }

    function beat() {
      if (document.hidden) return;
      fetch(beatUrl, { method: 'POST', keepalive: true })
        .then(function (r) {
          if (r.status === 409) {
            stop('Signed out', 'Your pass was opened on another device.');
            window.setTimeout(function () { location.reload(); }, 2500);
          } else if (r.status === 403) {
            location.reload();
          }
        })
        .catch(function () {});
    }

    check();
    beat();
    window.setInterval(beat, 25000);
    // Every 15s: start the video the moment it goes live, follow pauses and the now/next line while it plays.
    window.setInterval(check, 15000);
    document.addEventListener('visibilitychange', function () { if (!document.hidden) beat(); });
  })();

  /* ---------- Nav: scrolled state + mobile menu ---------- */
  var nav = document.getElementById('nav');
  if (nav) {
    var onScroll = function () {
      nav.classList.toggle('is-scrolled', window.scrollY > 24);
    };
    onScroll();
    window.addEventListener('scroll', onScroll, { passive: true });
  }

  var burger = document.getElementById('burger');
  var mobileMenu = document.getElementById('mobileMenu');
  if (burger && mobileMenu) {
    var setMenuOpen = function (open) {
      burger.setAttribute('aria-expanded', open ? 'true' : 'false');
      burger.setAttribute('aria-label', open ? 'Close menu' : 'Open menu');
      mobileMenu.setAttribute('aria-hidden', open ? 'false' : 'true');
      mobileMenu.classList.toggle('is-open', open);
      document.body.classList.toggle('menu-open', open);
    };

    burger.addEventListener('click', function () {
      setMenuOpen(burger.getAttribute('aria-expanded') !== 'true');
    });
    mobileMenu.querySelectorAll('a').forEach(function (link) {
      link.addEventListener('click', function () { setMenuOpen(false); });
    });
    document.addEventListener('keydown', function (event) {
      if (event.key === 'Escape') setMenuOpen(false);
    });
    window.addEventListener('resize', function () {
      if (window.innerWidth > 900) setMenuOpen(false);
    });
  }

  /* ---------- Fields that open only when a specific choice is made ---------- */
  // data-reveal-when="<field name>:<value>" — hidden until that choice is made.
  // Works for checkboxes, radios and selects.
  document.querySelectorAll('[data-reveal-when]').forEach(function (panel) {
    var spec = panel.getAttribute('data-reveal-when');
    var divider = spec.indexOf(':');
    var name = spec.slice(0, divider);
    var wanted = spec.slice(divider + 1);

    var controls = Array.prototype.slice.call(document.querySelectorAll('[name="' + name + '"]'))
      .filter(function (c) {
        return c.tagName === 'SELECT' || c.value === wanted;
      });
    if (!controls.length) return;

    var input = panel.querySelector('input, textarea, select');

    function isOpen() {
      return controls.some(function (c) {
        return c.tagName === 'SELECT' ? c.value === wanted : c.checked;
      });
    }

    function sync(focusOnOpen) {
      var open = isOpen();
      panel.hidden = !open;
      if (!open && input) input.value = '';
      if (open && focusOnOpen && input) input.focus();
    }

    controls.forEach(function (c) {
      c.addEventListener('change', function () { sync(true); });
    });
    sync(false);
  });

  /* ---------- Registration form: path switching + step indicator ---------- */
  var form = document.getElementById('regForm');
  if (form) {
    var pathInputs = Array.prototype.slice.call(form.querySelectorAll('input[name="participation"]'));
    var conditionals = Array.prototype.slice.call(form.querySelectorAll('.form__conditional'));
    var onsiteHide = Array.prototype.slice.call(form.querySelectorAll('[data-onsite-hide]'));
    var steps = Array.prototype.slice.call(document.querySelectorAll('#regSteps li'));

    // ponytail: derive from the rendered steps so paths that omit sections stay aligned
    var sectionOrder = steps.length
      ? steps.map(function (li) { return li.getAttribute('data-step'); })
      : ['path', 'you', 'produce', 'details', 'consent'];
    var sections = sectionOrder.reduce(function (acc, key) {
      acc[key] = form.querySelector('[data-section="' + key + '"]');
      return acc;
    }, {});

    function currentPath() {
      var checked = form.querySelector('input[name="participation"]:checked, input[name="participation"][type="hidden"]');
      return checked ? checked.value : null;
    }

    function applyPath(path) {
      conditionals.forEach(function (el) { el.classList.toggle('is-active', el.getAttribute('data-only') === path); });
      onsiteHide.forEach(function (el) { el.style.display = path === 'onsite' ? 'none' : ''; });
    }

    function stepFromScroll() {
      if (!steps.length) return;
      var offset = 120;
      var current = 'path';
      sectionOrder.forEach(function (key) {
        var el = sections[key];
        if (el && el.getBoundingClientRect().top - offset <= 0) current = key;
      });
      var idx = sectionOrder.indexOf(current);
      steps.forEach(function (li, i) {
        li.classList.toggle('is-current', i === idx);
        li.classList.toggle('is-done', i < idx);
      });
    }

    pathInputs.forEach(function (input) {
      input.addEventListener('change', function () { applyPath(input.value); });
    });
    applyPath(currentPath());

    window.addEventListener('scroll', stepFromScroll, { passive: true });
    window.addEventListener('resize', stepFromScroll);
    stepFromScroll();

    // Coming back with the browser's back button restores the page as it was
    // left, button disabled and all. Put it back the way it started.
    window.addEventListener('pageshow', function () {
      var btn = document.getElementById('submitBtn');
      if (btn && btn.disabled) {
        btn.disabled = false;
        btn.classList.remove('is-loading');
        var label = btn.querySelector('.btn__label');
        if (label && btn.dataset.prev) label.textContent = btn.dataset.prev;
      }
    });

    // Gentle live validation feedback on submit
    form.addEventListener('submit', function () {
      var btn = document.getElementById('submitBtn');
      if (btn) {
        btn.disabled = true;
        btn.classList.add('is-loading');
        var label = btn.querySelector('.btn__label');
        if (label) { var prev = label.textContent; label.textContent = 'Submitting…'; btn.dataset.prev = prev; }
      }
    });
  }

  /* ---------- Video triggers: short hover preview only ---------- */
  var triggers = document.querySelectorAll('[data-video-trigger]');
  if (triggers.length) {
    var canHover = window.matchMedia('(hover: hover) and (pointer: fine)').matches;
    var preview = null;
    var previewPlayer = null;
    var previewTimer = null;

    var positionPreview = function () {
      if (!preview || !currentTrigger || !preview.classList.contains('is-visible')) return;
      var rect = currentTrigger.getBoundingClientRect();
      var w = preview.offsetWidth;
      var h = preview.offsetHeight;
      var top = rect.top - h - 16;
      var below = false;
      if (top < 80) { top = rect.bottom + 16; below = true; }
      var left = rect.left + rect.width / 2 - w / 2;
      left = Math.max(12, Math.min(left, window.innerWidth - w - 12));
      preview.classList.toggle('is-below', below);
      preview.style.top = top + 'px';
      preview.style.left = left + 'px';
    };

    var positionLoop = null;
    var startLoop = function () {
      if (positionLoop) return;
      var tick = function () { positionPreview(); positionLoop = requestAnimationFrame(tick); };
      positionLoop = requestAnimationFrame(tick);
    };
    var stopLoop = function () {
      if (positionLoop) { cancelAnimationFrame(positionLoop); positionLoop = null; }
    };

    var stopPreview = function () {
      if (previewTimer) { clearTimeout(previewTimer); previewTimer = null; }
      stopLoop();
      if (preview) { preview.classList.remove('is-visible'); }
      if (previewPlayer) {
        previewPlayer.pause();
        try { previewPlayer.currentTime = 0; } catch (e) {}
      }
    };

    var startPreview = function (src) {
      if (!canHover) return;
      if (!preview) {
        preview = document.createElement('figure');
        preview.className = 'video-preview';
        preview.setAttribute('aria-hidden', 'true');
        previewPlayer = document.createElement('video');
        previewPlayer.muted = true;
        previewPlayer.volume = 0;
        previewPlayer.loop = true;
        previewPlayer.playsInline = true;
        previewPlayer.preload = 'none';
        previewPlayer.controls = false;
        previewPlayer.disablePictureInPicture = true;
        previewPlayer.setAttribute('controlslist', 'nodownload nofullscreen noremoteplayback');
        previewPlayer.setAttribute('disableremoteplayback', '');
        preview.appendChild(previewPlayer);
        document.body.appendChild(preview);
      }
      if (previewPlayer.getAttribute('src') !== src) previewPlayer.setAttribute('src', src);
      previewTimer = setTimeout(function () {
        preview.classList.add('is-visible');
        preview.classList.remove('is-below');
        positionPreview();
        startLoop();
        var p = previewPlayer.play();
        if (p && p.catch) p.catch(function () {});
      }, 200);
    };

    var currentTrigger = null;
    triggers.forEach(function (el) {
      currentTrigger = el;
      el.addEventListener('mouseenter', function () { currentTrigger = el; startPreview(el.getAttribute('data-video-src')); });
      el.addEventListener('mouseleave', stopPreview);
    });
  }

  /* ---------- GSAP motion ---------- */
  function initGsap() {
    if (!window.gsap) return;
    var gsap = window.gsap;
    var hasST = !!window.ScrollTrigger;
    if (hasST) gsap.registerPlugin(window.ScrollTrigger);

    if (reduce) {
      document.querySelectorAll('[data-reveal], [data-split]').forEach(function (el) { el.classList.add('is-inview'); });
      return;
    }

    // Hero panel parallax on scroll
    var hero = document.querySelector('.hero');
    if (hero && hasST) {
      gsap.utils.toArray('.hero__panel').forEach(function (panel) {
        var depth = parseFloat(panel.getAttribute('data-parallax') || '0');
        gsap.to(panel, {
          y: depth * 2.4, ease: 'none',
          scrollTrigger: { trigger: hero, start: 'top top', end: 'bottom top', scrub: true }
        });
      });
    }

    // Reveal on scroll
    if (hasST) {
      gsap.utils.toArray('[data-reveal]').forEach(function (el) {
        var delay = parseFloat(el.getAttribute('data-reveal-delay') || '0');
        ScrollTrigger.create({
          trigger: el, start: 'top 88%', once: true,
          onEnter: function () { gsap.to(el, { opacity: 1, y: 0, duration: 0.9, ease: 'power3.out', delay: delay }); }
        });
      });
    } else {
      document.querySelectorAll('[data-reveal]').forEach(function (el) { el.classList.add('is-inview'); });
    }

    // Split text (line-based, manual — no plugin needed)
    document.querySelectorAll('[data-split]').forEach(function (el) {
      var lines = wrapLines(el);
      if (hasST) {
        gsap.set(lines, { yPercent: 110, y: 0 });
        ScrollTrigger.create({
          trigger: el, start: 'top 85%', once: true,
          onEnter: function () {
            gsap.to(lines, { yPercent: 0, duration: 1, ease: 'power4.out', stagger: 0.08 });
          }
        });
      } else {
        lines.forEach(function (l) { l.style.transform = 'none'; });
      }
    });

    // Collage float
    document.querySelectorAll('[data-float]').forEach(function (el) {
      var n = parseInt(el.getAttribute('data-float'), 10) || 1;
      gsap.to(el, {
        y: n % 2 ? '+=10' : '-=10', rotate: (n % 2 ? '+=' : '-=') + (1 + n * 0.3),
        duration: 4 + n * 0.4, ease: 'sine.inOut', repeat: -1, yoyo: true
      });
    });

    // Admin bar fills
    if (hasST) {
      gsap.utils.toArray('.adm-bar').forEach(function (el) {
        ScrollTrigger.create({ trigger: el, start: 'top 95%', once: true, onEnter: function () { el.classList.add('is-inview'); } });
      });
    }

    // Note card entry (keeps its CSS tilt)
    var note = document.querySelector('.collage__note');
    if (note && hasST) {
      ScrollTrigger.create({
        trigger: note, start: 'top 92%', once: true,
        onEnter: function () {
          gsap.from(note, { opacity: 0, y: 26, rotation: -8.5, duration: .9, ease: 'power3.out' });
        }
      });
    }

    // Eyebrow pill: glossy beam around border
    var pillEl = document.querySelector('.eyebrow--pill');
    if (pillEl) {
      var beam = { a: 0 };
      gsap.to(beam, {
        a: 360, duration: 4.5, ease: 'none', repeat: -1,
        onUpdate: function () { pillEl.style.setProperty('--beam', beam.a.toFixed(2) + 'deg'); }
      });
    }

    // Refresh after fonts load
    if (document.fonts && document.fonts.ready && hasST) {
      document.fonts.ready.then(function () { ScrollTrigger.refresh(); });
    }
  }

  function wrapLines(el) {
    // Wrap each visual line in an overflow-hidden mask with an inner that slides up.
    // Lines come from <br> separators or from block children; otherwise the whole text is one line.
    var groups = [[]];
    var sawBreak = false;
    Array.prototype.slice.call(el.childNodes).forEach(function (node) {
      if (node.nodeType === 1 && node.tagName === 'BR') {
        sawBreak = true;
        groups.push([]);
        return;
      }
      if (node.nodeType === 3 && !node.textContent.trim()) return;
      groups[groups.length - 1].push(node);
    });

    if (!sawBreak) {
      // Block children (e.g. .hero__line spans) already act as their own lines.
      var elements = groups[0].filter(function (n) { return n.nodeType === 1; });
      groups = elements.length > 1 ? elements.map(function (n) { return [n]; }) : [groups[0]];
    }

    groups = groups.filter(function (g) { return g.length; });
    if (!groups.length) return [];

    el.innerHTML = '';
    return groups.map(function (nodes) {
      var mask = document.createElement('span'); mask.className = 'split-line';
      var inner = document.createElement('span'); inner.className = 'split-inner';
      nodes.forEach(function (node) { inner.appendChild(node); });
      mask.appendChild(inner);
      el.appendChild(mask);
      return inner;
    });
  }

  /* ---------- Copy-to-clipboard buttons ---------- */
  // "Starting in 3d 4h" on the pass; hides itself once the day arrives.
  document.querySelectorAll('[data-countdown]').forEach(function (el) {
    var at = Date.parse(el.getAttribute('data-countdown'));
    var out = el.querySelector('span');
    if (isNaN(at) || !out) return;
    var tick = function () {
      var left = Math.floor((at - Date.now()) / 1000);
      if (left <= 0) { el.hidden = true; return; }
      var d = Math.floor(left / 86400), h = Math.floor(left % 86400 / 3600), m = Math.floor(left % 3600 / 60);
      out.textContent = d > 0 ? d + 'd ' + h + 'h' : h > 0 ? h + 'h ' + m + 'm' : m + 'm';
      el.hidden = false;
    };
    tick();
    setInterval(tick, 60000);
  });

  // Sponsor amount chips are radios, so the browser brings them back after
  // a back-button return; the amount box is refilled from whichever is checked.
  var amountBox = document.getElementById('amount');
  var presets = document.querySelectorAll('input[name="preset"]');
  if (amountBox && presets.length) {
    var fillFromPreset = function (focusOther) {
      var checked = document.querySelector('input[name="preset"]:checked');
      if (!checked) return;
      if (checked.value) amountBox.value = checked.value;
      else if (focusOther) { amountBox.value = ''; amountBox.focus(); }
    };
    presets.forEach(function (r) { r.addEventListener('change', function () { fillFromPreset(true); }); });
    amountBox.addEventListener('input', function () {
      var match = document.querySelector('input[name="preset"][value="' + amountBox.value.replace(/"/g, '') + '"]');
      (match || presets[presets.length - 1]).checked = true;
    });
    window.addEventListener('pageshow', function () { if (!amountBox.value) fillFromPreset(false); });
  }

  // A form that came back with errors: bring the first one into view.
  var firstError = document.querySelector('.field__error, .form__error');
  if (firstError && !location.hash) {
    firstError.closest('.field, fieldset, form').scrollIntoView({ block: 'center' });
  }

  document.querySelectorAll('[data-copy]').forEach(function (btn) {
    btn.addEventListener('click', function () {
      // data-copy holds the text itself, or a #id whose text should be copied.
      var raw = btn.getAttribute('data-copy') || '';
      var text = raw;
      if (raw.charAt(0) === '#') {
        var source = document.querySelector(raw);
        if (!source) return;
        text = (source.textContent || '').trim();
      }
      var swap = function () {
        var original = btn.textContent;
        btn.textContent = 'Copied ✓';
        btn.classList.add('is-copied');
        setTimeout(function () {
          btn.textContent = original;
          btn.classList.remove('is-copied');
        }, 1600);
      };
      var fallback = function () {
        var ta = document.createElement('textarea');
        ta.value = text;
        ta.setAttribute('readonly', '');
        ta.style.position = 'fixed';
        ta.style.opacity = '0';
        document.body.appendChild(ta);
        ta.select();
        try { document.execCommand('copy'); } catch (e) {}
        document.body.removeChild(ta);
      };
      if (navigator.clipboard && navigator.clipboard.writeText) {
        navigator.clipboard.writeText(text).then(swap, function () { fallback(); swap(); });
      } else {
        fallback();
        swap();
      }
    });
  });

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initGsap);
  } else {
    initGsap();
  }

  /* Viewer count, shown in the header and over the video. Shared by the player and the chat polls. */
  function showWatching(n) {
    var text = n === 1 ? '1 watching' : n + ' watching';
    document.querySelectorAll('[data-watch-count]').forEach(function (el) { el.textContent = text; el.hidden = n < 1; });
  }

  /* ---------- Comment board on the watch page ---------- */
  (function () {
    var board = document.querySelector('[data-comments]');
    if (!board) return;
    var boardOpen = board.getAttribute('data-comments-open') === '1';

    var url = board.getAttribute('data-comments-url');
    var list = board.querySelector('[data-comments-list]');
    var empty = board.querySelector('[data-comments-empty]');
    var count = board.querySelector('[data-comments-count]');
    var form = board.querySelector('[data-comments-form]');
    var input = board.querySelector('[data-comments-input]');
    var hint = board.querySelector('[data-comments-hint]');
    var token = form.querySelector('input[name="_token"]').value;
    var defaultHint = hint.textContent;
    var lastId = 0;
    var total = 0;

    function atBottom() {
      return list.scrollHeight - list.scrollTop - list.clientHeight < 40;
    }

    function render(comments) {
      if (!comments.length) return;
      var stick = atBottom();
      comments.forEach(function (c) {
        if (c.id <= lastId) return;
        lastId = c.id;
        total++;
        var li = document.createElement('li');
        li.className = 'chat__item';
        var initials = c.author.replace(/&[^;]+;/g, '').split(/\s+/).map(function (w) { return w.charAt(0); }).join('').slice(0, 2).toUpperCase() || '•';
        // The server escapes author and body, so this markup is already safe.
        li.innerHTML = '<span class="chat__initials mono" aria-hidden="true">' + initials + '</span><div>' +
                       '<p class="chat__meta"><span class="chat__author">' + c.author + '</span><span>' + c.at + '</span></p>' +
                       '<p class="chat__body">' + c.body + '</p></div>';
        list.appendChild(li);
      });
      if (empty && empty.parentNode) empty.remove();
      count.textContent = total + (total === 1 ? ' message' : ' messages');
      if (stick) list.scrollTop = list.scrollHeight;
    }

    function closeBoard() {
      board.hidden = true;
    }

    // ----- The organisers' poll or question, popping up over the page -----
    var promptBox = document.querySelector('[data-prompt]');
    var promptUrl = promptBox ? promptBox.getAttribute('data-prompt-url') : '';
    var promptToken = promptBox ? promptBox.querySelector('input[name="_token"]').value : '';
    var shownPromptId = 0, dismissedPromptId = 0, promptBusy = false;
    function promptSay(msg, isError) {
      var h = promptBox.querySelector('[data-prompt-hint]'); h.textContent = msg || ''; h.classList.toggle('is-error', !!isError);
    }
    function showPrompt(pr) {
      if (!promptBox) return;
      if (!pr) { promptBox.hidden = true; shownPromptId = 0; return; }
      if (pr.id === dismissedPromptId) return;
      if (pr.answered && pr.id !== shownPromptId) { dismissedPromptId = pr.id; return; } // answered earlier: nothing more to show
      var fresh = pr.id !== shownPromptId;
      shownPromptId = pr.id;
      promptBox.querySelector('[data-prompt-kicker]').textContent = pr.kind === 'poll' ? 'Poll from the organisers' : 'Question from the organisers';
      promptBox.querySelector('[data-prompt-question]').innerHTML = pr.question;
      var body = promptBox.querySelector('[data-prompt-body]'); body.innerHTML = '';
      if (pr.kind === 'poll') {
        var total = pr.results ? pr.results.total : 0;
        pr.options.forEach(function (label, i) {
          var b = document.createElement('button'); b.type = 'button'; b.className = 'prompt__option' + (pr.answered ? ' is-result' : '') + (pr.my_choice === i ? ' is-mine' : '');
          var pct = pr.results && total ? Math.round(100 * pr.results.counts[i] / total) : 0;
          b.innerHTML = '<span class="prompt__option-fill" style="width:' + (pr.answered ? pct : 0) + '%"></span><span class="prompt__option-label">' + label + '</span>' + (pr.answered ? '<span class="mono prompt__option-pct">' + pct + '%</span>' : '');
          if (!pr.answered) b.addEventListener('click', function () { answerPrompt({ prompt_id: pr.id, choice: i }); });
          else b.disabled = true;
          body.appendChild(b);
        });
        promptSay(pr.answered ? (total === 1 ? '1 vote so far' : total + ' votes so far') + ' · thank you' : 'Tap one to vote. One vote each.');
      } else {
        if (pr.answered) {
          body.innerHTML = '<p class="prompt__thanks">Thank you, your reply has gone to the organisers.</p>';
          promptSay('');
        } else {
          var wrap = document.createElement('div'); wrap.className = 'prompt__reply';
          wrap.innerHTML = '<textarea rows="2" maxlength="500" placeholder="Your reply…"></textarea><button type="button" class="btn btn--ink prompt__send"><span class="btn__label">Send</span></button>';
          wrap.querySelector('button').addEventListener('click', function () {
            var t = wrap.querySelector('textarea').value.trim(); if (!t) { promptSay('Write something first.', true); return; }
            answerPrompt({ prompt_id: pr.id, text: t });
          });
          body.appendChild(wrap);
          promptSay('Only the organisers see replies.');
        }
      }
      promptBox.hidden = false;
      if (fresh) { promptBox.classList.remove('is-in'); void promptBox.offsetWidth; promptBox.classList.add('is-in'); }
    }
    function answerPrompt(fields) {
      if (promptBusy) return; promptBusy = true;
      var data = new FormData(); data.append('_token', promptToken);
      Object.keys(fields).forEach(function (k) { data.append(k, String(fields[k])); });
      fetch(promptUrl, { method: 'POST', body: data, headers: { 'X-Requested-With': 'fetch' } })
        .then(function (r) { return r.json(); })
        .then(function (d) {
          if (d && d.ok) {
            showPrompt(d.prompt);
            // Answered: let them see the result, then slide away on its own.
            var answeredId = d.prompt && d.prompt.id;
            window.setTimeout(function () { if (shownPromptId === answeredId && !promptBox.hidden) { dismissedPromptId = answeredId; promptBox.hidden = true; } }, 6000);
          } else {
            promptSay((d && d.message) || 'That did not send.', true);
            if (d && d.reason === 'closed') showPrompt(null);
          }
        })
        .catch(function () { promptSay('That did not send. Try again.', true); })
        .then(function () { promptBusy = false; });
    }
    if (promptBox) promptBox.querySelector('[data-prompt-dismiss]').addEventListener('click', function () { dismissedPromptId = shownPromptId; promptBox.hidden = true; });

    function load() {
      fetch(url + '?after=' + lastId, { headers: { 'X-Requested-With': 'fetch' } })
        .then(function (r) { return r.ok ? r.json() : null; })
        .then(function (d) {
          if (!d || !d.ok) return;
          if (typeof d.watching === 'number') showWatching(d.watching);
          showPrompt(d.prompt || null);
          if (!d.enabled) { closeBoard(); return; }
          render(d.comments || []);
        })
        .catch(function () {});
    }

    function say(message, isError) {
      hint.textContent = message;
      hint.classList.toggle('is-error', !!isError);
      if (message !== defaultHint) {
        setTimeout(function () { hint.textContent = defaultHint; hint.classList.remove('is-error'); }, 4000);
      }
    }

    var sending = false;
    if (boardOpen) form.addEventListener('submit', function (e) {
      e.preventDefault();
      var body = input.value.trim();
      if (!body || sending) return; // a second press while one is in flight is ignored, not queued
      sending = true;
      var button = form.querySelector('button[type="submit"]');
      button.disabled = true;

      var data = new FormData();
      data.append('_token', token);
      data.append('body', body);
      data.append('after', String(lastId));

      fetch(url, { method: 'POST', body: data, headers: { 'X-Requested-With': 'fetch' } })
        .then(function (r) { return r.json().then(function (d) { return { status: r.status, data: d }; }); })
        .then(function (res) {
          if (res.status === 403) { location.reload(); return; } // signed out elsewhere
          if (res.data && res.data.ok) {
            input.value = ''; input.style.height = 'auto';
            render(res.data.comments || []);
            list.scrollTop = list.scrollHeight;
            input.focus();
            return;
          }
          if (res.data && res.data.reason === 'closed') closeBoard();
          if (res.data && res.data.reason === 'too_fast') {
            // Sent too soon after the last one: hold it and send again by ourselves.
            say('Sending…');
            window.setTimeout(function () { sending = false; button.disabled = false; form.dispatchEvent(new Event('submit', { cancelable: true })); }, Math.max(1, res.data.retry_after || 1) * 1000 + 150);
            return 'retrying';
          }
          say((res.data && res.data.message) || 'That did not send. Try again.', true);
        })
        .catch(function () { say('That did not send. Try again.', true); })
        .then(function (outcome) { if (outcome !== 'retrying') { sending = false; button.disabled = false; } });
    });

    // The composer grows with the message, up to a few lines.
    if (boardOpen) input.addEventListener('input', function () { input.style.height = 'auto'; input.style.height = Math.min(input.scrollHeight, 96) + 'px'; });

    // Enter sends, shift+enter starts a new line.
    if (boardOpen) input.addEventListener('keydown', function (e) {
      if (e.key === 'Enter' && !e.shiftKey) {
        e.preventDefault();
        form.dispatchEvent(new Event('submit', { cancelable: true }));
      }
    });

    load();
    setInterval(load, 3000); // comments and any poll or question, a few seconds at most
  })();
})();
