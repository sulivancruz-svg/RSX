# Handoff: RSX Travel — Site institucional

## Visão geral

Site institucional para a **RSX Travel** (rsxtravel.com.br), uma curadora pessoal de viagens especializada em resorts All-Inclusive, conduzida pela Rebeca Piccolo (Curitiba, PR). O site é vitrine + manifesto + ponto de contato: o usuário descobre os resorts em destaque, entende a filosofia da curadoria, conhece a Rebeca, e converte por WhatsApp.

**Não é uma plataforma de booking.** Não há checkout, preços ou disponibilidade — todo o fluxo de conversão termina em uma mensagem pré-preenchida via WhatsApp.

## Sobre os arquivos de design

Os arquivos neste pacote são **referências de design criadas em HTML/CSS/React inline** — protótipos que mostram a aparência e o comportamento pretendidos, **não código de produção para copiar diretamente**.

A tarefa é **recriar estes designs no ambiente do codebase de destino** (Next.js / Astro / Nuxt / WordPress headless / etc.) usando os padrões, bibliotecas e tooling estabelecidos. Caso ainda não exista um codebase, recomendamos **Next.js (App Router) com Tailwind CSS** dado o perfil estático/marketing do site.

## Fidelidade

**High-fidelity (hifi).** Cores, tipografia, espaçamentos, animações e interações estão finalizados e devem ser recriados com precisão. As imagens são placeholders (drag-and-drop no protótipo via `<image-slot>`) e devem ser substituídas por fotografia real fornecida pela cliente.

---

## Estrutura do site (single page)

Uma única página, scroll vertical, com 7 seções principais. Cada seção tem `data-screen-label` para referência.

### 01 · Hero
- **Layout**: Full viewport (100vh, min 720px). Imagem de fundo full-bleed com **parallax** (data-parallax="-0.25").
- **Conteúdo**:
  - Headline serif gigante, à esquerda, top-left: "Olá!" / "Vamos viajar?" (com "viajar?" em itálico dourado)
  - Tag eyebrow: "Página Principal · RSX Travel"
  - Quote bottom-left: "Curadoria pessoal de resorts e roteiros..."
  - Meta bottom-right: "Curitiba — PR · Brasil" + coordenadas em mono
  - Scroll indicator centralizado embaixo
- **Overlay**: Gradiente vertical 55% → 20% → 55% rgba navy escuro para legibilidade.

### 02 · Search bar (Pedir curadoria)
- **Posição**: Flutua sobre o final do hero, sobrepondo a próxima seção (margin-top: -52px).
- **Layout**: Pílula horizontal cremosa, com sombra profunda. 3 campos clicáveis + botão CTA.
- **Campos**:
  - **Destino** — abre dropdown com 5 resorts (Costão, Fazzenda, Japaratinga, Salinas Maragogi, Transamerica Comandatuba)
  - **Check-in · Check-out** — abre calendário de 2 meses com seleção de intervalo
  - **Hóspedes** — stepper para adultos/crianças/quartos
- **CTA "Pedir curadoria"** — abre WhatsApp (`wa.me/5541988429348`) com mensagem pré-preenchida usando os valores selecionados:
  ```
  Oi Rebeca! Estou pensando em {destino}, {datas}, para {N pessoas · N quartos}. Pode me ajudar?
  ```

### 03 · Destaques (4 cards)
- **Layout**: Grid 4 colunas (desktop), 2 colunas (≤1400px), 1 coluna (≤640px).
- **Cards**:
  1. **Quem sou** → `#sobre` (âncora interna)
  2. **Costão do Santinho** → `rsxtravel.com.br/costao`
  3. **Fazzenda Park Resort** → `rsxtravel.com.br/fazzenda`
  4. **Japaratinga Lounge Resort** → `rsxtravel.com.br/japaratinga`
- **Card anatomia**:
  - Imagem aspect-ratio 16/11, hover scale 1.06 (transição 1.2s)
  - Bookmark icon top-right
  - Meta (location · tipo) em uppercase dourado pequeno
  - Nome em serif 28px
  - Descrição em sans-serif 13px cinza
  - "Conhecer o resort →" em uppercase 11px, com border-top divisor

### 04 · Stats (Pilares da marca)
- **Layout**: Grid 4 colunas. Cards conectados por divisores 1px.
- **Pilares** (sem números — são valores conceituais):
  1. **All-Inclusive** — "Especialistas em resorts com tudo incluso"
  2. **Curadoria** — "Cada roteiro pensado do voo à volta para casa"
  3. **Atendimento 1:1** — "Você fala direto com a Rebeca"
  4. **Sob medida** — "Roteiro feito para o seu momento"
