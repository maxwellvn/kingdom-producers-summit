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
    if (cookieBanner && !storedCookieChoice()) cookieBanner.hidden = false;
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
    var skipIntro = intro.querySelector('[data-intro-skip]');
    if (skipIntro) skipIntro.addEventListener('click', closeIntro);
  } else {
    if (intro) intro.hidden = true;
    showCookieBanner();
  }

  if (cookieBanner) {
    cookieBanner.querySelectorAll('[data-cookie-choice]').forEach(function (button) {
      button.addEventListener('click', function () {
        try { localStorage.setItem('producers_cookie_choice', button.getAttribute('data-cookie-choice')); } catch (e) {}
        cookieBanner.hidden = true;
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
      window.location.href = 'mailto:lkps@loveworldconsulate.org?subject=' + encodeURIComponent('Producer dispatch signup') + '&body=' + encodeURIComponent('Please add ' + email.value + ' to the Producer Dispatches mailing list.');
    });
  }

  document.querySelectorAll('.footer__flag-video').forEach(function (video) {
    var startVideo = function () { video.play().catch(function () {}); };
    if (video.readyState >= 2) startVideo();
    else video.addEventListener('canplay', startVideo, { once: true });
    document.addEventListener('visibilitychange', function () { if (!document.hidden) startVideo(); });
  });

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

  /* ---------- Supporting organisations: animate only when clipped ---------- */
  var supporters = document.querySelector('.supporters');
  var supportersList = document.querySelector('.supporters__list');
  if (supporters && supportersList) {
    var updateSupportersMotion = function () {
      supportersList.classList.remove('is-overflowing');
      supportersList.style.removeProperty('--supporters-overflow');
      supportersList.style.removeProperty('--supporters-duration');

      var available = supporters.getBoundingClientRect().width;
      var content = supportersList.scrollWidth;
      var overflow = Math.ceil(content - available);
      if (overflow > 2) {
        supportersList.style.setProperty('--supporters-overflow', overflow + 'px');
        supportersList.style.setProperty('--supporters-duration', Math.max(9, overflow / 24).toFixed(1) + 's');
        supportersList.classList.add('is-overflowing');
      }
    };

    if ('ResizeObserver' in window) {
      var supportersObserver = new ResizeObserver(updateSupportersMotion);
      supportersObserver.observe(supporters);
      supportersObserver.observe(supportersList);
    } else {
      window.addEventListener('resize', updateSupportersMotion);
    }
    updateSupportersMotion();
  }

  /* ---------- Copy reference code ---------- */
  document.querySelectorAll('[data-copy]').forEach(function (btn) {
    btn.addEventListener('click', function () {
      var target = document.querySelector(btn.getAttribute('data-copy'));
      if (!target) return;
      var text = target.textContent.trim();
      var done = function () { var prev = btn.textContent; btn.textContent = 'Copied'; setTimeout(function () { btn.textContent = prev; }, 1600); };
      if (navigator.clipboard && navigator.clipboard.writeText) {
        navigator.clipboard.writeText(text).then(done, function () { fallback(); });
      } else { fallback(); }
      function fallback() {
        var r = document.createRange(); r.selectNode(target);
        var s = window.getSelection(); s.removeAllRanges(); s.addRange(r);
        try { document.execCommand('copy'); done(); } catch (e) {}
        s.removeAllRanges();
      }
    });
  });

  /* ---------- Registration form: path switching + step indicator ---------- */
  var form = document.getElementById('regForm');
  if (form) {
    var pathInputs = Array.prototype.slice.call(form.querySelectorAll('input[name="participation"]'));
    var conditionals = Array.prototype.slice.call(form.querySelectorAll('.form__conditional'));
    var onsiteHide = Array.prototype.slice.call(form.querySelectorAll('[data-onsite-hide]'));
    var steps = Array.prototype.slice.call(document.querySelectorAll('#regSteps li'));

    var sectionOrder = ['path', 'you', 'produce', 'details', 'consent'];
    var sections = sectionOrder.reduce(function (acc, key) {
      acc[key] = form.querySelector('[data-section="' + key + '"]');
      return acc;
    }, {});

    function currentPath() {
      var checked = form.querySelector('input[name="participation"]:checked');
      return checked ? checked.value : null;
    }

    function applyPath(path) {
      conditionals.forEach(function (el) { el.classList.toggle('is-active', el.getAttribute('data-only') === path); });
      onsiteHide.forEach(function (el) { el.style.display = path === 'onsite' ? 'none' : ''; });
      var submitLabel = document.querySelector('#submitBtn .btn__label');
      if (submitLabel) {
        var paid = path === 'onsite' ? submitLabel.getAttribute('data-pay-label') : submitLabel.getAttribute('data-free-label');
        if (paid) submitLabel.innerHTML = paid;
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

  /* ---------- Video triggers: hover preview + click lightbox ---------- */
  var triggers = document.querySelectorAll('[data-video-trigger]');
  if (triggers.length) {
    var modal = document.getElementById('videoModal');
    var modalPlayer = document.getElementById('videoModalPlayer');
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

    var openModal = function (src) {
      if (!modal) return;
      stopPreview();
      if (modalPlayer.getAttribute('src') !== src) {
        modalPlayer.setAttribute('src', src);
      }
      modal.hidden = false;
      document.body.style.overflow = 'hidden';
      var p = modalPlayer.play();
      if (p && p.catch) p.catch(function () {});
      modal.querySelector('.video-modal__close').focus();
    };

    var closeModal = function () {
      if (!modal || modal.hidden) return;
      modalPlayer.pause();
      modal.hidden = true;
      document.body.style.overflow = '';
    };

    var currentTrigger = null;
    triggers.forEach(function (el) {
      currentTrigger = el;
      el.addEventListener('mouseenter', function () { currentTrigger = el; startPreview(el.getAttribute('data-video-src')); });
      el.addEventListener('mouseleave', stopPreview);
      el.addEventListener('focus', function () { currentTrigger = el; startPreview(el.getAttribute('data-video-src')); });
      el.addEventListener('blur', stopPreview);
      el.addEventListener('click', function () { openModal(el.getAttribute('data-video-src')); });
      el.addEventListener('keydown', function (e) {
        if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); openModal(el.getAttribute('data-video-src')); }
      });
    });

    if (modal) {
      modal.querySelectorAll('[data-video-close]').forEach(function (el) {
        el.addEventListener('click', closeModal);
      });
      document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') closeModal();
      });
    }
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
    var html = el.innerHTML;
    var raw = el.textContent.trim().split(/\r?\n/).map(function (s) { return s.trim(); }).filter(Boolean);
    if (raw.length > 1) {
      // Markup already provides line breaks via <br> or block children — wrap per top-level child.
      el.innerHTML = '';
      var children = Array.prototype.slice.call(parseToNodes(html)).filter(function (n) {
        return n.nodeType === 1 || (n.nodeType === 3 && n.textContent.trim());
      });
      var inners = [];
      children.forEach(function (child) {
        var mask = document.createElement('span'); mask.className = 'split-line';
        var inner = document.createElement('span'); inner.className = 'split-inner';
        inner.appendChild(child);
        mask.appendChild(inner);
        el.appendChild(mask);
        inners.push(inner);
      });
      return inners;
    }
    // Single line — wrap whole text.
    el.innerHTML = '';
    var mask = document.createElement('span'); mask.className = 'split-line';
    var inner = document.createElement('span'); inner.className = 'split-inner'; inner.textContent = raw[0];
    mask.appendChild(inner); el.appendChild(mask);
    return [inner];
  }

  function parseToNodes(html) {
    var tpl = document.createElement('template'); tpl.innerHTML = html; return tpl.content.childNodes;
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initGsap);
  } else {
    initGsap();
  }
})();
