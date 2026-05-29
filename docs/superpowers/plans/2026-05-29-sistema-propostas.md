# Sistema de Propostas de Viagem — Plano de Implementação

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Adicionar ao site RSX Travel um sistema de propostas de viagem onde a Rebeca cria cotações personalizadas via wizard no admin e gera um link público único por cliente.

**Architecture:** PHP + JSON estático no cPanel, seguindo o padrão existente de `cms-save.php`/`cms-data.json`. Novos arquivos: `proposals-save.php` (CRUD), `proposals.json` (banco de dados), `proposta.html` (página pública). Admin (`admin.html`) ganha aba "Propostas" com lista + wizard de 6 passos.

**Tech Stack:** HTML, CSS, JS (vanilla), PHP 7+, cPanel Git Version Control.

---

## Mapa de Arquivos

| Arquivo | Ação | Responsabilidade |
|---|---|---|
| `proposals-save.php` | Criar | CRUD de propostas (GET retorna JSON, POST salva/deleta) |
| `proposals.json` | Criar | Banco de dados local das propostas (nunca sobrescrito no deploy) |
| `proposta.html` | Criar | Página pública da proposta — render client-side via ?id= |
| `admin.html` | Modificar | +sidebar "Propostas", +PROPOSALS engine, +WIZ object, +PAGES.propostas() |
| `.cpanel.yml` | Modificar | Incluir novos arquivos no deploy |

---

## Task 1: Backend — `proposals-save.php` + `proposals.json`

**Files:**
- Create: `proposals-save.php`
- Create: `proposals.json`

- [ ] **Step 1: Criar `proposals.json` com estrutura inicial**

```json
{"proposals":{}}
```

Salvar em: `C:\Users\suliv\OneDrive\Área de Trabalho\REBECA\proposals.json`

- [ ] **Step 2: Criar `proposals-save.php`**

```php
<?php
/**
 * RSX Travel — Proposals CRUD Endpoint
 * GET  → retorna proposals.json completo
 * POST → { _pass, action:"save"|"delete", proposal?:{...}, id?:"..." }
 */
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }

$DATA_FILE = __DIR__ . '/proposals.json';
$PASS_FILE = __DIR__ . '/cms-pass.txt';

if (!file_exists($DATA_FILE)) {
    @file_put_contents($DATA_FILE, '{"proposals":{}}');
    @chmod($DATA_FILE, 0664);
}
if (!is_writable($DATA_FILE)) { @chmod($DATA_FILE, 0664); }

function getPass($f) {
    if (file_exists($f)) { $p = trim(file_get_contents($f)); if ($p) return $p; }
    return 'rsx2024';
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    echo file_exists($DATA_FILE) ? file_get_contents($DATA_FILE) : '{"proposals":{}}';
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $body = json_decode(file_get_contents('php://input'), true);
    if (!$body) { http_response_code(400); echo json_encode(['ok'=>false,'error'=>'Invalid JSON']); exit; }

    if (($body['_pass'] ?? '') !== getPass($PASS_FILE)) {
        http_response_code(401); echo json_encode(['ok'=>false,'error'=>'Unauthorized']); exit;
    }

    $current = json_decode(file_get_contents($DATA_FILE), true) ?: ['proposals'=>[]];

    $action = $body['action'] ?? '';
    if ($action === 'save') {
        $p = $body['proposal'];
        if (empty($p['id'])) { http_response_code(400); echo json_encode(['ok'=>false,'error'=>'Missing id']); exit; }
        $current['proposals'][$p['id']] = $p;
    } elseif ($action === 'delete') {
        $id = $body['id'] ?? '';
        if ($id) unset($current['proposals'][$id]);
    } else {
        http_response_code(400); echo json_encode(['ok'=>false,'error'=>'Unknown action']); exit;
    }

    $json = json_encode($current, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    if (file_put_contents($DATA_FILE, $json) === false) {
        http_response_code(500); echo json_encode(['ok'=>false,'error'=>'Write failed']); exit;
    }
    echo json_encode(['ok'=>true]);
    exit;
}

http_response_code(405);
echo json_encode(['ok'=>false,'error'=>'Method not allowed']);
```

- [ ] **Step 3: Verificar o endpoint localmente**

