/* FORMA — lightweight vanilla JS: nav, program tabs, schedule filters, FAQ, trial + newsletter forms. */
(function () {
  'use strict';
  var $ = function (s, c) { return (c || document).querySelector(s); };
  var $$ = function (s, c) { return Array.prototype.slice.call((c || document).querySelectorAll(s)); };

  /* Sticky nav + mobile toggle */
  var nav = $('#site-nav');
  if (nav) {
    var onScroll = function () { nav.classList.toggle('is-stuck', window.scrollY > 40); };
    onScroll(); window.addEventListener('scroll', onScroll, { passive: true });
    var tg = $('.nav-toggle', nav);
    var setOpen = function (o) { nav.classList.toggle('is-open', o); tg.setAttribute('aria-expanded', String(o)); };
    tg.addEventListener('click', function () { setOpen(tg.getAttribute('aria-expanded') !== 'true'); });
    nav.addEventListener('click', function (e) { if (e.target.closest('.nav-panel a')) setOpen(false); });
    document.addEventListener('keydown', function (e) { if (e.key === 'Escape' && nav.classList.contains('is-open')) { setOpen(false); tg.focus(); } });
  }

  /* Program tabs (WAI-ARIA, roving tabindex, no reload) */
  $$('[data-forma-tabs]').forEach(function (root) {
    var tabs = $$('[role=tab]', root), panels = $$('[role=tabpanel]', root);
    function select(i, focus) {
      tabs.forEach(function (t, n) {
        var on = n === i;
        t.classList.toggle('on', on); t.setAttribute('aria-selected', String(on)); t.tabIndex = on ? 0 : -1;
        panels[n].hidden = !on;
      });
      if (focus) tabs[i].focus();
      tabs[i].scrollIntoView({ block: 'nearest', inline: 'nearest' });
    }
    tabs.forEach(function (t, i) {
      t.addEventListener('click', function () { select(i); });
      t.addEventListener('keydown', function (e) {
        var n = tabs.length, k = e.key, j = null;
        if (k === 'ArrowRight') j = (i + 1) % n; else if (k === 'ArrowLeft') j = (i - 1 + n) % n;
        else if (k === 'Home') j = 0; else if (k === 'End') j = n - 1;
        if (j !== null) { e.preventDefault(); select(j, true); }
      });
    });
  });

  /* Schedule filters: operate on data-* attributes rendered from SCF rows */
  $$('[data-forma-schedule]').forEach(function (root) {
    var rows = $$('.sched-list .row', root), empty = $('.sched-empty', root);
    var state = { day: ($('.day.on', root) || {}).dataset ? $('.day.on', root).dataset.day : 'Mon', program: 'all' };
    function apply() {
      var shown = 0;
      rows.forEach(function (r) {
        var ok = r.dataset.day === state.day && (state.program === 'all' || r.dataset.program === state.program);
        r.hidden = !ok; if (ok) shown++;
      });
      empty.hidden = shown !== 0;
    }
    function group(sel, key) {
      $$(sel, root).forEach(function (b) {
        b.addEventListener('click', function () {
          $$(sel, root).forEach(function (o) { var on = o === b; o.classList.toggle('on', on); o.setAttribute('aria-pressed', String(on)); });
          state[key] = key === 'day' ? b.dataset.day : b.dataset.program; apply();
        });
      });
    }
    group('.day', 'day'); group('.chip', 'program'); apply();
  });

  /* FAQ accordion: real buttons, aria-expanded, Enter/Space native; arrows move between questions */
  $$('[data-forma-faq]').forEach(function (root) {
    var qs = $$('.faq-q', root);
    qs.forEach(function (q, i) {
      q.addEventListener('click', function () {
        var open = q.getAttribute('aria-expanded') === 'true';
        q.setAttribute('aria-expanded', String(!open));
        $('#' + q.getAttribute('aria-controls')).hidden = open;
        $('i', q).textContent = open ? '+' : '−';
      });
      q.addEventListener('keydown', function (e) {
        var j = null;
        if (e.key === 'ArrowDown') j = (i + 1) % qs.length; else if (e.key === 'ArrowUp') j = (i - 1 + qs.length) % qs.length;
        else if (e.key === 'Home') j = 0; else if (e.key === 'End') j = qs.length - 1;
        if (j !== null) { e.preventDefault(); qs[j].focus(); }
      });
    });
  });

  /* Forms */
  function post(url, data) {
    return fetch(url, { method: 'POST', headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': (window.FORMA || {}).nonce || '' }, body: JSON.stringify(data) })
      .then(function (r) { return r.json().then(function (j) { j._status = r.status; return j; }); });
  }
  var emailRe = /^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/;
  function validate(f) {
    var e = {}, v = function (n) { return (f.elements[n].value || '').trim(); };
    if (v('first_name').length < 2) e.first_name = 'Enter your first name.';
    if (v('last_name').length < 2) e.last_name = 'Enter your last name.';
    if (!emailRe.test(v('email'))) e.email = 'Enter a valid email address.';
    var d = v('phone').replace(/\D/g, '');
    if (!/^\+?[\d\s().\-]{7,20}$/.test(v('phone')) || d.length < 7 || d.length > 15) e.phone = 'Enter a valid phone number.';
    if (!v('goal')) e.goal = 'Choose a goal.';
    return e;
  }
  function showErrors(f, errs) {
    ['first_name', 'last_name', 'email', 'phone', 'goal'].forEach(function (n) {
      var input = f.elements[n], idMap = { first_name: 'fm-first', last_name: 'fm-last', email: 'fm-email', phone: 'fm-phone', goal: 'fm-goal' };
      var out = document.getElementById(idMap[n] + '-err');
      if (!out) return;
      out.textContent = errs[n] || '';
      if (errs[n]) input.setAttribute('aria-invalid', 'true'); else input.removeAttribute('aria-invalid');
    });
  }
  $$('[data-forma-form]').forEach(function (f) {
    var box = $('.form-error', f), wrap = f.parentNode, ok = $('.trial-success', wrap), btn = $('button[type=submit]', f);
    $$('input,select', f).forEach(function (i) { i.addEventListener('input', function () { if (i.getAttribute('aria-invalid')) { i.removeAttribute('aria-invalid'); var o = document.getElementById(i.id + '-err'); if (o) o.textContent = ''; } }); });
    f.addEventListener('submit', function (ev) {
      ev.preventDefault(); box.hidden = true;
      var errs = validate(f); showErrors(f, errs);
      var keys = Object.keys(errs);
      if (keys.length) { box.textContent = 'Please fix ' + keys.length + (keys.length > 1 ? ' fields' : ' field') + ' below.'; box.hidden = false; f.elements[keys[0]].focus(); return; }
      var data = {}; $$('input,select', f).forEach(function (i) { if (i.type === 'radio' && !i.checked) return; if (i.name) data[i.name] = i.value; });
      btn.disabled = true;
      post(FORMA.rest, data).then(function (res) {
        btn.disabled = false;
        if (res.ok) { f.hidden = true; ok.hidden = false; ok.focus(); return; }
        if (res.errors) { showErrors(f, res.errors); box.textContent = 'Please fix the highlighted fields.'; }
        else box.textContent = res.message || 'Something went wrong. Please try again.';
        box.hidden = false;
      }).catch(function () { btn.disabled = false; box.textContent = 'Network error. Check your connection and try again.'; box.hidden = false; });
    });
  });
  $$('[data-forma-news]').forEach(function (f) {
    var msg = $('.news-msg', f), inp = $('input', f);
    f.addEventListener('submit', function (ev) {
      ev.preventDefault(); msg.className = 'news-msg';
      if (!emailRe.test(inp.value.trim())) { msg.textContent = 'Enter a valid email address.'; msg.classList.add('bad'); inp.setAttribute('aria-invalid', 'true'); inp.focus(); return; }
      inp.removeAttribute('aria-invalid');
      post(FORMA.rest.replace('/trial', '/newsletter'), { email: inp.value.trim() }).then(function (r) {
        if (r.ok) { msg.textContent = 'Thanks. You are on the list.'; f.reset(); } else { msg.textContent = (r.errors && r.errors.email) || r.message || 'Could not subscribe.'; msg.classList.add('bad'); }
      }).catch(function () { msg.textContent = 'Network error.'; msg.classList.add('bad'); });
    });
  });
})();
