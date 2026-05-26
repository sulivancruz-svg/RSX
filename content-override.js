/* RSX Travel — CMS content override. Included in every page. */
(function () {
  'use strict';
  var KEY = 'rsx_cms_content';

  function load() {
    try { return JSON.parse(localStorage.getItem(KEY) || '{}'); }
    catch (e) { return {}; }
  }

  function pageId() {
    var f = window.location.pathname.split('/').pop().replace(/\.html$/, '');
    return f === '' ? 'index' : (f || 'index');
  }

  var RESORT_FIELDS = [
    { k: 'hero_img',         s: '.page-hero',                         t: 'bg'   },
    { k: 'hero_title',       s: '.page-hero-title',                   t: 'html' },
    { k: 'hero_sub',         s: '.page-hero-sub',                     t: 'html' },
    { k: 'regime',           s: '.page-hero-badge',                   t: 'badge'},
    { k: 'overview_h2',      s: '.overview-text .section-h2',         t: 'html' },
    { k: 'overview_p1',      s: '.overview-text > p:nth-of-type(1)',  t: 'html' },
    { k: 'overview_p2',      s: '.overview-text > p:nth-of-type(2)',  t: 'html' },
    { k: 'overview_p3',      s: '.overview-text > p:nth-of-type(3)',  t: 'html' },
    { k: 'experience_quote', s: '.experience-quote',                  t: 'html' },
    { k: 'gallery_1',        s: '.gallery-item:nth-child(1)',         t: 'bg'   },
    { k: 'gallery_2',        s: '.gallery-item:nth-child(2)',         t: 'bg'   },
    { k: 'gallery_3',        s: '.gallery-item:nth-child(3)',         t: 'bg'   },
    { k: 'gallery_4',        s: '.gallery-item:nth-child(4)',         t: 'bg'   },
    { k: 'gallery_5',        s: '.gallery-item:nth-child(5)',         t: 'bg'   },
  ];

  var FIELDS = {
    index:       [{ k: 'hero_video', s: '#heroIframe', t: 'yt' }],
    costao:      RESORT_FIELDS,
    fazzenda:    RESORT_FIELDS,
    japaratinga: RESORT_FIELDS,
    sobre: [
      { k: 'hero_h1',     s: '.sobre-hero h1',               t: 'html' },
      { k: 'bio_p1',      s: '.bio-text > p:nth-of-type(1)', t: 'html' },
      { k: 'bio_p2',      s: '.bio-text > p:nth-of-type(2)', t: 'html' },
      { k: 'bio_p3',      s: '.bio-text > p:nth-of-type(3)', t: 'html' },
      { k: 'profile_img', s: '.bio-portrait-img',             t: 'bg'   },
    ],
    contato: [],
  };

  function ytEmbed(url) {
    var m = (url || '').match(/(?:youtu\.be\/|youtube\.com\/(?:watch\?v=|embed\/|shorts\/))([^&\s?\/]+)/);
    var id = m ? m[1] : url;
    return 'https://www.youtube-nocookie.com/embed/' + id +
      '?autoplay=1&mute=1&loop=1&playlist=' + id +
      '&controls=0&showinfo=0&rel=0&iv_load_policy=3&modestbranding=1&playsinline=1&enablejsapi=0&cc_load_policy=0';
  }

  function applyField(f, val) {
    if (!val) return;
    var el = document.querySelector(f.s);
    if (!el) return;
    if (f.t === 'html') {
      el.innerHTML = val;
    } else if (f.t === 'badge') {
      var parts = el.textContent.split('·');
      parts[0] = ' ' + val + ' ';
      el.textContent = parts.join('·');
    } else if (f.t === 'bg') {
      el.style.backgroundImage = 'url("' + val + '")';
      el.style.backgroundSize  = 'cover';
      el.style.backgroundPosition = 'center center';
      var ph = el.querySelector('.gallery-placeholder,.bio-portrait-placeholder');
      if (ph) ph.style.display = 'none';
    } else if (f.t === 'yt') {
      el.src = ytEmbed(val);
    }
  }

  /* ── Atualiza os cards dos resorts na página inicial ── */
  function applyIndexCards(data) {
    var resorts = ['costao', 'fazzenda', 'japaratinga'];
    resorts.forEach(function(pg) {
      var d = data.pages && data.pages[pg];
      if (!d) return;
      var card = document.querySelector('a.tour-card[href="' + pg + '.html"]');
      if (!card) return;

      // Regime → badge + meta
      if (d.regime) {
        var badge = card.querySelector('.tour-badge');
        if (badge) badge.textContent = d.regime;
        var metaLast = card.querySelector('.tour-meta span:last-child');
        if (metaLast) metaLast.textContent = d.regime;
      }
      // Tipo → badge (só se não tiver regime)
      if (d.tipo && !d.regime) {
        var badge2 = card.querySelector('.tour-badge');
        if (badge2) badge2.textContent = d.tipo;
      }
      // Descrição curta do card
      if (d.card_desc) {
        var desc = card.querySelector('.tour-desc');
        if (desc) desc.textContent = d.card_desc;
      }
      // Imagem do card
      if (d.card_img) {
        var imgInner = card.querySelector('.tour-img-inner');
        if (imgInner) {
          imgInner.style.backgroundImage = 'url("' + d.card_img + '")';
          imgInner.style.backgroundSize = 'cover';
          imgInner.style.backgroundPosition = 'center';
          var ph2 = imgInner.querySelector('.tour-img-placeholder');
          if (ph2) ph2.style.display = 'none';
        }
      }
    });
  }

  /* ── Injeta cards de novos destinos criados no admin ── */
  function applyCustomCards(data) {
    var slot = document.getElementById('custom-resorts-slot');
    if (!slot) return;
    try {
      var custom = JSON.parse(localStorage.getItem('rsx_custom_pages') || '[]');
      if (!custom.length) return;
      var pages = data.pages || {};
      slot.innerHTML = custom.map(function(p) {
        var d = pages[p.slug] || {};
        var regime  = d.regime  || p.regime  || 'Resort';
        var tipo    = d.tipo    || p.tipo    || regime;
        var loc     = p.localizacao || 'Brasil';
        var desc    = d.card_desc || p.desc || 'Descubra este destino com a curadoria RSX Travel.';
        var imgStyle = d.card_img
          ? 'background-image:url("' + d.card_img + '");background-size:cover;background-position:center'
          : 'background:linear-gradient(150deg,#1a3040,#081828)';
        return '<a href="' + p.slug + '.html" class="tour-card">' +
          '<div class="tour-img">' +
          '<div class="tour-badge">' + regime + '</div>' +
          '<div class="tour-img-inner" style="' + imgStyle + '">' +
          (d.card_img ? '' : '<p class="tour-img-placeholder">' + p.nome + '</p>') + '</div>' +
          '<div class="tour-bookmark"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M19 21l-7-5-7 5V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2z"/></svg></div>' +
          '</div><div class="tour-body">' +
          '<div class="tour-meta"><span>' + loc + '</span><span>' + regime + '</span></div>' +
          '<h3 class="tour-name">' + p.nome + '</h3>' +
          '<p class="tour-desc">' + desc + '</p>' +
          '<div class="tour-cta-row"><span class="tour-cta-label">Conhecer o resort</span>' +
          '<div class="tour-cta-arrow"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 12h14M12 5l7 7-7 7"/></svg></div>' +
          '</div></div></a>';
      }).join('');
    } catch(e) {}
  }

  function run() {
    var data = load();

    // WhatsApp global
    var wa = data.global && data.global.whatsapp;
    if (wa) {
      document.querySelectorAll('a[href*="wa.me"]').forEach(function (a) {
        a.href = a.href.replace(/wa\.me\/\d+/, 'wa.me/' + wa);
      });
    }

    var pid = pageId();

    // Página inicial: atualiza cards dos resorts existentes + injeta novos
    if (pid === 'index') {
      applyIndexCards(data);
      applyCustomCards(data);
    }

    // Aplica campos da página atual
    var pageData = data.pages && data.pages[pid];
    if (!pageData) return;
    var fields = FIELDS[pid];
    if (!fields && document.querySelector('.page-hero') && document.querySelector('.gallery-grid')) {
      fields = RESORT_FIELDS;
    }
    (fields || []).forEach(function (f) { applyField(f, pageData[f.k]); });
  }

  document.readyState === 'loading'
    ? document.addEventListener('DOMContentLoaded', run)
    : run();
}());
