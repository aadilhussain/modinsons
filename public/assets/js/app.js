/* Modi And Sons — light front-end behaviour. No dependencies. */
(function () {
  'use strict';
  var $ = function (s, c) { return (c || document).querySelector(s); };

  /* header shadow + floating actions */
  var hdr = $('#hdr'), fabs = $('#fabs'), t = false;
  function onScroll() {
    var y = window.scrollY;
    if (hdr) hdr.classList.toggle('stuck', y > 8);
    if (fabs) fabs.classList.toggle('show', y > 400);
    t = false;
  }
  window.addEventListener('scroll', function () {
    if (!t) { t = true; requestAnimationFrame(onScroll); }
  }, { passive: true });
  onScroll();

  /* mobile nav */
  var b = $('#burger'), m = $('#mobnav');
  if (b && m) {
    b.addEventListener('click', function () {
      var open = b.classList.toggle('x');
      m.classList.toggle('open', open);
      b.setAttribute('aria-expanded', open ? 'true' : 'false');
    });
  }

  /* reveal on scroll */
  var rv = [].slice.call(document.querySelectorAll('.rv'));
  if (rv.length) {
    if (!('IntersectionObserver' in window) || matchMedia('(prefers-reduced-motion: reduce)').matches) {
      rv.forEach(function (e) { e.classList.add('in'); });
    } else {
      var io = new IntersectionObserver(function (en) {
        en.forEach(function (e) { if (e.isIntersecting) { e.target.classList.add('in'); io.unobserve(e.target); } });
      }, { threshold: 0.08, rootMargin: '0px 0px -5% 0px' });
      rv.forEach(function (e) { io.observe(e); });
    }
  }

  /* animated counters */
  var nums = [].slice.call(document.querySelectorAll('[data-count]'));
  if (nums.length) {
    var fmt = function (n) { return n.toLocaleString('en-IN'); };
    if (!('IntersectionObserver' in window) || matchMedia('(prefers-reduced-motion: reduce)').matches) {
      nums.forEach(function (e) { e.textContent = fmt(+e.dataset.count) + (e.dataset.suffix || ''); });
    } else {
      var io2 = new IntersectionObserver(function (en) {
        en.forEach(function (e) {
          if (!e.isIntersecting) return;
          var el = e.target, target = +el.dataset.count, sfx = el.dataset.suffix || '', t0 = null;
          (function step(ts) {
            if (!t0) t0 = ts;
            var p = Math.min((ts - t0) / 1400, 1);
            el.textContent = fmt(Math.round(target * (1 - Math.pow(1 - p, 3)))) + sfx;
            if (p < 1) requestAnimationFrame(step);
          })(performance.now());
          io2.unobserve(el);
        });
      }, { threshold: 0.5 });
      nums.forEach(function (e) { io2.observe(e); });
    }
  }

  /* auto-submit catalogue filters */
  [].slice.call(document.querySelectorAll('[data-autosubmit]')).forEach(function (el) {
    el.addEventListener('change', function () { el.form && el.form.submit(); });
  });

  /* confirm destructive actions */
  document.addEventListener('submit', function (e) {
    var f = e.target;
    if (f.matches('[data-confirm]') && !window.confirm(f.getAttribute('data-confirm'))) {
      e.preventDefault();
    }
  });

  /* admin: repeatable specification rows */
  var specs = $('#specRows');
  if (specs) {
    var addBtn = $('#addSpec');
    addBtn && addBtn.addEventListener('click', function () {
      var row = specs.firstElementChild.cloneNode(true);
      [].slice.call(row.querySelectorAll('input')).forEach(function (i) { i.value = ''; });
      specs.appendChild(row);
    });
    specs.addEventListener('click', function (e) {
      if (e.target.closest('.rm-spec') && specs.children.length > 1) {
        e.target.closest('.spec-row').remove();
      }
    });
  }

  /* admin: image preview before upload */
  var img = $('#imageInput'), prev = $('#imagePreview');
  if (img && prev) {
    img.addEventListener('change', function () {
      var f = img.files && img.files[0];
      if (f) { prev.src = URL.createObjectURL(f); prev.style.display = 'block'; }
    });
  }
})();
