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

    /* Zone or Campus Ministry → Group → Church directory. */
    var churchHierarchy = form.querySelector('[data-church-hierarchy]');
    if (churchHierarchy) {
      var apiBase = churchHierarchy.getAttribute('data-api-base').replace(/\/$/, '');
      var zoneValue = churchHierarchy.querySelector('#zone');
      var zoneSelect = churchHierarchy.querySelector('#zone_directory');
      var campusSelect = churchHierarchy.querySelector('#campus_directory');
      var directoryTypeInputs = Array.prototype.slice.call(churchHierarchy.querySelectorAll('input[name="directory_type"]'));
      var directoryChoices = Array.prototype.slice.call(churchHierarchy.querySelectorAll('[data-directory-choice]'));
      var groupSelect = churchHierarchy.querySelector('#group_name');
      var churchSelect = churchHierarchy.querySelector('#church_name');
      var directoryStatus = churchHierarchy.querySelector('[data-church-status]');
      var oldZone = zoneValue.getAttribute('data-old-value') || '';
      var oldGroup = groupSelect.getAttribute('data-old-value') || '';
      var oldChurch = churchSelect.getAttribute('data-old-value') || '';

      function directoryUrl(path) {
        return path.indexOf('http') === 0 ? path : new URL(path, apiBase + '/').toString();
      }

      function fetchDirectory(path) {
        return fetch(directoryUrl(path), { headers: { Accept: 'application/json' } }).then(function (response) {
          if (!response.ok) throw new Error('Directory request failed');
          return response.json();
        });
      }

      function resetDirectorySelect(select, label, disabled) {
        select.innerHTML = '';
        var option = document.createElement('option');
        option.value = '';
        option.textContent = label;
        select.appendChild(option);
        select.disabled = disabled;
      }

      function appendDirectoryOptions(select, items, oldValue) {
        items.forEach(function (item) {
          var option = document.createElement('option');
          option.value = item.name;
          option.textContent = item.name;
          option.dataset.id = String(item.id);
          option.dataset.link = item.links && (item.links.groups || item.links.churches) || '';
          option.selected = item.name === oldValue;
          select.appendChild(option);
        });
      }

      function loadGroups(selected) {
        resetDirectorySelect(groupSelect, 'Loading groups…', true);
        resetDirectorySelect(churchSelect, 'Choose a group first', true);
        var groupsPath = selected && selected.dataset.link;
        if (!groupsPath) {
          zoneValue.value = '';
          resetDirectorySelect(groupSelect, 'Choose a zone or Campus Ministry first', true);
          directoryStatus.textContent = 'Choose your zone or Campus Ministry, then your group and church.';
          return;
        }
        zoneValue.value = selected.value;
        directoryStatus.textContent = 'Loading groups…';
        fetchDirectory(groupsPath).then(function (payload) {
          var groups = Array.isArray(payload.data) ? payload.data : [];
          resetDirectorySelect(groupSelect, groups.length ? 'Select your group' : 'No groups listed', false);
          appendDirectoryOptions(groupSelect, groups, oldGroup);
          directoryStatus.textContent = groups.length ? 'Now choose your group.' : 'No groups are currently listed for this zone.';
          if (oldGroup && groupSelect.value === oldGroup) {
            oldGroup = '';
            groupSelect.dispatchEvent(new Event('change'));
          }
        }).catch(function () {
          resetDirectorySelect(groupSelect, 'Could not load groups — refresh to try again', true);
          directoryStatus.textContent = 'The church directory is temporarily unavailable. Please refresh and try again.';
        });
      }

      zoneSelect.addEventListener('change', function () {
        loadGroups(zoneSelect.options[zoneSelect.selectedIndex]);
      });
      campusSelect.addEventListener('change', function () {
        loadGroups(campusSelect.options[campusSelect.selectedIndex]);
      });

      directoryTypeInputs.forEach(function (input) {
        input.addEventListener('change', function () {
          directoryChoices.forEach(function (choice) {
            choice.hidden = choice.getAttribute('data-directory-choice') !== input.value;
          });
          zoneValue.value = '';
          zoneSelect.value = '';
          campusSelect.value = '';
          resetDirectorySelect(groupSelect, 'Choose a zone or Campus Ministry first', true);
          resetDirectorySelect(churchSelect, 'Choose a group first', true);
          directoryStatus.textContent = input.value === 'campus'
            ? 'Choose your Campus Ministry, then your group and church.'
            : 'Choose your zone, then your group and church.';
        });
      });

      groupSelect.addEventListener('change', function () {
        resetDirectorySelect(churchSelect, 'Loading churches…', true);
        var selected = groupSelect.options[groupSelect.selectedIndex];
        var churchesPath = selected && selected.dataset.link;
        if (!churchesPath) {
          resetDirectorySelect(churchSelect, 'Choose a group first', true);
          return;
        }
        directoryStatus.textContent = 'Loading churches…';
        fetchDirectory(churchesPath).then(function (payload) {
          var churches = Array.isArray(payload.data) ? payload.data : [];
          resetDirectorySelect(churchSelect, 'Select your church', false);
          appendDirectoryOptions(churchSelect, churches, oldChurch);
          var notListed = document.createElement('option');
          notListed.value = 'Church not listed';
          notListed.textContent = 'My church is not listed';
          notListed.selected = oldChurch === notListed.value || churches.length === 0;
          churchSelect.appendChild(notListed);
          directoryStatus.textContent = churches.length ? 'Choose your church, or select “My church is not listed”.' : 'No churches are listed for this group; “My church is not listed” has been selected.';
          oldChurch = '';
        }).catch(function () {
          resetDirectorySelect(churchSelect, 'Could not load churches — refresh to try again', true);
          directoryStatus.textContent = 'The church directory is temporarily unavailable. Please refresh and try again.';
        });
      });

      fetchDirectory('/api/v1/regions').then(function (payload) {
        var regions = Array.isArray(payload.data) ? payload.data : [];
        return Promise.all(regions.map(function (region) {
          return fetchDirectory(region.links.zones).then(function (zonesPayload) {
            return (zonesPayload.data || []).map(function (zone) {
              return Object.assign({}, zone, { isCampus: region.name === 'Campus Ministry' });
            });
          });
        }));
      }).then(function (regionZones) {
        var zones = [].concat.apply([], regionZones);
        zones.sort(function (a, b) { return a.name.localeCompare(b.name); });
        var mainZones = zones.filter(function (zone) { return !zone.isCampus; });
        var campuses = zones.filter(function (zone) { return zone.isCampus; });
        resetDirectorySelect(zoneSelect, 'Select your zone', false);
        resetDirectorySelect(campusSelect, 'Select your Campus Ministry', false);
        appendDirectoryOptions(zoneSelect, mainZones, oldZone);
        appendDirectoryOptions(campusSelect, campuses, oldZone);
        directoryStatus.textContent = 'Choose Zone or Campus Ministry to begin.';
        var restoredSelect = zoneSelect.value === oldZone ? zoneSelect : (campusSelect.value === oldZone ? campusSelect : null);
        if (restoredSelect) {
          var restoredType = restoredSelect === campusSelect ? 'campus' : 'zone';
          var typeInput = churchHierarchy.querySelector('input[name="directory_type"][value="' + restoredType + '"]');
          typeInput.checked = true;
          directoryChoices.forEach(function (choice) {
            choice.hidden = choice.getAttribute('data-directory-choice') !== restoredType;
          });
          zoneValue.value = oldZone;
          oldZone = '';
          restoredSelect.dispatchEvent(new Event('change'));
        }
      }).catch(function () {
        resetDirectorySelect(zoneSelect, 'Could not load directory — refresh to try again', true);
        resetDirectorySelect(campusSelect, 'Could not load directory — refresh to try again', true);
        directoryStatus.textContent = 'The church directory is temporarily unavailable. Please refresh and try again.';
      });
    }

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
      var text = btn.getAttribute('data-copy') || '';
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