- Cada card tem: ícone SVG dourado 48px no topo, título serif 26px navy, label uppercase pequeno.

### 05 · Destino em destaque (full-bleed)
- **Layout**: Full-width, min-height 720px, imagem de fundo com parallax (-0.15).
- **Overlay**: SVG com linhas tracejadas dourado conectando 4 "map dots" posicionados (Costão, Fazzenda, Japaratinga, Maragogi). Cada dot tem label em serif italic.
- **Conteúdo bottom**: Grid 2 colunas
  - Esquerda: "Conheça" eyebrow + título "Costão do Santinho" em serif gigante
  - Direita: parágrafo descritivo, pills (All-Inclusive, Spa, Mata Atlântica, Kids Club), CTA "Explorar o destino →"

### 06 · Manifesto
- **Background**: Navy escuro (`--navy-900`).
- **Decoração**: Texto "RSX" gigante (clamp 360-640px) em serif italic, opacidade 5%, centralizado absoluto atrás do conteúdo.
- **Conteúdo centralizado** (max 880px):
  - Eyebrow "— Filosofia"
  - Título: "Alguns registros guardam mais do que **imagens.**" (serif clamp 42-72px)
  - 2 parágrafos com o manifesto fiel ao site original
  - Assinatura "— Rebeca Piccolo" em serif italic dourado

### 07 · Rebeca (Quem sou)
- **Background**: Cream warm.
- **Layout**: Grid 2 colunas (retrato esquerda 1fr · texto 1.1fr), max 1400px, gap 100px.
- **Retrato**: Aspect 4/5, com borda dourada decorativa offsetada (-18px top/left, +18px bottom/right).
- **Texto**:
  - Eyebrow "Quem sou"
  - Headline "Sou a Rebeca. E cuido dos detalhes."
  - 2 parágrafos (bio)
  - Assinatura: nome em serif italic dourado + cargo "Founder & Travel Designer"

### 08 · CTA final
- **Background**: Paper.
- **Layout**: Centralizado, texto + botão.
- Headline gigante (clamp 56-104px): "Quando for a sua vez de viajar, eu cuido dos detalhes."
- Botão "Começar minha viagem →" navy escuro → WhatsApp.

### 09 · Footer
- **Background**: Navy 900.
- **Layout**: Grid 4 colunas (logo+tagline · Navegar · Resorts · Conecte-se).
- Logo branco grande (84px), tagline em serif italic.
- Listas de links em sans-serif cinza claro.
- Bottom: copyright + "Desenhado com cuidado" em uppercase pequeno.

### Flutuantes
- **Nav fixa** (top): logo branco + 5 links + pill de idioma + CTA "Reservar". Vira sólida navy com blur ao rolar (`scrolled` class).
- **WhatsApp float** (bottom-right): pílula navy com border dourado, ícone + label "WhatsApp".

---

## Design tokens

### Cores (paleta principal "Navy · Gold")

```css
--navy-900: #0e1a35   /* fundo escuro principal, ink */
--navy-800: #1a2847   /* fundo escuro secundário */
--navy-700: #233357   /* hover state */
--gold-500: #b8935c   /* dourado primário (text/icon) */
--gold-400: #c9a574   /* dourado accent (highlights) */
--gold-300: #dcc093   /* dourado claro (em fundos escuros) */
--cream:    #f5efe6   /* cremoso (cartões claros, texto sobre escuro) */
--cream-warm: #ede4d3 /* fundo seção Rebeca */
--paper:    #f7f3eb   /* fundo principal do site */
--ink:      #0e1a35   /* texto sobre fundo claro */
--line:     rgba(201, 165, 116, 0.25)
```

### Paletas alternativas (no protótipo via Tweaks)

| Nome | navy-900 | gold-500 | paper |
|---|---|---|---|
| Midnight | #0a0e1a | #a07849 | #f4eee3 |
| Forest | #162820 | #c2a274 | #faf6ec |
| Wine | #3a1820 | #b8915f | #faf4ea |

### Tipografia

- **Display**: `Cormorant Garamond` (Google Fonts). Weights: 300, 400, 500, 600. Italic 300, 400. Use itálico em ouro para destaque.
- **Body / UI**: `Inter` (Google Fonts). Weights: 300, 400, 500, 600, 700.
- **Mono**: `JetBrains Mono` (Google Fonts). Para metadata, coordenadas, URLs.

