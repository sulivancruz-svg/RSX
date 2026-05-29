# Sistema de Propostas de Viagem — RSX Travel

**Data:** 2026-05-29  
**Status:** Aprovado  
**Abordagem:** JSON separado + PHP (Opção A)

---

## Visão Geral

Permitir que a Rebeca monte propostas de viagem personalizadas dentro do admin, com o visual do site RSX Travel, e gere um link público único para cada cliente acessar sua cotação.

---

## Arquitetura

### Novos arquivos

| Arquivo | Função |
|---|---|
| `proposals.json` | Banco de dados das propostas (servidor, nunca sobrescrito no deploy) |
| `proposals-save.php` | CRUD de propostas via POST/GET, mesma autenticação do `cms-save.php` |
| `proposta.html` | Página pública da proposta — lê `?id=abc123` da URL |

### Arquivos modificados

| Arquivo | Mudança |
|---|---|
| `admin.html` | Nova aba "📋 Propostas" com wizard + lista |
| `.cpanel.yml` | Adicionar cópia de `proposta.html` e `proposals-save.php`; adicionar `cp -n proposals.json` |

### URL pública

```
rsxtravel.com.br/proposta.html?id=abc123
```

O `id` é um slug legível gerado a partir do nome do cliente + resort + mês, ex: `ana-japaratinga-jul25`. Em caso de colisão, sufixo numérico é adicionado.

---

## Estrutura de Dados (`proposals.json`)

```json
{
  "proposals": {
    "ana-japaratinga-jul25": {
      "id": "ana-japaratinga-jul25",
      "status": "open",
      "created_at": "2026-05-29",
      "client": {
        "name": "Ana Lima",
        "internal_note": ""
      },
      "validity": "2026-06-30",
      "resort": {
        "type": "existing",
        "slug": "japaratinga",
        "name": "Japaratinga Lounge Resort",
        "location": "Japaratinga · AL",
        "regime": "All-Inclusive Premium",
        "video_url": "",
        "images": []
      },
      "stay": {
        "checkin": "2026-07-12",
        "checkout": "2026-07-19",
        "room_type": "Chalé Vista Mar",
        "adults": 2,
        "children": [8]
      },
      "accommodation_price": 14000,
      "services": [
        { "type": "flight",    "icon": "✈️", "description": "CWB → MCZ · Ida e volta · 2 adultos", "price": 3600 },
        { "type": "transfer",  "icon": "🚐", "description": "Transfer aeroporto → resort",           "price": 320  },
        { "type": "insurance", "icon": "🛡️", "description": "Seguro viagem · 7 dias",               "price": 240  }
      ],
      "conditions": {
        "payment_info": "30% de entrada + 70% até 30 dias antes",
        "cancellation": "Cancelamento gratuito até 60 dias antes do check-in.",
        "legal_notes": ""
      },
      "whatsapp_message": "Oi Rebeca! Quero confirmar a proposta para o Japaratinga em julho. Pode me ajudar?"
    }
  }
}
```

**Campo `resort.type`:**
- `"existing"` → resort cadastrado no site; `slug` referencia a página (`costao`, `fazzenda`, `japaratinga`). O vídeo é puxado de `cms-data.json` (`pages[slug].hero_video_yt`) em tempo de exibição.
- `"custom"` → hotel externo; `name`, `location`, `regime` preenchidos manualmente; `images` contém paths de fotos feitas upload para `/midia/`.

**Total calculado:** `accommodation_price + sum(services[].price)` — calculado em runtime, não armazenado.

---

## `proposals-save.php`

Segue o mesmo padrão do `cms-save.php`:

- **GET** `proposals-save.php` → retorna `proposals.json` completo
- **POST** `proposals-save.php` com `{ _pass, action, ...payload }`:
  - `action: "save"` → cria ou atualiza proposta pelo `id`
  - `action: "delete"` → remove proposta pelo `id`
- Autenticação pela mesma senha (`cms-pass.txt`, padrão `rsx2024`)
- Resposta: `{ ok: true }` ou `{ ok: false, error: "..." }`

---

## Admin — Aba "Propostas"

### Lista de propostas
- Tabela com: status (verde = aberta / vermelho = expirada), nome do cliente, resort, datas, data de criação
- Ações por linha: **🔗 Copiar link**, **✏️ Editar**, **⧉ Duplicar**
- Botão **"+ Nova proposta"** abre o wizard

### Wizard — 6 passos

