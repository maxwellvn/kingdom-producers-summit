/* Admin UI behaviour: small, dependency-free pieces shared by every admin page. */
(function () {
  'use strict';
  var still = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

  // Show or hide a password. The two icons cross-fade in CSS; this only flips the state.
  document.querySelectorAll('[data-reveal-password]').forEach(function (btn) {
    var input = document.getElementById(btn.getAttribute('aria-controls'));
    if (!input) return;
    btn.addEventListener('click', function () {
      var show = input.type === 'password';
      input.type = show ? 'text' : 'password';
      btn.setAttribute('aria-pressed', show ? 'true' : 'false');
      btn.setAttribute('aria-label', show ? 'Hide password' : 'Show password');
      input.focus({ preventScroll: true });
    });
  });

  // Confirmations become toasts that spring in and leave on their own. Errors stay where they are.
  var flashes = document.querySelectorAll('.adm-main .form__alert[role=status]');
  if (flashes.length) {
    var stack = document.createElement('div');
    stack.className = 'ui-toasts';
    stack.setAttribute('role', 'status');
    stack.setAttribute('aria-live', 'polite');
    document.body.appendChild(stack);
    flashes.forEach(function (flash) {
      var toast = document.createElement('div');
      toast.className = 'ui-toast';
      var mark = document.createElement('span');
      mark.className = 'ui-toast__mark';
      mark.setAttribute('aria-hidden', 'true');
      mark.textContent = '\u2713';
      var text = document.createElement('div');
      text.className = 'ui-toast__text';
      while (flash.firstChild) text.appendChild(flash.firstChild); // keeps any links inside the message
      var close = document.createElement('button');
      close.type = 'button';
      close.className = 'ui-toast__close';
      close.setAttribute('aria-label', 'Dismiss');
      close.textContent = '\u00d7';
      toast.append(mark, text, close);
      flash.remove();
      stack.appendChild(toast);
      var leave = function () {
        if (toast.classList.contains('is-leaving')) return;
        toast.classList.add('is-leaving');
        window.setTimeout(function () { toast.remove(); }, still ? 0 : 200);
      };
      close.addEventListener('click', leave);
      var timer = window.setTimeout(leave, 6000);
      toast.addEventListener('mouseenter', function () { window.clearTimeout(timer); });
      toast.addEventListener('mouseleave', function () { timer = window.setTimeout(leave, 2500); });
    });
  }

  // Stat figures count up the first time a page is opened in a session; after that they just show.
  var counted = false;
  try { counted = sessionStorage.getItem('count:' + location.pathname) === '1'; sessionStorage.setItem('count:' + location.pathname, '1'); } catch (e) {}
  if (!still && !counted) {
    document.querySelectorAll('.adm-stat__value').forEach(function (el) {
      var raw = el.textContent.trim();
      if (!/^[\d,]+$/.test(raw)) return; // only plain whole numbers; money and labels stay as written
      var target = parseInt(raw.replace(/,/g, ''), 10);
      if (!target) return;
      var start = performance.now(), duration = 650;
      el.style.minWidth = el.offsetWidth + 'px'; // no width jitter while digits change
      (function tick(now) {
        var t = Math.min(1, (now - start) / duration), eased = 1 - Math.pow(1 - t, 4);
        el.textContent = Math.round(target * eased).toLocaleString('en-GB');
        if (t < 1) requestAnimationFrame(tick); else el.textContent = raw;
      })(start);
    });
  }

  // Admin event switch: the thumb slides to the chosen event, then the form submits.
  document.querySelectorAll('[data-event-switch]').forEach(function (form) {
    var thumb = form.querySelector('.adm-event__thumb');
    var active = form.querySelector('.adm-event__opt.is-active');
    if (!thumb || !active) return;
    var place = function (opt) {
      form.style.setProperty('--thumb-x', (opt.offsetLeft) + 'px');
      form.style.setProperty('--thumb-w', opt.offsetWidth + 'px');
    };
    place(active);
    form.offsetWidth;
    form.classList.add('is-ready');
    window.addEventListener('resize', function () { place(form.querySelector('.adm-event__opt.is-active')); });
    form.querySelectorAll('.adm-event__opt').forEach(function (opt) {
      opt.addEventListener('click', function (event) {
        if (opt.classList.contains('is-active')) { event.preventDefault(); return; }
        event.preventDefault();
        form.querySelector('.adm-event__opt.is-active').classList.remove('is-active');
        opt.classList.add('is-active');
        place(opt);
        var field = document.createElement('input');
        field.type = 'hidden'; field.name = 'event'; field.value = opt.value;
        form.appendChild(field);
        window.setTimeout(function () { form.submit(); }, still ? 0 : 260);
      });
    });
  });

  // Row action menus: place the panel beside its button (fixed, so a scrolling table cannot clip it),
  // keep one open at a time, and close on Escape, an outside click or scroll.
  var menus = document.querySelectorAll('.ui-menu');
  var closeAll = function (except) { menus.forEach(function (m) { if (m !== except) m.removeAttribute('open'); }); };
  menus.forEach(function (menu) {
    var panel = menu.querySelector('.ui-menu__panel');
    var trigger = menu.querySelector('summary');
    menu.addEventListener('toggle', function () {
      if (!menu.open) return;
      closeAll(menu);
      var r = trigger.getBoundingClientRect();
      var width = panel.offsetWidth, height = panel.offsetHeight;
      var top = r.bottom + 6;
      if (top + height > window.innerHeight - 8) top = Math.max(8, r.top - height - 6); // open upwards near the bottom
      panel.style.top = top + 'px';
      panel.style.left = Math.max(8, Math.min(window.innerWidth - width - 8, r.right - width)) + 'px';
    });
  });
  document.addEventListener('click', function (e) { if (!e.target.closest('.ui-menu')) closeAll(null); });
  document.addEventListener('keydown', function (e) {
    if (e.key !== 'Escape') return;
    var open = document.querySelector('.ui-menu[open]');
    if (open) { open.removeAttribute('open'); open.querySelector('summary').focus(); }
  });
  window.addEventListener('scroll', function () { closeAll(null); }, { passive: true, capture: true });
})();