**Escala de tipo**:
- Hero H1: clamp(64px, 8.5vw, 152px), line-height 0.95, letter-spacing -0.02em
- Section H2: clamp(42px, 5vw, 72px), line-height 1.05-1.1
- Feature H2: clamp(60px, 7vw, 116px)
- CTA H2: clamp(56px, 6.5vw, 104px)
- Card title: 28px serif weight 500
- Body: 16-17px, line-height 1.7-1.85, weight 300
- Eyebrow / labels: 10-11px uppercase, letter-spacing 0.25-0.4em, weight 500-600

### Espaçamento

- Padding seções desktop: `140px 64px` (vertical 140, horizontal 64)
- Padding seções mobile: `100px 28px`
- Gaps de grid: 24-32px desktop, 16-20px mobile

### Border radius

- Cards de tour: 0 (retos)
- Search bar: 100px (pílula)
- Search field interno: 100px
- Popovers: 20px
- Pills: 100px
- Botões CTA grandes: 100px (pílula)
- Botões steppers: 100px (círculo 32px)

### Shadows

```css
/* Search bar */
box-shadow:
  0 24px 60px -16px rgba(14,26,53,0.35),
  0 8px 20px -8px rgba(14,26,53,0.2),
  0 0 0 1px rgba(201,165,116,0.18);

/* Tour card hover */
box-shadow: 0 24px 48px -16px rgba(14,26,53,0.18);

/* Popovers */
box-shadow:
  0 24px 50px -12px rgba(14,26,53,0.25),
  0 0 0 1px rgba(201,165,116,0.18);

/* WhatsApp float */
box-shadow: 0 12px 36px rgba(14,26,53,0.4);
```

---

## Interações e comportamento

### Parallax
- Engine custom em `requestAnimationFrame`. Loop em `scroll` com flag `ticking` para evitar trabalho redundante.
- Elementos com `[data-parallax="<speed>"]` recebem `translate3d(0, y, 0)` baseado em `(centerElemento - centroViewport) * speed`.
- Speeds usados: hero-bg `-0.25`, feature-bg `-0.15`, rebeca-portrait `+0.04`.
- Em produção: considerar `Intersection Observer` + CSS scroll-driven animations onde suportado, ou Framer Motion (`useScroll` + `useTransform`).

### Reveal on scroll
- `IntersectionObserver` com `threshold: 0.1` adiciona classe `.in` a `.reveal` elements.
- Transição: opacity 0→1 e translateY 32px→0 em 1s cubic-bezier(.2,.7,.2,1).

### Nav fixa
- Ao `scrollY > 40`: adiciona `.scrolled` → background navy com blur, padding reduzido.

### Search bar
- Estado controlado via React `useState`:
  - `open: 'dest' | 'dates' | 'guests' | null`
  - `dest: { id, name, sub }`
  - `start, end: Date | null` — range de check-in/out
  - `adults, children, rooms: number`
- Cada campo é um `<div role="button">` (não `<button>` — popovers contêm steppers que são botões; nesting de buttons inválido).
- Outside click via `document.addEventListener('mousedown')` + ref.
- Calendário: 2 meses lado-a-lado, navegação ‹ › altera `calOffset`. Pick range: 1º clique = start, 2º clique > start = end, 2º clique < start = novo start.
- Submit: monta string `Oi Rebeca! Estou pensando em {dest}, {dates}, para {guests}. Pode me ajudar?` e abre `wa.me/5541988429348?text={encoded}`.

### Tour cards hover
- `transform: translateY(-6px)` + box-shadow.
- Imagem interna `transform: scale(1.06)` em 1.2s.

### Map dots (feature)
- 4 dots absolute posicionados em %. Cada dot tem `::after` com label.
- SVG com `viewBox` flexível e `<line>` tracejada conectando dots — calculados pela posição visual no protótipo (não dados reais).

---

## State management (resumo)

| State | Onde | Persistência |
|---|---|---|
| `palette`, `heroTitle`, `parallaxIntensity` | Tweaks panel | localStorage via host (não relevante em prod) |
| `open`, `dest`, `start`, `end`, `adults`, `children`, `rooms`, `calOffset`, `hover` | SearchBar component | In-memory (sem persistência) |

Em produção, considerar persistir a busca em URL params (`?dest=costao&from=2026-07-14&to=2026-07-21&adults=2`) para sharable links e analytics.

---

## Conteúdo (copy fiel ao site original)

### Manifesto (verbatim)

> Alguns registros guardam mais do que imagens.
> Guardam o tempo — do jeito que a gente gostaria de viver mais vezes.
>
> Fica na memória, na foto esquecida que vira favorita,
> no "lembra disso?" que surge do nada na mesa do jantar.
> Fica no descanso, no cuidado, no cheiro daquele lugar que marcou.
>
> Por aqui, é isso que move:
> **fazer parte de viagens que deixam marcas boas.**
>
> E quando for a sua vez de viajar,
> eu cuido dos detalhes com carinho.

