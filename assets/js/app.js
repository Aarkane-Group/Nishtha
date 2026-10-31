/* Nishtha Global — vanilla JS: AJAX page navigation, menus, hero slider, accordion, gallery, enquiry form, analytics. */
(function () {
  'use strict';
  var NG = window.NG || {};
  var $ = function (sel, root) { return (root || document).querySelector(sel); };
  var $$ = function (sel, root) { return Array.prototype.slice.call((root || document).querySelectorAll(sel)); };
  var store = {
    get: function (k) { try { return localStorage.getItem(k); } catch (e) { return null; } },
    set: function (k, v) { try { localStorage.setItem(k, v); } catch (e) { /* ignore */ } }
  };
  var heroTimer;

  /* ================= Analytics + consent (one-time) ================= */
  var consent = function () { return store.get('analytics-consent'); };
  var gtag = function () { window.dataLayer = window.dataLayer || []; window.dataLayer.push(arguments); };
  var analyticsLoaded = false;
  function trackPageView() {
    if (window.gtag && NG.ga) {
      gtag('event', 'page_view', { page_path: location.pathname, page_title: document.title, page_location: location.href });
    }
  }
  function loadAnalytics() {
    if (analyticsLoaded) return;
    analyticsLoaded = true;
    if (NG.ga && /^[A-Za-z0-9-]+$/.test(NG.ga)) {
      var s = document.createElement('script');
      s.async = true; s.src = 'https://www.googletagmanager.com/gtag/js?id=' + encodeURIComponent(NG.ga);
      document.head.appendChild(s);
      gtag('js', new Date()); gtag('config', NG.ga, { send_page_view: false });
      window.gtag = gtag;
      trackPageView();
    }
    if (NG.clarity && /^[a-zA-Z0-9]+$/.test(NG.clarity)) {
      window.clarity = window.clarity || function () { (window.clarity.q = window.clarity.q || []).push(arguments); };
      var c = document.createElement('script');
      c.async = true; c.src = 'https://www.clarity.ms/tag/' + NG.clarity;
      document.head.appendChild(c);
    }
    if (NG.gtm && /^GTM-[A-Z0-9]+$/.test(NG.gtm)) {
      window.dataLayer = window.dataLayer || [];
      window.dataLayer.push({ 'gtm.start': Date.now(), event: 'gtm.js' });
      var g = document.createElement('script');
      g.async = true; g.src = 'https://www.googletagmanager.com/gtm.js?id=' + NG.gtm;
      document.head.appendChild(g);
    }
  }
  var banner = $('#cookie-consent');
  if (!NG.consentRequired || consent() === 'accepted') {
    loadAnalytics();
  } else if (banner && !consent()) {
    banner.classList.remove('hidden');
  }
  $$('[data-consent]', banner || document).forEach(function (btn) {
    btn.addEventListener('click', function () {
      store.set('analytics-consent', btn.getAttribute('data-consent'));
      banner.classList.add('hidden');
      if (btn.getAttribute('data-consent') === 'accepted') loadAnalytics();
    });
  });

  function trackEvent(type, label) {
    if (consent() === 'declined' || (NG.consentRequired && consent() !== 'accepted')) return;
    if (window.gtag) gtag('event', type, { event_category: 'engagement', event_label: label || location.pathname });
    if (window.clarity) window.clarity('event', type);
    try {
      fetch(NG.supabaseUrl + '/rest/v1/tracked_events', {
        method: 'POST', keepalive: true,
        headers: { apikey: NG.supabaseKey, 'Content-Type': 'application/json', Prefer: 'return=minimal' },
        body: JSON.stringify({ event_type: type, label: label || null, page_path: location.pathname.replace(NG.base || '', '') || '/', referrer: document.referrer || null })
      }).catch(function () {});
    } catch (e) { /* analytics must never break the journey */ }
  }
  document.addEventListener('click', function (ev) {
    var el = ev.target.closest && ev.target.closest('[data-track]');
    if (el) trackEvent(el.getAttribute('data-track'), el.getAttribute('data-label'));
  });

  /* ================= reCAPTCHA (script loaded once) ================= */
  function renderRecaptcha() {
    if (!NG.recaptchaKey) return;
    $$('[data-recaptcha]').forEach(function (box) {
      if (box.childElementCount || !window.grecaptcha || !window.grecaptcha.render) return;
      window.grecaptcha.ready(function () {
        if (!box.childElementCount) window.grecaptcha.render(box, { sitekey: NG.recaptchaKey });
      });
    });
  }
  var recaptchaScript;
  function ensureRecaptcha() {
    if (!NG.recaptchaKey || !$('[data-recaptcha]')) return;
    if (recaptchaScript) { renderRecaptcha(); return; }
    recaptchaScript = document.createElement('script');
    recaptchaScript.src = 'https://www.google.com/recaptcha/api.js?render=explicit';
    recaptchaScript.async = true; recaptchaScript.defer = true;
    recaptchaScript.onload = renderRecaptcha;
    document.head.appendChild(recaptchaScript);
  }

  /* ================= Per-page behaviour (re-run after every AJAX swap) ================= */
  function initPage() {
    /* Header: mobile menu */
    var toggle = $('[data-menu-toggle]');
    var mobileNav = $('[data-mobile-nav]');
    if (toggle && mobileNav) {
      toggle.addEventListener('click', function () {
        var open = mobileNav.classList.toggle('hidden') === false;
        toggle.setAttribute('aria-expanded', String(open));
        toggle.setAttribute('aria-label', open ? 'Close menu' : 'Open menu');
        $('[data-menu-open]', toggle).classList.toggle('hidden', open);
        $('[data-menu-close]', toggle).classList.toggle('hidden', !open);
      });
    }
    $$('[data-mobile-cat]').forEach(function (btn) {
      btn.addEventListener('click', function () {
        var open = btn.nextElementSibling.classList.toggle('hidden') === false;
        btn.setAttribute('aria-expanded', String(open));
        var svg = $('svg', btn);
        if (svg) svg.classList.toggle('rotate-180', open);
      });
    });

    /* Header: mega menu category tabs */
    $$('[data-megamenu]').forEach(function (mega) {
      var tabs = $$('[data-mega-tab]', mega);
      var panels = $$('[data-mega-panel]', mega);
      function activate(i) {
        tabs.forEach(function (t, idx) {
          var on = idx === i;
          t.classList.toggle('bg-background', on);
          t.classList.toggle('text-primary', on);
          t.classList.toggle('text-popover-foreground', !on);
          t.classList.toggle('hover:bg-background', !on);
        });
        panels.forEach(function (p, idx) { p.classList.toggle('hidden', idx !== i); });
      }
      tabs.forEach(function (t, i) {
        t.addEventListener('mouseenter', function () { activate(i); });
        t.addEventListener('focus', function () { activate(i); });
      });
    });

    /* Hero slider */
    clearInterval(heroTimer);
    var hero = $('[data-hero]');
    if (hero) {
      var imgs = $$('[data-hero-img]', hero);
      var dots = $$('[data-hero-dot]', hero);
      var meta = [];
      try { meta = JSON.parse($('[data-hero-slides]', hero).textContent); } catch (e) { /* ignore */ }
      var active = 0;
      var show = function (i) {
        active = (i + imgs.length) % imgs.length;
        imgs.forEach(function (img, idx) {
          img.classList.toggle('opacity-100', idx === active);
          img.classList.toggle('opacity-0', idx !== active);
          if (idx === active) img.removeAttribute('loading');
        });
        dots.forEach(function (d, idx) {
          var on = idx === active;
          d.classList.toggle('w-10', on); d.classList.toggle('bg-highlight', on);
          d.classList.toggle('w-5', !on); d.classList.toggle('bg-steel-foreground/50', !on);
        });
        if (meta[active]) {
          $('[data-hero-eyebrow]', hero).textContent = meta[active].eyebrow;
          $('[data-hero-title]', hero).textContent = meta[active].title;
          $('[data-hero-text]', hero).textContent = meta[active].text;
        }
      };
      var start = function () { clearInterval(heroTimer); heroTimer = setInterval(function () { show(active + 1); }, 6500); };
      dots.forEach(function (d, i) { d.addEventListener('click', function () { show(i); start(); }); });
      $('[data-hero-prev]', hero).addEventListener('click', function () { show(active - 1); start(); });
      $('[data-hero-next]', hero).addEventListener('click', function () { show(active + 1); start(); });
      if (imgs.length > 1) start();
    }

    /* Accordion (single, collapsible) */
    $$('[data-accordion]').forEach(function (acc) {
      var triggers = $$('[data-acc-trigger]', acc);
      triggers.forEach(function (btn) {
        btn.addEventListener('click', function () {
          var panel = btn.closest('div').querySelector('[data-acc-panel]');
          var willOpen = panel.classList.contains('hidden');
          triggers.forEach(function (b) {
            b.closest('div').querySelector('[data-acc-panel]').classList.add('hidden');
            b.setAttribute('aria-expanded', 'false');
            $('svg', b).classList.remove('rotate-180');
          });
          if (willOpen) {
            panel.classList.remove('hidden');
            btn.setAttribute('aria-expanded', 'true');
            $('svg', btn).classList.add('rotate-180');
          }
        });
      });
    });

    /* Product image gallery */
    $$('[data-gallery]').forEach(function (g) {
      var main = $('[data-gallery-main]', g);
      var thumbs = $$('[data-gallery-thumb]', g);
      thumbs.forEach(function (t) {
        t.addEventListener('click', function () {
          main.src = t.getAttribute('data-gallery-thumb');
          thumbs.forEach(function (o) {
            o.classList.toggle('border-primary', o === t);
            o.classList.toggle('border-border', o !== t);
          });
        });
      });
    });

    /* Enquiry form (AJAX submit) */
    var params = new URLSearchParams(location.search);
    $$('[data-lead-wrap]').forEach(function (wrap) {
      var form = $('[data-lead-form]', wrap);
      var done = $('[data-lead-done]', wrap);
      var err = $('[data-lead-error]', wrap);
      var btn = $('button[type=submit]', form);
      var sent = btn.textContent;
      form.elements.referrer.value = document.referrer || '';
      ['utm_source', 'utm_medium', 'utm_campaign'].forEach(function (k) { form.elements[k].value = params.get(k) || ''; });
      var opened = false;
      form.addEventListener('focusin', function () {
        if (!opened) { opened = true; trackEvent('quote_form_open', form.elements.source_page.value); }
      });
      form.addEventListener('submit', function (ev) {
        ev.preventDefault();
        err.classList.add('hidden');
        btn.disabled = true; btn.textContent = 'Sending…';
        fetch(form.action, { method: 'POST', body: new FormData(form), headers: { Accept: 'application/json' } })
          .then(function (r) { return r.json().catch(function () { return { ok: false }; }); })
          .then(function (res) {
            if (!res.ok) throw new Error(res.error || "We couldn't send your enquiry. Please call us instead.");
            trackEvent('lead_submitted', form.elements.source_page.value);
            form.reset();
            if (window.grecaptcha) try { window.grecaptcha.reset(); } catch (e) { /* ignore */ }
            form.classList.add('hidden'); done.classList.remove('hidden');
          })
          .catch(function (e) { err.textContent = e.message; err.classList.remove('hidden'); })
          .finally(function () { btn.disabled = false; btn.textContent = sent; });
      });
      $('[data-lead-again]', wrap).addEventListener('click', function () {
        done.classList.add('hidden'); form.classList.remove('hidden');
      });
    });
    ensureRecaptcha();
  }

  /* ================= AJAX navigation ================= */
  var app = $('#app');
  var bar = document.createElement('div');
  bar.className = 'fixed left-0 top-0 z-[60] h-0.5 w-0 bg-highlight opacity-0 transition-all duration-300';
  document.body.appendChild(bar);
  var barTimer;
  function progress(state) {
    clearTimeout(barTimer);
    if (state === 'start') {
      bar.classList.remove('opacity-0', 'w-full'); bar.classList.add('opacity-100', 'w-2/3');
    } else {
      bar.classList.remove('w-2/3'); bar.classList.add('w-full');
      barTimer = setTimeout(function () { bar.classList.remove('opacity-100', 'w-full'); bar.classList.add('opacity-0', 'w-0'); }, 250);
    }
  }

  var ajaxOk = !!(window.fetch && window.history && history.pushState && window.DOMParser && app);
  var current = 0;      // guards against out-of-order responses
  var cache = {};       // path -> {html, t}
  var CACHE_MS = 30000;

  /* Same-origin, inside the app base path, not a file/asset/endpoint. */
  function isAjaxLink(a, ev) {
    if (!a || !a.href || a.target === '_blank' || a.hasAttribute('download') || a.hasAttribute('data-no-ajax')) return false;
    if (ev && (ev.defaultPrevented || ev.button !== 0 || ev.metaKey || ev.ctrlKey || ev.shiftKey || ev.altKey)) return false;
    var u;
    try { u = new URL(a.href, location.href); } catch (e) { return false; }
    if (u.origin !== location.origin) return false;
    var base = NG.base || '';
    if (base && u.pathname !== base && u.pathname.indexOf(base + '/') !== 0) return false;
    var rel = u.pathname.slice(base.length) || '/';
    if (/^\/(enquiry|sitemap\.xml|robots\.txt|assets|images|storage|app|admin|auth|api)(\/|$)/.test(rel) || /\.[a-z0-9]{2,5}$/i.test(rel)) return false;
    if (u.pathname === location.pathname && u.search === location.search && u.hash) return false; // in-page anchor
    return true;
  }

  function swapHead(doc) {
    document.title = doc.title;
    $$('meta[name=description],meta[name=robots],meta[name=keywords],meta[property^="og:"],meta[name^="twitter:"],link[rel=canonical],script[type="application/ld+json"]').forEach(function (n) { n.remove(); });
    $$('meta[name=description],meta[name=robots],meta[name=keywords],meta[property^="og:"],meta[name^="twitter:"],link[rel=canonical],script[type="application/ld+json"]', doc.head).forEach(function (n) {
      var copy = document.createElement(n.tagName);
      for (var i = 0; i < n.attributes.length; i++) copy.setAttribute(n.attributes[i].name, n.attributes[i].value);
      if (n.tagName === 'SCRIPT') copy.textContent = n.textContent;
      document.head.appendChild(copy);
    });
  }

  function render(html, finalUrl, push, hash) {
    var doc = new DOMParser().parseFromString(html, 'text/html');
    var next = doc.getElementById('app');
    if (!next) { location.href = finalUrl; return; }
    swapHead(doc);
    app.innerHTML = next.innerHTML;
    if (push === 'push') history.pushState({ ajax: 1 }, '', finalUrl);
    else if (push === 'replace') history.replaceState({ ajax: 1 }, '', finalUrl);
    initPage();
    trackPageView();
    var target = hash && document.getElementById(decodeURIComponent(hash.slice(1)));
    if (target) target.scrollIntoView(); else if (push !== 'pop') window.scrollTo(0, 0);
  }

  function go(url, push) {
    var u = new URL(url, location.href);
    var key = u.pathname + u.search;
    var id = ++current;
    var hit = cache[key];
    if (hit && Date.now() - hit.t < CACHE_MS) { render(hit.html, hit.url, push, u.hash); return; }
    progress('start');
    fetch(u.pathname + u.search, { headers: { 'X-Requested-With': 'fetch', Accept: 'text/html' }, credentials: 'same-origin' })
      .then(function (r) {
        var type = r.headers.get('Content-Type') || '';
        if (type.indexOf('text/html') === -1) { location.href = u.href; return null; }
        return r.text().then(function (html) { return { html: html, url: r.url, ok: r.ok }; });
      })
      .then(function (res) {
        if (!res || id !== current) return;
        var final = new URL(res.url);
        if (res.ok) cache[key] = { html: res.html, url: res.url, t: Date.now() };
        // A server redirect (e.g. legacy URL) lands on a different path — show that URL, keep the hash.
        render(res.html, final.pathname + final.search + (u.hash || final.hash), push, u.hash || final.hash);
      })
      .catch(function () { location.href = u.href; })
      .finally(function () { if (id === current) progress('end'); });
  }

  if (ajaxOk) {
    history.replaceState({ ajax: 1 }, '', location.href);
    document.addEventListener('click', function (ev) {
      var a = ev.target.closest && ev.target.closest('a');
      if (!isAjaxLink(a, ev)) return;
      ev.preventDefault();
      go(a.href, 'push');
    });
    window.addEventListener('popstate', function () { go(location.href, 'pop'); });
    // Warm the cache when the pointer rests on a link so the click feels instant.
    var hoverTimer;
    document.addEventListener('mouseover', function (ev) {
      var a = ev.target.closest && ev.target.closest('a');
      if (!isAjaxLink(a)) return;
      var u = new URL(a.href, location.href), key = u.pathname + u.search;
      if (cache[key] && Date.now() - cache[key].t < CACHE_MS) return;
      clearTimeout(hoverTimer);
      hoverTimer = setTimeout(function () {
        fetch(key, { headers: { 'X-Requested-With': 'fetch', Accept: 'text/html' } })
          .then(function (r) { return (r.ok && (r.headers.get('Content-Type') || '').indexOf('text/html') > -1) ? r.text().then(function (html) { cache[key] = { html: html, url: r.url, t: Date.now() }; }) : null; })
          .catch(function () {});
      }, 120);
    });
    document.addEventListener('mouseout', function () { clearTimeout(hoverTimer); });
  }

  initPage();
})();
