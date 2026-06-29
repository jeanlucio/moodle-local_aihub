# AI Hub (`local_aihub`)

🇧🇷 [Português](#-central-de-ia-pt-br) · 🇬🇧 [English](#-ai-hub-en)

---

## 🇬🇧 AI Hub (EN)

A small **BYOK (bring your own key)** broker that lets the institution's own Moodle
plugins generate text through shared AI API keys, without each plugin reimplementing
the transport, the SSRF guard or a key store.

### ✨ What it does

- Stores **site** API keys (admin) and optional **personal** API keys (per user, opt-in)
  for **Gemini**, **Groq** and any **OpenAI-compatible** endpoint.
- Resolves the key **personal first, then site**, and exposes a one-call facade for
  consumer plugins: `\local_aihub\ai::generate_text($system, $user, $jsonmode, $component)`.
- Does **not** wrap `core_ai`. Consumers keep their own `core_ai` fallback, so a site that
  already has `core_ai` configured needs no extra setup.
- Personal keys are **write-only**: once saved, a key is never shown again — the page only
  reports a *configured / not configured* status.
- Ships a self-service **My AI keys** page (in the user's preferences) to manage personal
  keys and review one's own recent usage.
- Gives administrators a **site-keys usage report** — every request served by the site keys,
  across all users — with **CSV / Excel** download (capability `local/aihub:viewusage`).

### 🔧 For developers (consuming the hub)

```php
if (class_exists(\local_aihub\ai::class) && \local_aihub\ai::is_available()) {
    // 4th arg: your frankenstyle (for the usage log). 5th arg: a short label of what you
    // are generating, shown in the admin report (e.g. "Concept cartridge - Algebra").
    $result = \local_aihub\ai::generate_text('', $prompt, false, 'your_frankenstyle', 'Short label');
    if ($result['success']) {
        // $result['data'] is RAW, untrusted text — validate and format_text() it.
    }
}
// Keep your own core_ai fallback for sites without the hub installed.
```

### 🔒 Third-party service disclosure / data transmission

When a key resolves to a provider, the **prompt text is transmitted** to that provider's
API to generate the response:

- **Google Gemini** — `generativelanguage.googleapis.com`
- **Groq** — `api.groq.com`
- **OpenAI-compatible** — the endpoint configured by the admin or user
  (default `api.openai.com`)

No prompt is sent unless an API key (site or personal) is configured. These destinations
are declared in the plugin's Privacy Provider.

### ⚙️ Requirements

- Moodle 4.5+ (`$plugin->requires = 2024100700`)

### 📜 License

GNU GPL v3 or later.

---

## 🇧🇷 Central de IA (PT-BR)

Um pequeno corretor **BYOK (traga sua própria chave)** que permite aos plugins Moodle da
própria instituição gerar texto através de chaves de API de IA compartilhadas, sem que
cada plugin reimplemente o transporte, o guard de SSRF ou um armazenamento de chaves.

### ✨ O que faz

- Guarda chaves de API de **site** (admin) e chaves **pessoais** opcionais (por usuário,
  opt-in) para **Gemini**, **Groq** e qualquer endpoint **compatível com OpenAI**.
- Resolve a chave **pessoal primeiro, depois a de site**, e expõe uma fachada de uma
  chamada para os plugins consumidores:
  `\local_aihub\ai::generate_text($system, $user, $jsonmode, $component)`.
- **Não** embrulha o `core_ai`. Os consumidores mantêm o próprio fallback para `core_ai`,
  então um site que já tem `core_ai` configurado não precisa de nada extra.
- As chaves pessoais são **write-only**: uma vez salva, a chave nunca é exibida de novo —
  a página mostra apenas o status *configurada / não configurada*.
- Traz uma página self-service **Minhas chaves de IA** (nas preferências do usuário) para
  gerenciar as chaves pessoais e revisar o próprio uso recente.
- Oferece ao administrador um **relatório de uso das chaves do site** — todas as requisições
  atendidas pelas chaves do site, de todos os usuários — com download **CSV / Excel**
  (capability `local/aihub:viewusage`).

### 🔒 Divulgação de serviços de terceiros / transmissão de dados

Quando uma chave é resolvida para um provedor, o **texto do prompt é transmitido** à API
desse provedor para gerar a resposta:

- **Google Gemini** — `generativelanguage.googleapis.com`
- **Groq** — `api.groq.com`
- **Compatível com OpenAI** — o endpoint configurado pelo admin ou usuário
  (padrão `api.openai.com`)

Nenhum prompt é enviado sem que uma chave (de site ou pessoal) esteja configurada. Esses
destinos estão declarados no Privacy Provider do plugin.

### ⚙️ Requisitos

- Moodle 4.5+ (`$plugin->requires = 2024100700`)

### 📜 Licença

GNU GPL v3 ou posterior.