### Resorts (do site original)

- **Costão do Santinho** — Florianópolis · SC — rsxtravel.com.br/costao
- **Fazzenda Park Resort** — Santo Amaro · SC — rsxtravel.com.br/fazzenda
- **Japaratinga Lounge Resort** — Japaratinga · AL — rsxtravel.com.br/japaratinga

### Contato

- WhatsApp: `+55 41 98842-9348` → `wa.me/5541988429348`
- Instagram: `@rebecapiccolo` → instagram.com/rebecapiccolo
- Facebook: facebook.com/rebeca.piccolo.9

---

## Assets

### Logos (em `/assets`)

| Arquivo | Uso |
|---|---|
| `rsx-logo-white.png` | Nav, footer (fundo escuro). PNG com transparência. |
| `rsx-logo-black.png` | Reserva (fundos claros). PNG com transparência. |
| `rsx-logo-principal.png` | Marca dourada principal. PNG com transparência. |
| `rsx-logo.png` | Legado: logo sobre fundo navy. |

### Imagens (placeholders)

Todas as imagens grandes estão como `<image-slot>` no protótipo — placeholders drag-and-drop que persistem localmente. Em produção, substituir por:

| ID | Local | Conteúdo esperado |
|---|---|---|
| `hero-bg` | Hero | Paisagem cinematográfica (praia/montanha/resort) |
| `tour-quem-sou` | Card "Quem sou" | Retrato profissional da Rebeca |
| `tour-costao` | Card Costão | Foto do Costão do Santinho |
| `tour-fazzenda` | Card Fazzenda | Foto do Fazzenda Park |
| `tour-japaratinga` | Card Japaratinga | Foto do Japaratinga Lounge |
| `feature-bg` | Destaque full-bleed | Paisagem dramática do resort destacado |
| `rebeca-portrait` | Seção Rebeca | Retrato pessoal da Rebeca (4:5) |

---

## Responsive

Breakpoints:
- **Desktop**: ≥1400px (grid 4 colunas de tours)
- **Tablet large**: 1100-1400px (grid 2 colunas, arrows escondidos)
- **Tablet**: 760-1100px (nav links escondidos, sections com padding reduzido)
- **Mobile**: ≤760px (search bar empilhada, popovers full-width, grids 1 coluna)

---

## Arquivos neste pacote

- `RSX Travel.html` — protótipo completo (HTML + CSS inline + React/Babel inline)
- `image-slot.js` — web component placeholder de imagem (não usar em prod)
- `tweaks-panel.jsx` — painel de tweaks do protótipo (não usar em prod)
- `assets/` — logos da marca

---

## Recomendações de implementação

### Stack sugerida (se não houver codebase)

- **Framework**: Next.js 14+ (App Router) — bom SEO para institucional
- **Styling**: Tailwind CSS — fácil mapear os tokens; OU CSS Modules se preferir manter próximo do protótipo
- **Animação**: Framer Motion (`useScroll`, `useTransform` para parallax; `motion.div` com `whileInView` para reveal)
- **Fonts**: `next/font/google` para Cormorant Garamond, Inter, JetBrains Mono
- **Imagens**: `next/image` com placeholders blur

### Componentização sugerida

```
components/
  layout/
    Nav.tsx
    Footer.tsx
    WhatsAppFloat.tsx
  sections/
    Hero.tsx
    SearchBar/
      index.tsx
      DestinationPopover.tsx
      DatesPopover.tsx
      Calendar.tsx
      GuestsPopover.tsx
    Destaques.tsx
    ResortCard.tsx
    BrandPillars.tsx
    Feature.tsx
    Manifesto.tsx
    Rebeca.tsx
    FinalCTA.tsx
  ui/
    Pill.tsx
    Button.tsx
```

### Acessibilidade — pendências

- Popovers do search devem ter `role="dialog"` + focus trap + Esc para fechar
- Calendário precisa navegação por teclado (setas) — atualmente só mouse
- Decorative SVG icons devem ter `aria-hidden="true"`
- Logo `<img>` precisa `alt` significativo (já presente)
- Contraste do texto cinza-claro sobre paper precisa ser verificado (`rgba(14,26,53,0.55)` em `#f7f3eb` está borderline AA)

### Performance

- Hero parallax via JS roda em rAF — em produção, preferir `transform` com `will-change` em CSS via scroll-driven animations onde suportado
- Imagens de fundo grandes (hero, feature) devem ser servidas em WebP/AVIF com `srcset`
- Carregar Cormorant Garamond apenas nos weights usados (300, 400, 500 + italic 300, 400)
