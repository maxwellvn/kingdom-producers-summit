/* Status notices fade out on their own. Errors (role="alert") stay until acted on. */
(function () {
  var LINGER = 6000;
  document.querySelectorAll('[role="status"]').forEach(function (notice) {
    if (notice.closest('[data-comments]') || notice.closest('.watch__stage')) return; // live regions, not toasts
    var timer;
    var dismiss = function () {
      notice.classList.add('is-leaving');
      notice.addEventListener('transitionend', function () { notice.remove(); }, { once: true });
      setTimeout(function () { if (notice.parentNode) notice.remove(); }, 700);
    };
    var arm = function () { clearTimeout(timer); timer = setTimeout(dismiss, LINGER); };
    notice.addEventListener('mouseenter', function () { clearTimeout(timer); });
    notice.addEventListener('mouseleave', arm);
    arm();
  });
})();