Abra `admin.html` no browser. Abra DevTools → Console e rode:
```js
fetch('proposals-save.php').then(r=>r.json()).then(console.log)
```
Esperado: `{proposals: {}}` sem erro de rede (pode dar erro de CORS em file://, normal — vai funcionar no servidor).

- [ ] **Step 4: Commit**

```bash
git add proposals-save.php proposals.json
git commit -m "feat: add proposals CRUD backend"
```

---

## Task 2: Página Pública — `proposta.html`

**Files:**
- Create: `proposta.html`

A página lê `?id=` da URL, busca `proposals-save.php`, renderiza a proposta. Visual dark/luxo idêntico ao site RSX.

- [ ] **Step 1: Criar `proposta.html`**

```html
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width,initial-scale=1.0"/>
<title>Proposta de Viagem — RSX Travel</title>
<meta name="robots" content="noindex,nofollow"/>
<link rel="preconnect" href="https://fonts.googleapis.com"/>
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin/>
<link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400;0,500;1,300;1,400&family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet"/>
<style>
:root{
  --navy-950:#080f1f;--navy-900:#0e1a35;--navy-800:#1a2847;--navy-700:#233357;
  --gold-600:#a07840;--gold-500:#b8935c;--gold-400:#c9a574;--gold-300:#dcc093;--gold-200:#edda9f;
  --cream:#f5efe6;--line:rgba(201,165,116,.18);
}
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
html{scroll-behavior:smooth;font-size:16px}
body{font-family:'Inter',sans-serif;background:var(--navy-950);color:var(--cream);-webkit-font-smoothing:antialiased;min-height:100vh}
a{color:inherit;text-decoration:none}

/* NAV */
.topbar{display:flex;align-items:center;justify-content:space-between;padding:20px 32px;border-bottom:1px solid var(--line)}
.topbar-logo{font-family:'Cormorant Garamond',serif;font-size:18px;letter-spacing:.2em;color:var(--gold-300)}
.topbar-tag{font-size:10px;letter-spacing:.2em;text-transform:uppercase;color:rgba(245,239,230,.4)}

/* HERO */
.hero{position:relative;min-height:420px;display:flex;align-items:flex-end;overflow:hidden}
.hero-bg{position:absolute;inset:0;background-size:cover;background-position:center;background-color:var(--navy-800)}
.hero-overlay{position:absolute;inset:0;background:linear-gradient(to top,var(--navy-950) 0%,rgba(8,15,31,.6) 50%,rgba(8,15,31,.2) 100%)}
.hero-content{position:relative;z-index:1;padding:40px 32px;width:100%}
.hero-regime{font-size:9px;letter-spacing:.2em;text-transform:uppercase;color:var(--gold-300);margin-bottom:8px}
.hero-name{font-family:'Cormorant Garamond',serif;font-size:clamp(36px,7vw,72px);font-weight:400;line-height:.95;color:var(--cream);letter-spacing:-.02em;margin-bottom:8px}
.hero-location{font-size:12px;color:rgba(245,239,230,.5);letter-spacing:.06em}
.hero-video-btn{display:inline-flex;align-items:center;gap:8px;margin-top:16px;background:rgba(201,165,116,.12);border:1px solid rgba(201,165,116,.3);border-radius:20px;padding:6px 14px;font-size:11px;color:var(--gold-300);cursor:pointer;transition:.2s}
.hero-video-btn:hover{background:rgba(201,165,116,.2)}
.video-expand{display:none;padding:0 32px 32px}
.video-expand.open{display:block}
.video-wrap{position:relative;aspect-ratio:16/9;border-radius:4px;overflow:hidden;background:#000}
.video-wrap iframe{position:absolute;inset:0;width:100%;height:100%;border:none}

/* CLIENT BAR */
.client-bar{display:flex;align-items:center;justify-content:space-between;padding:14px 32px;background:rgba(201,165,116,.06);border-top:1px solid var(--line);border-bottom:1px solid var(--line);flex-wrap:wrap;gap:8px}
.client-name{font-size:13px;color:var(--cream);font-weight:500}
.client-validity{font-size:11px;color:rgba(245,239,230,.4)}
.client-ref{font-size:10px;letter-spacing:.1em;color:var(--gold-400);background:rgba(201,165,116,.1);padding:3px 10px;border-radius:20px}

/* BODY */
.prop-body{max-width:680px;margin:0 auto;padding:40px 32px 80px}

.section-label{font-size:9px;letter-spacing:.2em;text-transform:uppercase;color:var(--gold-400);font-weight:600;margin-bottom:14px}

/* STAY GRID */
.stay-grid{display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-bottom:36px}
.stay-item{background:rgba(255,255,255,.03);border:1px solid rgba(255,255,255,.07);border-radius:3px;padding:12px 14px}
.stay-item-lbl{font-size:8px;text-transform:uppercase;letter-spacing:.12em;color:rgba(245,239,230,.35);margin-bottom:4px}
.stay-item-val{font-size:13px;color:var(--cream)}

/* SERVICES */
.services{margin-bottom:28px}
.service-row{display:flex;align-items:center;justify-content:space-between;padding:11px 0;border-bottom:1px solid rgba(255,255,255,.05)}
.service-row:last-child{border-bottom:none}
.service-left{display:flex;align-items:center;gap:10px;font-size:13px;color:rgba(245,239,230,.75)}
.service-icon{font-size:16px;width:22px;text-align:center;flex-shrink:0}
.service-price{font-size:13px;color:var(--gold-300);font-weight:500;white-space:nowrap}

/* TOTAL */
.total-box{background:rgba(201,165,116,.07);border:1px solid rgba(201,165,116,.22);border-radius:4px;padding:18px 20px;display:flex;align-items:center;justify-content:space-between;margin-bottom:32px}
.total-lbl{font-size:9px;text-transform:uppercase;letter-spacing:.15em;color:rgba(245,239,230,.5)}
.total-val{font-family:'Cormorant Garamond',serif;font-size:36px;color:var(--gold-300);letter-spacing:-.01em}

/* CTA */
.cta-btn{display:flex;align-items:center;justify-content:center;gap:10px;width:100%;background:#25D366;color:#fff;font-size:10px;letter-spacing:.2em;text-transform:uppercase;font-weight:700;border:none;padding:16px;border-radius:3px;cursor:pointer;transition:.2s;text-decoration:none;margin-bottom:28px}
.cta-btn:hover{background:#1da855}
.cta-btn svg{flex-shrink:0}

/* CONDITIONS */
.conditions{border-top:1px solid var(--line);padding-top:24px}
.cond-item{margin-bottom:16px}
.cond-label{font-size:9px;letter-spacing:.15em;text-transform:uppercase;color:var(--gold-400);margin-bottom:4px}
.cond-text{font-size:12px;color:rgba(245,239,230,.5);line-height:1.7}

/* FOOTER */
.prop-footer{text-align:center;padding:24px 32px;border-top:1px solid var(--line)}
.prop-footer-logo{font-family:'Cormorant Garamond',serif;font-size:14px;letter-spacing:.2em;color:var(--gold-400);margin-bottom:4px}
.prop-footer-tag{font-size:10px;color:rgba(245,239,230,.25);letter-spacing:.05em}

/* ERROR */
.error-state{min-height:60vh;display:flex;flex-direction:column;align-items:center;justify-content:center;text-align:center;padding:40px 32px;gap:16px}
.error-title{font-family:'Cormorant Garamond',serif;font-size:32px;color:var(--gold-300)}
.error-sub{font-size:14px;color:rgba(245,239,230,.45);max-width:360px;line-height:1.6}

/* LOADING */
.loading-state{min-height:60vh;display:flex;align-items:center;justify-content:center}
.loading-bar{width:120px;height:1px;background:rgba(201,165,116,.15);position:relative;overflow:hidden}
.loading-bar::after{content:'';position:absolute;left:-50%;width:50%;height:100%;background:var(--gold-300);animation:load .8s ease-in-out infinite}
@keyframes load{to{left:100%}}

@media(max-width:600px){
  .topbar,.hero-content,.prop-body,.video-expand,.client-bar{padding-left:20px;padding-right:20px}
  .stay-grid{grid-template-columns:1fr}
  .hero-name{font-size:clamp(30px,9vw,48px)}
}
</style>
</head>
<body>

<div class="topbar">
  <div class="topbar-logo">RSX TRAVEL</div>
  <div class="topbar-tag">Proposta de Viagem</div>
</div>

<div id="app">
  <div class="loading-state"><div class="loading-bar"></div></div>
</div>

<script>
(function() {
  'use strict';

  // Helpers
  function esc(s) { return String(s||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;'); }

  function fmtDate(iso) {
    if (!iso) return '—';
    const [y,m,d] = iso.split('-');
    const months = ['jan','fev','mar','abr','mai','jun','jul','ago','set','out','nov','dez'];
    return `${parseInt(d,10)} ${months[parseInt(m,10)-1]} ${y}`;
  }

  function fmtMoney(v) {
    return 'R$ ' + Number(v||0).toLocaleString('pt-BR',{minimumFractionDigits:0,maximumFractionDigits:0});
  }

  function fmtGuests(stay) {
    const parts = [];
    const adults = parseInt(stay.adults,10)||0;
    if (adults) parts.push(adults + (adults===1?' adulto':' adultos'));
    const children = stay.children || [];
    if (children.length) parts.push(children.length + (children.length===1?' criança':' crianças'));
    return parts.join(' · ') || '—';
  }

  function calcTotal(p) {
    const acc = Number(p.accommodation_price||0);
    const svcs = (p.services||[]).reduce((s,sv)=>s+Number(sv.price||0),0);
    return acc + svcs;
  }

  // Get ID from URL
  function getParam(name) {
    return new URLSearchParams(window.location.search).get(name);
  }

  function ytId(url) {
    var m = (url||'').match(/(?:youtu\.be\/|youtube\.com\/(?:watch\?v=|embed\/|shorts\/))([^&\s?\/]+)/);
    return m ? m[1] : null;
  }

  // Render
  function render(p, cmsData) {
    const app = document.getElementById('app');

    // Hero background: first image if custom, else use resort gallery from CMS
    let heroBg = '';
    if (p.resort.type === 'custom' && p.resort.images && p.resort.images.length) {
      heroBg = `background-image:url('${esc(p.resort.images[0])}')`;
    }

    // Video: check CMS for existing resort
    let videoYt = '';
    let videoId = '';
    if (p.resort.type === 'existing' && cmsData) {
      const pageData = (cmsData.pages||{})[p.resort.slug]||{};
      videoYt = pageData.hero_video_yt || pageData.hero_video_file || '';
      if (videoYt) videoId = ytId(videoYt) || '';
    }

    // Services list (accommodation first, then extras)
    const allServices = [
      { icon:'🏨', description: p.stay.room_type ? `${p.stay.room_type} · ${p.stay.checkin ? (parseInt(p.stay.checkout.split('-')[2],10) - parseInt(p.stay.checkin.split('-')[2],10)) + ' noites' : ''}`.trim() : 'Hospedagem', price: p.accommodation_price },
      ...(p.services||[])
    ];

    const servicesHtml = allServices.map(sv => sv.price > 0 ? `
      <div class="service-row">
        <div class="service-left"><span class="service-icon">${esc(sv.icon||'🏨')}</span>${esc(sv.description)}</div>
        <div class="service-price">${fmtMoney(sv.price)}</div>
      </div>` : '').join('');

    const hasConditions = p.conditions && (p.conditions.payment_info || p.conditions.cancellation || p.conditions.legal_notes);

    const condHtml = hasConditions ? `
      <div class="conditions">
        ${p.conditions.payment_info ? `<div class="cond-item"><div class="cond-label">Forma de pagamento</div><div class="cond-text">${esc(p.conditions.payment_info)}</div></div>` : ''}
        ${p.conditions.cancellation ? `<div class="cond-item"><div class="cond-label">Cancelamento</div><div class="cond-text">${esc(p.conditions.cancellation)}</div></div>` : ''}
        ${p.conditions.legal_notes ? `<div class="cond-item"><div class="cond-label">Informações</div><div class="cond-text">${esc(p.conditions.legal_notes)}</div></div>` : ''}
      </div>` : '';

    const waMsg = encodeURIComponent(p.conditions && p.conditions.whatsapp_message
      ? p.conditions.whatsapp_message
      : `Oi Rebeca, quero confirmar minha proposta de viagem! (Ref: ${p.id})`);
    const waLink = `https://wa.me/5541988429348?text=${waMsg}`;

    document.title = `Proposta ${esc(p.client.name)} — RSX Travel`;

    app.innerHTML = `
      <div class="hero">
        <div class="hero-bg" style="${heroBg}"></div>
        <div class="hero-overlay"></div>
        <div class="hero-content">
          ${p.resort.regime ? `<div class="hero-regime">${esc(p.resort.regime)}</div>` : ''}
          <div class="hero-name">${esc(p.resort.name)}</div>
          ${p.resort.location ? `<div class="hero-location">${esc(p.resort.location)}</div>` : ''}
          ${videoId ? `<button class="hero-video-btn" onclick="toggleVideo(this,'${esc(videoId)}')">▶ Ver vídeo do resort</button>` : ''}
        </div>
      </div>
      ${videoId ? `<div class="video-expand" id="videoExpand"></div>` : ''}

      <div class="client-bar">
        <div class="client-name">Proposta para <strong>${esc(p.client.name)}</strong></div>
        ${p.validity ? `<div class="client-validity">Válida até ${fmtDate(p.validity)}</div>` : ''}
      </div>

      <div class="prop-body">
        <div class="section-label">Sua estadia</div>
        <div class="stay-grid">
          <div class="stay-item"><div class="stay-item-lbl">Check-in</div><div class="stay-item-val">${fmtDate(p.stay.checkin)}</div></div>
          <div class="stay-item"><div class="stay-item-lbl">Check-out</div><div class="stay-item-val">${fmtDate(p.stay.checkout)}</div></div>
          ${p.stay.room_type ? `<div class="stay-item"><div class="stay-item-lbl">Acomodação</div><div class="stay-item-val">${esc(p.stay.room_type)}</div></div>` : ''}
          <div class="stay-item"><div class="stay-item-lbl">Hóspedes</div><div class="stay-item-val">${esc(fmtGuests(p.stay))}</div></div>
        </div>

        <div class="section-label">O que está incluído</div>
        <div class="services">${servicesHtml}</div>

        <div class="total-box">
          <div class="total-lbl">Total da viagem</div>
          <div class="total-val">${fmtMoney(calcTotal(p))}</div>
        </div>

        <a href="${waLink}" target="_blank" rel="noopener" class="cta-btn">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><path d="M17.5 14.4c-.3-.1-1.8-.9-2.1-1-.3-.1-.5-.1-.7.1-.2.3-.7 1-.9 1.1-.2.2-.3.2-.6.1-.3-.1-1.3-.5-2.4-1.5-.9-.8-1.5-1.8-1.7-2.1-.2-.3 0-.5.1-.6l.4-.5c.1-.2.2-.3.3-.5.1-.2 0-.4 0-.5 0-.1-.7-1.7-1-2.3-.3-.6-.5-.5-.7-.5h-.6c-.2 0-.5.1-.8.4-.3.3-1.1 1.1-1.1 2.6 0 1.6 1.1 3.1 1.3 3.3.2.2 2.3 3.5 5.6 4.9.8.3 1.4.5 1.9.7.8.2 1.5.2 2.1.1.6-.1 1.8-.7 2.1-1.5.3-.7.3-1.4.2-1.5-.1-.2-.3-.3-.6-.4zM12 2C6.5 2 2 6.5 2 12c0 1.8.5 3.5 1.3 5L2 22l5.2-1.4c1.5.8 3.1 1.2 4.8 1.2 5.5 0 10-4.5 10-10S17.5 2 12 2z"/></svg>
          Quero essa viagem!
        </a>

        ${condHtml}
      </div>

      <div class="prop-footer">
        <div class="prop-footer-logo">RSX TRAVEL</div>
        <div class="prop-footer-tag">Rebeca Piccolo · Curitiba PR · rsxtravel.com.br</div>
      </div>
    `;
  }

  function toggleVideo(btn, videoId) {
    const wrap = document.getElementById('videoExpand');
    if (!wrap) return;
    if (wrap.classList.contains('open')) {
      wrap.classList.remove('open');
      wrap.innerHTML = '';
      btn.textContent = '▶ Ver vídeo do resort';
    } else {
      wrap.classList.add('open');
      wrap.innerHTML = `<div class="video-wrap"><iframe src="https://www.youtube-nocookie.com/embed/${videoId}?rel=0&modestbranding=1&iv_load_policy=3" title="Vídeo do resort" frameborder="0" allow="accelerometer;autoplay;clipboard-write;encrypted-media;gyroscope;picture-in-picture" allowfullscreen></iframe></div>`;
      btn.textContent = '✕ Fechar vídeo';
    }
  }
  window.toggleVideo = toggleVideo;

  function showError(msg) {
    document.getElementById('app').innerHTML = `
      <div class="error-state">
        <div class="error-title">Proposta não encontrada</div>
        <div class="error-sub">${esc(msg)}</div>
        <a href="index.html" style="margin-top:8px;font-size:11px;letter-spacing:.15em;text-transform:uppercase;color:rgba(201,165,116,.6)">← Voltar ao site</a>
      </div>`;
  }

  // Main
  async function init() {
    const id = getParam('id');
    if (!id) { showError('Nenhum ID de proposta foi informado na URL.'); return; }

    try {
      const r = await fetch('proposals-save.php?_=' + Date.now());
      if (!r.ok) throw new Error('Servidor indisponível');
      const json = await r.json();
      const proposal = (json.proposals||{})[id];
      if (!proposal) { showError('A proposta "' + id + '" não existe ou foi removida.'); return; }

      // If existing resort, also fetch cms-data.json for video URL
      let cmsData = null;
      if (proposal.resort && proposal.resort.type === 'existing') {
        try {
          const cr = await fetch('cms-data.json?_=' + Date.now());
          if (cr.ok) cmsData = await cr.json();
        } catch(e) {}
      }

      render(proposal, cmsData);
    } catch(e) {
      showError('Erro ao carregar a proposta. Tente novamente em alguns segundos.');
    }
  }

  init();
}());
</script>
</body>
</html>
```

- [ ] **Step 2: Verificar localmente**

Abra `proposta.html?id=teste` no browser local. Deve mostrar "Proposta não encontrada" com mensagem amigável — isso é correto, pois não há dados ainda.

- [ ] **Step 3: Commit**

```bash
git add proposta.html
git commit -m "feat: add public proposal page (proposta.html)"
```

---

## Task 3: Admin — PROPOSALS Engine + Sidebar + Lista de Propostas

**Files:**
- Modify: `admin.html`

Três adições ao `admin.html`: (1) botão na sidebar, (2) objeto `PROPOSALS`, (3) `PAGES.propostas()`.

- [ ] **Step 1: Adicionar botão "Propostas" na sidebar**

Localizar este trecho em `admin.html`:
```html
      <div class="sidebar-section-label">Criar</div>
      <button class="nav-btn" data-page="novo" onclick="showPage('novo',this)">
