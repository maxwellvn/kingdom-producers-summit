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

    function say(message, note) {
      placeholder.hidden = false;
      video.hidden = true;
      frame.hidden = true;
      placeholder.innerHTML = '';
      var line = document.createElement('p');
      line.className = 'mono';
      line.textContent = message;
      placeholder.appendChild(line);
      if (note) {
        var second = document.createElement('p');
        second.className = 'watch__note';
        second.textContent = note;
        placeholder.appendChild(second);
      }
    }

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
            if (statusLabel) statusLabel.textContent = 'Not started yet';
            stop('The stream has not started', 'This page will start it the moment it goes live.');
            return;
          }
          if (statusLabel) statusLabel.textContent = 'Live now';
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
    window.setInterval(function () { if (!loaded) check(); }, 15000);
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
      var submitLabel = document.querySelector('#submitBtn .btn__label');
      if (submitLabel) {
        var label = submitLabel.getAttribute('data-pay-label-' + path) || submitLabel.getAttribute('data-free-label');
        if (label) submitLabel.innerHTML = label;
      }
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
})();