#### Passo 1 — Cliente
- Nome do cliente (obrigatório)
- Data de validade da proposta
- Nota interna (não aparece na proposta pública)

#### Passo 2 — Resort
- Toggle: **"Resort do site"** | **"Outro hotel / resort"**
- **Resort do site:** dropdown com Costão, Fazzenda, Japaratinga (+ resorts customizados criados no admin). Ao selecionar: nome, regime e indicador de vídeo são preenchidos automaticamente a partir do `cms-data.json`.
- **Outro resort/hotel:** campos manuais para nome, localização, regime. Upload de até 5 fotos (usa o `upload.php` existente, salva em `/midia/`).

#### Passo 3 — Hospedagem
- Check-in / Check-out (date pickers)
- Tipo de quarto / acomodação (texto livre)
- Nº de adultos
- Nº e idades das crianças (campo dinâmico: adicionar/remover)
- Valor da hospedagem (R$)

#### Passo 4 — Serviços Extras
- Lista dinâmica: cada item tem ícone (seletor emoji), descrição e valor
- Botão "+ Adicionar serviço" com atalhos pré-definidos: ✈️ Aéreo, 🚐 Transfer, 🛡️ Seguro, 🏖️ Passeio, ➕ Outro
- Total geral calculado em tempo real (hospedagem + serviços)

#### Passo 5 — Condições
- Forma de pagamento (textarea)
- Política de cancelamento (textarea)
- Informações legais / observações gerais (textarea)
- Mensagem WhatsApp personalizada (pré-preenchida automaticamente, editável)

#### Passo 6 — Revisão
- Preview fiel ao que o cliente vai ver (iframe ou render inline da `proposta.html`)
- Botão **"Gerar e copiar link"** → salva a proposta via `proposals-save.php` e exibe o link para copiar
- Botão **"Abrir proposta"** → abre em nova aba

---

## Página Pública (`proposta.html`)

### Visual
Fundo escuro / luxo imersivo, mesma identidade do site RSX Travel (fontes Cormorant Garamond + Inter, paleta navy/gold/cream).

### Seções (de cima para baixo)

1. **Hero** — foto do resort (ou primeira imagem do upload se externo) com overlay escuro. Para resorts do site com vídeo disponível: botão "▶ Ver vídeo do resort" que expande um player YouTube embedded abaixo do hero.
2. **Badge de cliente** — "Proposta para [Nome]" à esquerda · "Válida até [data]" à direita
3. **Estadia** — grid 2×2 com check-in, check-out, acomodação, hóspedes
4. **O que está incluído** — lista de serviços (hospedagem + extras) com valores individuais
5. **Total** — destaque dourado com valor total da viagem
6. **CTA** — botão verde WhatsApp "Quero essa viagem!" com mensagem personalizada pré-preenchida
7. **Rodapé de condições** — forma de pagamento, cancelamento, notas legais

### Comportamento
- Ao carregar: `proposta.html` lê o `?id=` da URL, faz `fetch('proposals-save.php')` e filtra a proposta pelo id.
- Se proposta não encontrada: exibe mensagem amigável de erro.
- Se `resort.type === "existing"`: faz `fetch('cms-data.json')` para buscar `hero_video_yt` do resort e exibir o botão de vídeo.
- A página é totalmente client-side; sem server-side rendering adicional.

---

## `.cpanel.yml` — Atualização

```yaml
tasks:
  - /bin/cp -f proposta.html proposals-save.php /home/rsxtrave/public_html/
  - /bin/cp -n proposals.json /home/rsxtrave/public_html/proposals.json || true
```

O `-n` (no-clobber) garante que o `proposals.json` do servidor nunca é sobrescrito pelo deploy — mesmo comportamento do `cms-data.json`.

---

## Fora do Escopo (não implementar agora)

- Envio automático por e-mail
- Assinatura digital / aceite formal
- Integração com sistema de pagamento
- Notificação para o admin quando cliente clica no WhatsApp
- Expiração automática de propostas (status é gerenciado manualmente)

---

## Critérios de Sucesso

1. Admin consegue criar uma proposta completa em menos de 3 minutos
2. Link gerado abre corretamente em qualquer dispositivo sem login
3. Propostas persistem após novo deploy do site
4. Editar e duplicar uma proposta funcionam sem recriar tudo do zero
5. Para resorts do site: vídeo aparece automaticamente se disponível no CMS
6. Para resorts externos: fotos enviadas aparecem no hero da proposta