```

Inserir **antes** desse bloco:
```html
      <div class="sidebar-section-label">Ferramentas</div>
      <button class="nav-btn" data-page="propostas" onclick="showPage('propostas',this)">
        <span class="nav-icon">📋</span>Propostas
      </button>
```

- [ ] **Step 2: Adicionar objeto `PROPOSALS` após o objeto `CMS`**

Localizar a linha `/* ══ AUTH ══ */` (após o fechamento do CMS) e inserir **antes** dela:

```js
/* ══════════════════════════════════════════
   PROPOSALS ENGINE
══════════════════════════════════════════ */
const PROPOSALS = {
  data: { proposals: {} },

  async load() {
    try {
      const r = await fetch('proposals-save.php?_=' + Date.now());
      this.data = r.ok ? await r.json() : { proposals: {} };
    } catch(e) { this.data = { proposals: {} }; }
  },

  async save(proposal) {
    const r = await fetch('proposals-save.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ _pass: CMS.pass(), action: 'save', proposal })
    });
    return r.json();
  },

  async remove(id) {
    const r = await fetch('proposals-save.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ _pass: CMS.pass(), action: 'delete', id })
    });
    return r.json();
  },

  all() {
    return Object.values(this.data.proposals || {})
      .sort((a, b) => (b.created_at || '').localeCompare(a.created_at || ''));
  },

  get(id) { return (this.data.proposals || {})[id] || null; },

  propLink(id) {
    return location.origin + location.pathname.replace('admin.html', '') + 'proposta.html?id=' + id;
  }
};
```

- [ ] **Step 3: Adicionar `PAGES.propostas()` dentro do objeto `PAGES`**

Localizar o fechamento do objeto `PAGES` (a linha com `};` após `contato() {...}`):
```js
  contato() { ... },
};
```

Adicionar **antes** do `};`:

```js
  propostas() {
    const all = PROPOSALS.all();
    const today = new Date().toISOString().substring(0, 10);

    const rows = all.length ? all.map(p => {
      const expired = p.validity && p.validity < today;
      const statusDot = `<span class="prop-status-dot ${expired ? 'exp' : 'open'}" title="${expired ? 'Expirada' : 'Aberta'}"></span>`;
      const link = PROPOSALS.propLink(p.id);
      return `
        <div class="prop-list-row">
          ${statusDot}
          <div class="prop-list-client">
            <div class="prop-list-name">${esc(p.client.name)}</div>
            <div class="prop-list-detail">${esc(p.resort.name || '—')}${p.stay.checkin ? ' · ' + p.stay.checkin : ''}</div>
          </div>
          <div class="prop-list-date">${p.created_at || '—'}</div>
          <div class="prop-list-actions">
            <button class="prop-action-btn" onclick="propCopyLink('${esc(p.id)}','${esc(link)}')">🔗 Link</button>
            <button class="prop-action-btn" onclick="propEdit('${esc(p.id)}')">✏️ Editar</button>
            <button class="prop-action-btn" onclick="propDuplicate('${esc(p.id)}')">⧉ Duplicar</button>
            <button class="prop-action-btn danger" onclick="propDelete('${esc(p.id)}')">🗑</button>
          </div>
        </div>`;
    }).join('') : `<div class="prop-list-empty">Nenhuma proposta ainda. Crie a primeira!</div>`;

    return `
      <h1 class="page-title">Propostas de Viagem</h1>
      <p class="page-subtitle">Crie cotações personalizadas e gere links únicos por cliente.</p>
      <div style="margin-bottom:20px">
        <button class="btn-save" onclick="propNew()">+ Nova proposta</button>
      </div>
      <div class="prop-list-wrap">
        <div class="prop-list-header">
          <span style="flex:0 0 12px"></span>
          <span style="flex:1">Cliente / Resort</span>
          <span style="width:90px">Criada em</span>
          <span style="width:240px;text-align:right">Ações</span>
        </div>
        ${rows}
      </div>
    `;
  },
```

- [ ] **Step 4: Adicionar CSS para a lista de propostas e wizard**

Localizar `</style>` no `<head>` do `admin.html` e inserir **antes** dele:

```css
/* ── PROPOSTAS LIST ── */
.prop-list-wrap{background:var(--surface);border:1px solid var(--border);border-radius:8px;overflow:hidden}
.prop-list-header{display:flex;align-items:center;gap:12px;padding:10px 16px;background:rgba(255,255,255,.03);border-bottom:1px solid var(--border);font-size:9px;letter-spacing:.12em;text-transform:uppercase;color:var(--text-dim)}
.prop-list-row{display:flex;align-items:center;gap:12px;padding:12px 16px;border-bottom:1px solid rgba(255,255,255,.04)}
.prop-list-row:last-child{border-bottom:none}
.prop-status-dot{width:8px;height:8px;border-radius:50%;flex-shrink:0}
.prop-status-dot.open{background:#4ade80}
.prop-status-dot.exp{background:#f87171}
.prop-list-client{flex:1;min-width:0}
.prop-list-name{font-size:12px;color:var(--text);white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.prop-list-detail{font-size:10px;color:var(--text-dim)}
.prop-list-date{width:90px;font-size:10px;color:var(--text-dim);flex-shrink:0}
.prop-list-actions{display:flex;gap:6px;flex-shrink:0;width:240px;justify-content:flex-end}
.prop-action-btn{font-size:10px;color:var(--gold-dim);background:rgba(220,192,147,.07);border:1px solid rgba(220,192,147,.15);border-radius:2px;padding:4px 9px;cursor:pointer;white-space:nowrap;transition:.15s}
.prop-action-btn:hover{background:rgba(220,192,147,.14)}
.prop-action-btn.danger{color:rgba(248,113,113,.7);background:rgba(248,113,113,.06);border-color:rgba(248,113,113,.15)}
.prop-list-empty{padding:40px;text-align:center;color:var(--text-dim);font-size:13px}
/* ── WIZARD ── */
.wiz-container{max-width:760px}
.wiz-header{display:flex;align-items:center;justify-content:space-between;margin-bottom:24px}
.wiz-steps-bar{display:flex;background:var(--surface);border:1px solid var(--border);border-radius:6px;overflow:hidden;margin-bottom:24px}
.wiz-step-item{flex:1;padding:10px 8px;text-align:center;font-size:9px;letter-spacing:.06em;color:var(--text-dim);border-right:1px solid var(--border);cursor:pointer;transition:.15s}
.wiz-step-item:last-child{border-right:none}
.wiz-step-item.active{color:var(--gold-dim);background:rgba(220,192,147,.07);font-weight:600}
.wiz-step-item.done{color:rgba(220,192,147,.5)}
.wiz-step-item.done:hover{background:rgba(220,192,147,.05)}
.wiz-body{background:var(--surface);border:1px solid var(--border);border-radius:6px;padding:24px;margin-bottom:16px}
.wiz-body-title{font-size:14px;font-weight:600;color:var(--text);margin-bottom:20px}
.wiz-footer{display:flex;align-items:center;justify-content:space-between;padding:0 4px}
.wiz-progress{font-size:11px;color:var(--text-dim)}
.wiz-field-grid{display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:14px}
.wiz-field-grid.three{grid-template-columns:1fr 1fr 1fr}
.wiz-field-grid.full{grid-template-columns:1fr}
.wiz-field{margin-bottom:0}
.resort-toggle-wrap{display:flex;gap:0;background:rgba(255,255,255,.04);border-radius:4px;border:1px solid var(--border);overflow:hidden;width:fit-content;margin-bottom:18px}
.resort-toggle-btn{font-size:11px;padding:8px 20px;border:none;background:transparent;color:var(--text-dim);cursor:pointer;transition:.15s}
.resort-toggle-btn.active{background:rgba(220,192,147,.15);color:var(--gold-dim);font-weight:600}
.svc-row{display:flex;align-items:center;gap:8px;background:rgba(255,255,255,.03);border:1px solid var(--border);border-radius:3px;padding:8px 10px;margin-bottom:6px}
.svc-icon-input{width:36px;font-size:18px;background:transparent;border:none;color:var(--text);text-align:center;cursor:pointer}
.svc-desc-input{flex:1;background:transparent;border:none;color:var(--text);font-size:12px;outline:none;font-family:inherit}
.svc-price-input{width:100px;background:transparent;border:none;color:var(--gold-dim);font-size:12px;text-align:right;outline:none;font-family:inherit}
.svc-remove-btn{background:none;border:none;color:rgba(248,113,113,.5);cursor:pointer;font-size:14px;padding:0 4px}
.svc-add-presets{display:flex;flex-wrap:wrap;gap:6px;margin-bottom:10px}
.svc-preset-btn{font-size:10px;background:rgba(220,192,147,.07);border:1px solid var(--border);border-radius:3px;padding:5px 10px;color:var(--text-dim);cursor:pointer;transition:.15s}
.svc-preset-btn:hover{background:rgba(220,192,147,.14);color:var(--gold-dim)}
.wiz-total{background:rgba(220,192,147,.07);border:1px solid rgba(220,192,147,.2);border-radius:3px;padding:10px 14px;display:flex;justify-content:space-between;align-items:center;margin-top:12px}
.wiz-total-lbl{font-size:9px;text-transform:uppercase;letter-spacing:.12em;color:var(--text-dim)}
.wiz-total-val{font-size:20px;color:var(--gold-dim);font-family:'Cormorant Garamond',serif}
.children-row{display:flex;align-items:center;gap:8px;margin-bottom:6px}
.children-age-input{width:70px}
.link-result{background:rgba(74,222,128,.08);border:1px solid rgba(74,222,128,.25);border-radius:4px;padding:16px;margin-bottom:16px}
.link-result-url{font-size:11px;color:#4ade80;word-break:break-all;margin-bottom:10px;font-family:monospace}
.link-copy-btn{background:#4ade80;color:#000;border:none;padding:7px 16px;border-radius:2px;font-size:10px;letter-spacing:.12em;font-weight:700;cursor:pointer}
```

- [ ] **Step 5: Adicionar funções de ação das propostas após `showPage()`**

Localizar a linha `// Init if session active` e inserir **antes** dela:

```js
/* ── Proposal list actions ── */
function propCopyLink(id, link) {
  navigator.clipboard.writeText(link).then(() => toast('🔗 Link copiado!'));
}

function propNew() {
  WIZ.init(null);
}

function propEdit(id) {
  const p = PROPOSALS.get(id);
  if (!p) { toast('Proposta não encontrada.', true); return; }
  WIZ.init(p);
}

function propDuplicate(id) {
  const p = PROPOSALS.get(id);
  if (!p) { toast('Proposta não encontrada.', true); return; }
  const copy = JSON.parse(JSON.stringify(p));
  copy.id = '';
  copy.created_at = '';
  copy.status = 'open';
  copy.client = Object.assign({}, copy.client, { name: copy.client.name + ' (cópia)' });
  WIZ.init(copy);
}

async function propDelete(id) {
  if (!confirm('Excluir esta proposta? Esta ação não pode ser desfeita.')) return;
  const res = await PROPOSALS.remove(id);
  if (res.ok) {
    await PROPOSALS.load();
    toast('🗑 Proposta excluída.');
    showPage('propostas', document.querySelector('[data-page="propostas"]'));
  } else {
    toast('❌ Erro ao excluir: ' + res.error, true);
  }
}
```

- [ ] **Step 6: Carregar `PROPOSALS` junto com `afterLoad()`**

Localizar a função `afterLoad()`:
```js
function afterLoad() {
  CMS.syncFromServer(function(synced) {
    if (synced) toast('✅ Dados sincronizados do servidor.', false);
    renderCustomPageNav();
    showPage('geral', document.querySelector('[data-page="geral"]'));
  });
}
```

Substituir por:
```js
function afterLoad() {
  CMS.syncFromServer(function(synced) {
    if (synced) toast('✅ Dados sincronizados do servidor.', false);
    renderCustomPageNav();
    showPage('geral', document.querySelector('[data-page="geral"]'));
  });
  PROPOSALS.load(); // carrega propostas em paralelo
}
```

- [ ] **Step 7: Verificar**

Abra `admin.html` → faça login → clique em "📋 Propostas" na sidebar.
Esperado: página com título "Propostas de Viagem", botão "+ Nova proposta" e mensagem "Nenhuma proposta ainda."

- [ ] **Step 8: Commit**

```bash
git add admin.html
git commit -m "feat: add proposals tab and list to admin"
```

---

## Task 4: Admin — WIZ Object + Steps 1, 2 e 3

**Files:**
- Modify: `admin.html`

- [ ] **Step 1: Adicionar o objeto `WIZ` após as funções de ação das propostas**

```js
/* ══════════════════════════════════════════
   PROPOSAL WIZARD
══════════════════════════════════════════ */
const WIZ = {
  step: 1,
  data: {},
  editId: null,

  _blank() {
    return {
      client: { name: '', internal_note: '' },
      validity: '',
      resort: { type: 'existing', slug: '', name: '', location: '', regime: '', images: [] },
      stay: { checkin: '', checkout: '', room_type: '', adults: 2, children: [] },
      accommodation_price: 0,
      services: [],
      conditions: { payment_info: '', cancellation: '', legal_notes: '', whatsapp_message: '' }
    };
  },

  init(editData) {
    this.step = 1;
    if (editData) {
      this.editId = editData.id || null;
      this.data = JSON.parse(JSON.stringify(editData));
      // Ensure all keys exist
      if (!this.data.resort.images) this.data.resort.images = [];
      if (!this.data.conditions) this.data.conditions = { payment_info: '', cancellation: '', legal_notes: '', whatsapp_message: '' };
    } else {
      this.editId = null;
      this.data = this._blank();
    }
    this.render();
  },

  render() {
    const stepNames = ['Cliente','Resort','Hospedagem','Serviços','Condições','Revisão'];
    const stepsHtml = stepNames.map((s, i) => {
      const n = i + 1;
      const cls = n < this.step ? 'done' : n === this.step ? 'active' : '';
      const clickable = n < this.step ? `onclick="WIZ.jumpTo(${n})"` : '';
      return `<div class="wiz-step-item ${cls}" ${clickable}>${n}. ${s}</div>`;
    }).join('');

    const isLast = this.step === 6;
    document.getElementById('mainInner').innerHTML = `
      <div class="wiz-container">
        <div class="wiz-header">
          <h1 class="page-title">${this.editId ? 'Editar Proposta' : 'Nova Proposta'}</h1>
          <button class="btn-ghost" onclick="WIZ.close()">← Voltar</button>
        </div>
        <div class="wiz-steps-bar">${stepsHtml}</div>
        <div class="wiz-body" id="wiz-body"></div>
        <div class="wiz-footer">
          <button class="btn-ghost" onclick="WIZ.prev()" style="visibility:${this.step === 1 ? 'hidden' : 'visible'}">← Voltar</button>
          <span class="wiz-progress">Passo ${this.step} de 6</span>
          ${isLast
            ? `<button class="btn-save" onclick="WIZ.saveAndFinish()">💾 Gerar link</button>`
            : `<button class="btn-save" onclick="WIZ.next()">Próximo →</button>`
          }
        </div>
      </div>`;
    this.renderBody();
  },

  renderBody() {
    const body = document.getElementById('wiz-body');
    if (body) body.innerHTML = this['renderStep' + this.step]();
    if (this.step === 4) this.updateTotal();
  },

  collect() {
    if (this.step === 1) this._collect1();
    if (this.step === 2) this._collect2();
    if (this.step === 3) this._collect3();
    if (this.step === 4) this._collect4();
    if (this.step === 5) this._collect5();
  },

  validate() {
    if (this.step === 1 && !this.data.client.name.trim()) {
      toast('⚠️ Informe o nome do cliente.', true); return false;
    }
    if (this.step === 2 && !this.data.resort.name.trim()) {
      toast('⚠️ Selecione ou informe o resort.', true); return false;
    }
    return true;
  },

  next() { this.collect(); if (!this.validate()) return; if (this.step < 6) { this.step++; this.render(); } },
  prev() { this.collect(); if (this.step > 1) { this.step--; this.render(); } },
  jumpTo(n) { this.collect(); this.step = n; this.render(); },
  close() { showPage('propostas', document.querySelector('[data-page="propostas"]')); },

  // ── STEP 1: Cliente ──
  renderStep1() {
    return `
      <div class="wiz-body-title">Dados do cliente</div>
      <div class="wiz-field-grid">
        <div class="field-row wiz-field">
          <label class="field-label">Nome do cliente *</label>
          <input id="w-name" class="field-input" value="${esc(this.data.client.name)}" placeholder="Ex: Ana Lima"/>
        </div>
        <div class="field-row wiz-field">
          <label class="field-label">Proposta válida até</label>
          <input id="w-validity" class="field-input" type="date" value="${esc(this.data.validity)}"/>
        </div>
      </div>
      <div class="field-row">
        <label class="field-label">Nota interna (não aparece para o cliente)</label>
        <textarea id="w-note" class="field-input" rows="2" placeholder="Ex: cliente indicado pela família Souza">${esc(this.data.client.internal_note)}</textarea>
      </div>`;
  },
  _collect1() {
    this.data.client.name = document.getElementById('w-name')?.value || '';
    this.data.validity = document.getElementById('w-validity')?.value || '';
    this.data.client.internal_note = document.getElementById('w-note')?.value || '';
  },

  // ── STEP 2: Resort ──
  renderStep2() {
    const isExisting = this.data.resort.type !== 'custom';
    // Build existing resort options
    const staticResorts = [
      { slug:'costao',      name:'Costão do Santinho' },
      { slug:'fazzenda',    name:'Fazzenda Park Resort' },
      { slug:'japaratinga', name:'Japaratinga Lounge Resort' },
    ];
    try {
      const custom = JSON.parse(localStorage.getItem('rsx_custom_pages') || '[]');
      custom.forEach(c => { if (!staticResorts.find(r => r.slug === c.slug)) staticResorts.push({ slug: c.slug, name: c.nome }); });
    } catch(e) {}
    const opts = staticResorts.map(r =>
      `<option value="${esc(r.slug)}" ${this.data.resort.slug===r.slug?'selected':''}>${esc(r.name)}</option>`
    ).join('');

    const existingPanel = `
      <div class="field-row">
        <label class="field-label">Resort</label>
        <select id="w-resort-slug" class="field-input" onchange="WIZ.onResortChange(this.value)">
          <option value="">— Selecione —</option>
          ${opts}
        </select>
      </div>
      ${this.data.resort.name ? `
        <div class="wiz-field-grid three" style="margin-top:10px">
          <div class="field-item-info"><div class="field-help">Resort</div><strong>${esc(this.data.resort.name)}</strong></div>
          <div class="field-item-info"><div class="field-help">Regime</div><strong>${esc(this.data.resort.regime||'—')}</strong></div>
          <div class="field-item-info"><div class="field-help">Vídeo</div><strong>${CMS.get(this.data.resort.slug,'hero_video_yt') ? '✓ Disponível' : '✕ Não configurado'}</strong></div>
        </div>` : ''}`;

    const customPanel = `
      <div class="wiz-field-grid">
        <div class="field-row wiz-field">
          <label class="field-label">Nome do hotel / resort *</label>
          <input id="w-custom-name" class="field-input" value="${esc(isExisting?'':this.data.resort.name)}" placeholder="Ex: Nannai Resort & Spa"/>
        </div>
        <div class="field-row wiz-field">
          <label class="field-label">Localização</label>
          <input id="w-custom-location" class="field-input" value="${esc(isExisting?'':this.data.resort.location)}" placeholder="Ex: Porto de Galinhas · PE"/>
        </div>
      </div>
      <div class="field-row">
        <label class="field-label">Regime alimentar</label>
        <input id="w-custom-regime" class="field-input" value="${esc(isExisting?'':this.data.resort.regime)}" placeholder="Ex: All-Inclusive · Meia Pensão"/>
      </div>
      <div class="field-row">
        <label class="field-label">Fotos do resort (até 5 · JPG/PNG/WebP)</label>
        <div id="w-img-list" style="display:flex;flex-wrap:wrap;gap:8px;margin-bottom:8px">
          ${(this.data.resort.images||[]).map((url,i)=>`<div style="position:relative;display:inline-block"><img src="${esc(url)}" style="width:72px;height:72px;object-fit:cover;border-radius:3px;border:1px solid var(--border)"/><button onclick="WIZ.removeImage(${i})" style="position:absolute;top:-4px;right:-4px;background:#f87171;color:#fff;border:none;border-radius:50%;width:18px;height:18px;font-size:10px;cursor:pointer;line-height:18px">✕</button></div>`).join('')}
        </div>
        <input type="file" id="w-img-upload" accept="image/*" onchange="WIZ.uploadImage(this)" ${(this.data.resort.images||[]).length>=5?'disabled':''}/>
        <div class="field-help" id="w-img-status"></div>
      </div>`;

    return `
      <div class="wiz-body-title">Resort ou hotel</div>
      <div class="resort-toggle-wrap">
        <button class="resort-toggle-btn ${isExisting?'active':''}" onclick="WIZ.setResortType('existing')">🏖 Resort do site</button>
        <button class="resort-toggle-btn ${!isExisting?'active':''}" onclick="WIZ.setResortType('custom')">🏨 Outro hotel / resort</button>
      </div>
      ${isExisting ? existingPanel : customPanel}`;
  },

  setResortType(type) {
    this._collect2();
    this.data.resort.type = type;
    if (type === 'existing') {
      this.data.resort.name = '';
      this.data.resort.slug = '';
      this.data.resort.location = '';
      this.data.resort.regime = '';
    }
    this.renderBody();
  },

  onResortChange(slug) {
    if (!slug) { this.data.resort = Object.assign(this.data.resort, { slug:'', name:'', location:'', regime:'' }); return; }
    const names = { costao:'Costão do Santinho', fazzenda:'Fazzenda Park Resort', japaratinga:'Japaratinga Lounge Resort' };
    const custom = (() => { try { return JSON.parse(localStorage.getItem('rsx_custom_pages')||'[]'); } catch(e){return[];} })();
    const customPage = custom.find(c => c.slug === slug);
    this.data.resort.slug = slug;
    this.data.resort.name = names[slug] || (customPage && customPage.nome) || slug;
    this.data.resort.regime = CMS.get(slug, 'regime') || '';
    this.data.resort.location = CMS.get(slug, 'meta_localizacao') || '';
    this.renderBody();
  },

  _collect2() {
    if (this.data.resort.type === 'existing') {
      const sel = document.getElementById('w-resort-slug');
      if (sel) this.onResortChange(sel.value);
    } else {
      this.data.resort.name     = document.getElementById('w-custom-name')?.value || '';
      this.data.resort.location = document.getElementById('w-custom-location')?.value || '';
      this.data.resort.regime   = document.getElementById('w-custom-regime')?.value || '';
    }
  },

  async uploadImage(input) {
    if (!input.files || !input.files[0]) return;
    const status = document.getElementById('w-img-status');
    if (status) status.textContent = 'Enviando...';
    const fd = new FormData();
    fd.append('file', input.files[0]);
    fd.append('_pass', CMS.pass());
    try {
      const r = await fetch('upload.php', { method: 'POST', body: fd });
      const res = await r.json();
      if (res.ok) {
        if (!this.data.resort.images) this.data.resort.images = [];
        this.data.resort.images.push(res.url);
        this.renderBody();
      } else {
        if (status) status.textContent = '❌ ' + res.error;
      }
    } catch(e) {
      if (status) status.textContent = '❌ Falha no upload.';
    }
    input.value = '';
  },

  removeImage(idx) {
    this.data.resort.images.splice(idx, 1);
    this.renderBody();
  },

  // ── STEP 3: Hospedagem ──
  renderStep3() {
    const children = this.data.stay.children || [];
    const childrenHtml = children.map((age, i) => `
      <div class="children-row">
        <input class="field-input children-age-input" type="number" min="0" max="17" value="${age}" placeholder="Idade" data-child="${i}"/>
        <button class="btn-ghost" style="padding:4px 8px" onclick="WIZ.removeChild(${i})">✕</button>
      </div>`).join('');

    return `
      <div class="wiz-body-title">Detalhes da hospedagem</div>
      <div class="wiz-field-grid">
        <div class="field-row wiz-field">
          <label class="field-label">Check-in</label>
          <input id="w-checkin" class="field-input" type="date" value="${esc(this.data.stay.checkin)}"/>
        </div>
        <div class="field-row wiz-field">
          <label class="field-label">Check-out</label>
          <input id="w-checkout" class="field-input" type="date" value="${esc(this.data.stay.checkout)}"/>
        </div>
      </div>
      <div class="field-row">
        <label class="field-label">Tipo de acomodação / quarto</label>
        <input id="w-room" class="field-input" value="${esc(this.data.stay.room_type)}" placeholder="Ex: Chalé Vista Mar · Superior"/>
      </div>
      <div class="wiz-field-grid">
        <div class="field-row wiz-field">
          <label class="field-label">Nº de adultos</label>
          <input id="w-adults" class="field-input" type="number" min="1" max="20" value="${this.data.stay.adults||2}"/>
        </div>
        <div class="field-row wiz-field">
          <label class="field-label">Valor da hospedagem (R$)</label>
          <input id="w-acc-price" class="field-input" type="number" min="0" value="${this.data.accommodation_price||0}" oninput="WIZ.updateTotal()"/>
        </div>
      </div>
      <div class="field-row">
        <label class="field-label">Crianças (informe a idade de cada uma)</label>
        <div id="w-children-list">${childrenHtml}</div>
        <button class="btn-ghost" style="margin-top:6px;font-size:11px" onclick="WIZ.addChild()">+ Adicionar criança</button>
      </div>`;
  },

  addChild() {
    this._collect3();
    this.data.stay.children.push(0);
    this.renderBody();
  },

  removeChild(i) {
    this._collect3();
    this.data.stay.children.splice(i, 1);
    this.renderBody();
  },

  _collect3() {
    this.data.stay.checkin    = document.getElementById('w-checkin')?.value || '';
    this.data.stay.checkout   = document.getElementById('w-checkout')?.value || '';
    this.data.stay.room_type  = document.getElementById('w-room')?.value || '';
    this.data.stay.adults     = parseInt(document.getElementById('w-adults')?.value||2, 10);
    this.data.accommodation_price = parseFloat(document.getElementById('w-acc-price')?.value||0);
    document.querySelectorAll('[data-child]').forEach(inp => {
      const i = parseInt(inp.dataset.child, 10);
      this.data.stay.children[i] = parseInt(inp.value||0, 10);
    });
  },
};
```

- [ ] **Step 2: Verificar**

Abra o admin → clique em "Propostas" → clique em "+ Nova proposta".
Esperado: wizard abre com "Passo 1 de 6", campos de Nome, Validade, Nota interna. Clicar "Próximo →" sem nome deve mostrar toast de erro.

- [ ] **Step 3: Commit**

```bash
git add admin.html
git commit -m "feat: add proposal wizard steps 1-3"
```

---

## Task 5: Admin — WIZ Steps 4, 5, 6 + saveAndFinish

**Files:**
- Modify: `admin.html`

Adicionar os métodos `renderStep4`, `_collect4`, `renderStep5`, `_collect5`, `renderStep6`, `updateTotal` e `saveAndFinish` dentro do objeto `WIZ` (antes do `};` de fechamento).

- [ ] **Step 1: Adicionar renderStep4, _collect4 e updateTotal ao objeto WIZ**

Localizar o fechamento do objeto `WIZ` (`};` após `_collect3`) e inserir **antes** dele:

```js
  // ── STEP 4: Serviços ──
  renderStep4() {
    const presets = [
      { icon:'✈️', type:'flight',    label:'Aéreo' },
      { icon:'🚐', type:'transfer',  label:'Transfer' },
      { icon:'🛡️', type:'insurance', label:'Seguro' },
      { icon:'🏖️', type:'tour',      label:'Passeio' },
      { icon:'➕', type:'other',     label:'Outro' },
    ];
    const presetsHtml = presets.map(p =>
      `<button class="svc-preset-btn" onclick="WIZ.addService('${p.icon}','${p.type}','${p.label}')">${p.icon} ${p.label}</button>`
    ).join('');

    const svcsHtml = (this.data.services||[]).map((sv, i) => `
      <div class="svc-row" data-svc="${i}">
        <input class="svc-icon-input" value="${esc(sv.icon||'➕')}" maxlength="2" title="Emoji"/>
        <input class="svc-desc-input" value="${esc(sv.description)}" placeholder="Descrição do serviço"/>
        <input class="svc-price-input" type="number" min="0" value="${sv.price||0}" placeholder="R$" oninput="WIZ.updateTotal()"/>
        <button class="svc-remove-btn" onclick="WIZ.removeService(${i})">✕</button>
      </div>`).join('');

    return `
      <div class="wiz-body-title">Serviços incluídos na proposta</div>
      <div class="svc-add-presets">${presetsHtml}</div>
      <div id="w-svc-list">${svcsHtml || '<div style="color:var(--text-dim);font-size:12px;margin-bottom:10px">Nenhum serviço adicionado ainda.</div>'}</div>
      <div class="wiz-total">
        <span class="wiz-total-lbl">Total geral (hospedagem + serviços)</span>
        <span class="wiz-total-val" id="w-total">R$ 0</span>
      </div>`;
  },

  addService(icon, type, label) {
    this._collect4();
    this.data.services.push({ icon, type, description: label, price: 0 });
    this.renderBody();
  },

  removeService(i) {
    this._collect4();
    this.data.services.splice(i, 1);
    this.renderBody();
  },

  updateTotal() {
    const acc = parseFloat(document.getElementById('w-acc-price')?.value || this.data.accommodation_price || 0);
    const svcTotal = Array.from(document.querySelectorAll('.svc-price-input'))
      .reduce((s, inp) => s + parseFloat(inp.value||0), 0);
    const total = acc + svcTotal;
    const el = document.getElementById('w-total');
    if (el) el.textContent = 'R$ ' + total.toLocaleString('pt-BR', { minimumFractionDigits: 0, maximumFractionDigits: 0 });
  },

  _collect4() {
    this.data.services = Array.from(document.querySelectorAll('[data-svc]')).map(row => ({
      icon:        row.querySelector('.svc-icon-input')?.value || '➕',
      type:        'other',
      description: row.querySelector('.svc-desc-input')?.value || '',
      price:       parseFloat(row.querySelector('.svc-price-input')?.value || 0),
    }));
  },

  // ── STEP 5: Condições ──
  renderStep5() {
    const autoMsg = this.data.conditions.whatsapp_message ||
      `Oi Rebeca! Quero confirmar minha proposta de viagem para ${this.data.resort.name||'o resort'}. Pode me ajudar?`;
    return `
      <div class="wiz-body-title">Condições e informações</div>
      <div class="field-row">
        <label class="field-label">Forma de pagamento</label>
        <textarea id="w-payment" class="field-input" rows="2" placeholder="Ex: 30% de entrada + 70% até 30 dias antes">${esc(this.data.conditions.payment_info)}</textarea>
      </div>
      <div class="field-row">
        <label class="field-label">Política de cancelamento</label>
        <textarea id="w-cancel" class="field-input" rows="2" placeholder="Ex: Cancelamento gratuito até 60 dias antes do check-in">${esc(this.data.conditions.cancellation)}</textarea>
      </div>
      <div class="field-row">
        <label class="field-label">Informações legais / observações gerais</label>
        <textarea id="w-legal" class="field-input" rows="2" placeholder="Proposta sujeita à disponibilidade...">${esc(this.data.conditions.legal_notes)}</textarea>
      </div>
      <div class="field-row">
        <label class="field-label">Mensagem WhatsApp pré-preenchida para o cliente</label>
        <textarea id="w-wa-msg" class="field-input" rows="2">${esc(autoMsg)}</textarea>
        <div class="field-help">O cliente verá essa mensagem ao clicar em "Quero essa viagem!"</div>
      </div>`;
  },

  _collect5() {
    this.data.conditions.payment_info      = document.getElementById('w-payment')?.value || '';
    this.data.conditions.cancellation      = document.getElementById('w-cancel')?.value || '';
    this.data.conditions.legal_notes       = document.getElementById('w-legal')?.value || '';
    this.data.conditions.whatsapp_message  = document.getElementById('w-wa-msg')?.value || '';
  },

  // ── STEP 6: Revisão ──
  renderStep6() {
    const total = [this.data.accommodation_price, ...(this.data.services||[]).map(s=>s.price)]
      .reduce((a,b) => a + Number(b||0), 0);
    const fmtMoney = v => 'R$ ' + Number(v||0).toLocaleString('pt-BR',{minimumFractionDigits:0});
    const fmtDate = iso => { if(!iso) return '—'; const [y,m,d]=iso.split('-'); const ms=['jan','fev','mar','abr','mai','jun','jul','ago','set','out','nov','dez']; return `${parseInt(d,10)} ${ms[parseInt(m,10)-1]} ${y}`; };

    const svcsRows = [
      { icon:'🏨', description: this.data.stay.room_type || 'Hospedagem', price: this.data.accommodation_price },
      ...(this.data.services||[])
    ].filter(s => s.price > 0).map(s =>
      `<div style="display:flex;justify-content:space-between;padding:6px 0;border-bottom:1px solid rgba(255,255,255,.06);font-size:12px"><span>${esc(s.icon||'🏨')} ${esc(s.description)}</span><span style="color:var(--gold-dim)">${fmtMoney(s.price)}</span></div>`
    ).join('');

    return `
      <div class="wiz-body-title">Revisão — como o cliente vai ver</div>
      <div style="background:rgba(255,255,255,.02);border:1px solid var(--border);border-radius:4px;padding:20px;margin-bottom:16px">
        <div style="font-size:10px;letter-spacing:.15em;text-transform:uppercase;color:var(--gold-dim);margin-bottom:4px">${esc(this.data.resort.regime||'')}</div>
        <div style="font-family:'Cormorant Garamond',serif;font-size:28px;color:var(--text);margin-bottom:4px">${esc(this.data.resort.name||'—')}</div>
        <div style="font-size:11px;color:var(--text-dim);margin-bottom:16px">${esc(this.data.resort.location||'')}</div>
        <div style="font-size:11px;color:var(--text-dim);margin-bottom:16px">Proposta para <strong>${esc(this.data.client.name||'—')}</strong>${this.data.validity?' · Válida até '+fmtDate(this.data.validity):''}</div>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px;margin-bottom:16px">
          <div style="background:rgba(255,255,255,.03);border:1px solid rgba(255,255,255,.07);border-radius:3px;padding:10px"><div style="font-size:8px;text-transform:uppercase;letter-spacing:.1em;color:var(--text-dim);margin-bottom:3px">Check-in</div>${fmtDate(this.data.stay.checkin)}</div>
          <div style="background:rgba(255,255,255,.03);border:1px solid rgba(255,255,255,.07);border-radius:3px;padding:10px"><div style="font-size:8px;text-transform:uppercase;letter-spacing:.1em;color:var(--text-dim);margin-bottom:3px">Check-out</div>${fmtDate(this.data.stay.checkout)}</div>
        </div>
        ${svcsRows}
        <div style="background:rgba(220,192,147,.07);border:1px solid rgba(220,192,147,.2);border-radius:3px;padding:10px;display:flex;justify-content:space-between;align-items:center;margin-top:12px">
          <span style="font-size:9px;text-transform:uppercase;letter-spacing:.12em;color:var(--text-dim)">Total da viagem</span>
          <span style="font-family:'Cormorant Garamond',serif;font-size:28px;color:var(--gold-dim)">${fmtMoney(total)}</span>
        </div>
      </div>
      <div id="wiz-link-result"></div>
      <p style="font-size:11px;color:var(--text-dim)">Clique em "💾 Gerar link" para salvar e obter o link do cliente.</p>`;
  },

  // ── SAVE ──
  async saveAndFinish() {
    this.collect();

    // Generate ID/slug if new
    if (!this.editId) {
      const slugify = s => String(s||'').toLowerCase()
        .normalize('NFD').replace(/[̀-ͯ]/g,'')
        .replace(/[^a-z0-9]/g,'-').replace(/-+/g,'-').replace(/^-|-$/g,'');
      const clientPart = slugify(this.data.client.name).substring(0, 15);
      const resortPart = slugify(this.data.resort.name).substring(0, 10);
      const monthPart  = (this.data.stay.checkin || new Date().toISOString())
        .substring(0, 7).replace('-', '');
      let base = `${clientPart}-${resortPart}-${monthPart}`;
      let candidate = base;
      const all = PROPOSALS.all();
      let n = 2;
      while (all.find(p => p.id === candidate)) { candidate = base + '-' + n++; }
      this.data.id = candidate;
      this.data.created_at = new Date().toISOString().substring(0, 10);
      this.data.status = 'open';
    } else {
      this.data.id = this.editId;
    }

    const btn = document.querySelector('.wiz-footer .btn-save');
    if (btn) { btn.disabled = true; btn.textContent = 'Salvando...'; }

    const res = await PROPOSALS.save(this.data);

    if (btn) { btn.disabled = false; btn.textContent = '💾 Gerar link'; }

    if (res.ok) {
      await PROPOSALS.load();
      const link = PROPOSALS.propLink(this.data.id);
      const el = document.getElementById('wiz-link-result');
      if (el) {
        el.innerHTML = `
          <div class="link-result">
            <div style="font-size:10px;letter-spacing:.1em;text-transform:uppercase;color:rgba(74,222,128,.7);margin-bottom:8px">✓ Proposta salva com sucesso!</div>
            <div class="link-result-url">${esc(link)}</div>
            <div style="display:flex;gap:8px">
              <button class="link-copy-btn" onclick="navigator.clipboard.writeText('${esc(link)}').then(()=>toast('🔗 Link copiado!'))">📋 Copiar link</button>
              <a href="${esc(link)}" target="_blank" class="btn-ghost" style="font-size:10px;padding:7px 14px">↗ Abrir proposta</a>
            </div>
          </div>`;
        el.scrollIntoView({ behavior: 'smooth', block: 'center' });
      }
      toast('✅ Proposta salva!');
    } else {
      toast('❌ Erro ao salvar: ' + (res.error || 'desconhecido'), true);
    }
  },
```

- [ ] **Step 2: Verificar**

1. Abra admin → Propostas → Nova proposta
2. Passo 1: Nome "João Teste", validade futura → Próximo
3. Passo 2: Resort do site → Japaratinga → Próximo  
4. Passo 3: Datas, quarto "Superior", 2 adultos, valor 5000 → Próximo
5. Passo 4: Add "✈️ Aéreo · CWB→MCZ" = 1800 → total deve mostrar R$ 6.800 → Próximo
6. Passo 5: preencher condições → Próximo
7. Passo 6: ver resumo → clicar "💾 Gerar link"

Esperado: link aparece, clicar "↗ Abrir proposta" abre `proposta.html?id=...` com os dados corretos.

- [ ] **Step 3: Verificar edição e duplicação**

1. Voltar à lista → clicar "✏️ Editar" na proposta criada
2. Alterar algum campo → Gerar link novamente → proposta atualizada no link
3. Clicar "⧉ Duplicar" → wizard abre com nome "(cópia)" → alterar e gerar novo link

- [ ] **Step 4: Commit**

```bash
git add admin.html
git commit -m "feat: add proposal wizard steps 4-6 and save logic"
```

---

## Task 6: Deploy — `.cpanel.yml` + Push + Verificação Final

**Files:**
- Modify: `.cpanel.yml`

- [ ] **Step 1: Atualizar `.cpanel.yml`**

Substituir o conteúdo atual de `.cpanel.yml` por:

```yaml
---
deployment:
  tasks:
    - /bin/cp -f index.html sobre.html contato.html costao.html fazzenda.html japaratinga.html admin.html content-override.js cms-save.php upload.php proposta.html proposals-save.php /home/rsxtrave/public_html/
    - /bin/cp -n cms-data.json /home/rsxtrave/public_html/cms-data.json || true
    - /bin/cp -n proposals.json /home/rsxtrave/public_html/proposals.json || true
    - /bin/cp -rf design_handoff_rsx_travel_site /home/rsxtrave/public_html/
    - /bin/mkdir -p /home/rsxtrave/public_html/midia
    - /bin/chmod 755 /home/rsxtrave/public_html/midia
```

- [ ] **Step 2: Commit final e push**

```bash
git add .cpanel.yml
git commit -m "chore: add proposta.html and proposals-save.php to deployment"
git push origin main
```

- [ ] **Step 3: Deploy via cPanel**

1. Acesse cPanel → Git™ Version Control → Manage
2. Clique "Update from Remote" → aguardar → "Deploy HEAD Commit"
3. Aguardar conclusão

- [ ] **Step 4: Smoke test no servidor ao vivo**

Verificar cada item:

```
1. GET https://rsxtravel.com.br/proposals-save.php
   → Esperado: {"proposals":{}}

2. GET https://rsxtravel.com.br/proposta.html?id=inexistente
   → Esperado: página RSX com "Proposta não encontrada"

3. Admin: https://rsxtravel.com.br/admin.html → login → "📋 Propostas"
   → Esperado: aba abre, lista vazia com "Nenhuma proposta ainda."

4. Admin: criar proposta completa → gerar link
   → Esperado: link válido, proposta abre corretamente no browser

5. GET https://rsxtravel.com.br/proposals-save.php
   → Esperado: JSON com a proposta criada
```

- [ ] **Step 5: Verificar persistência após deploy**

```
1. Fazer um novo push de qualquer alteração pequena (ex: mudar um comentário)
2. Fazer deploy novamente
3. Verificar que proposals.json no servidor ainda tem as propostas
   → Esperado: propostas intactas (cp -n não sobrescreve)
```

---

## Resumo de Critérios de Aceite

| Critério | Verificado em |
|---|---|
| Admin cria proposta em < 3 min | Task 5, Step 2 |
| Link público abre sem login | Task 5, Step 2 |
| Proposta persiste após deploy | Task 6, Step 5 |
| Editar e duplicar funcionam | Task 5, Step 3 |
| Vídeo aparece em resort do site | Task 2 (proposta.html) |
| Fotos de resort externo aparecem no hero | Task 4, Step 2 (upload) |
