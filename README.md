# Moodle Local AI Hub

[![Moodle Plugin CI](https://github.com/jeanlucio/moodle-local_aihub/actions/workflows/ci.yml/badge.svg)](https://github.com/jeanlucio/moodle-local_aihub/actions/workflows/ci.yml)
![Moodle](https://img.shields.io/badge/Moodle-4.5%2B-orange?style=flat-square&logo=moodle&logoColor=white)
![License](https://img.shields.io/badge/License-GPLv3-blue?style=flat-square)
![Status](https://img.shields.io/badge/Status-Stable-green?style=flat-square)
[![PlayerGames Ecosystem](https://img.shields.io/badge/PlayerGames-Ecosystem-6f42c1?style=flat-square&logo=gamepad&logoColor=white)](https://moodle.org/plugins/browse.php?list=contributor&id=3970322)
![Role](https://img.shields.io/badge/Role-Shared_Service-198754?style=flat-square)

[English](#english) | [Português](#português)

---

## English

**AI Hub** is a small **BYOK (bring your own key)** broker for Moodle. It lets the institution's own plugins generate text through shared AI API keys, without each plugin reimplementing the HTTP transport, the SSRF guard, the provider ladder or a key store.

It is a **service plugin for developers**: it exposes a one-call PHP facade that any sibling plugin consumes with a `class_exists()` guard. It also gives administrators a place to store site keys, lets users bring their own personal key (opt-in), and reports how the site keys are being used.

<details>
<summary><b>📑 Table of Contents</b></summary>

- [✨ Features](#-features)
- [🧩 How consumers use it](#-how-consumers-use-it)
- [🔗 Key resolution (BYOK ladder)](#-key-resolution-byok-ladder)
- [📦 Requirements](#-requirements)
- [🛠️ Installation](#️-installation)
- [📖 Usage](#-usage)
- [🧪 Automated Tests](#-automated-tests)
- [🔐 Security & Compliance](#-security--compliance)
- [🔎 Third-party Service Disclosure](#-third-party-service-disclosure)
  - [Is an API key required?](#is-an-api-key-required)
  - [Supported providers](#supported-providers)
  - [How to obtain an API key](#how-to-obtain-an-api-key)
  - [Where keys are configured](#where-keys-are-configured)
  - [Data Transmission](#data-transmission)
- [📄 License](#-license)

</details>

---

### ✨ Features

* 🔑 **BYOK key store:** **site** keys (admin) and optional **personal** keys (per user, opt-in) for **Gemini**, **Groq** and any **OpenAI-compatible** endpoint.
* 🪜 **Personal → site resolution:** the hub tries the user's own key first, then the site key, exposing a single result to the caller.
* 🧩 **One-call facade:** `\local_aihub\ai::generate_text()` and `is_available()` — consumed by sibling plugins through a soft dependency (`class_exists`), with no hard dependency entry.
* 🚫 **Does not wrap `core_ai`:** each consumer keeps its own `core_ai` fallback, so a site that already has `core_ai` configured needs **no extra setup** — the hub stays optional.
* 👁️ **Write-only personal keys:** once saved, a personal key is **never returned to the browser** — the page only reports a *configured / not configured* status, closing the read vector via *Log in as*.
* 🧑‍💻 **Self-service page:** *My AI keys* (in the user's preferences) to add/replace/remove personal keys and review one's own recent usage.
* 📊 **Administrator usage report:** every request served by the **site keys**, across all users, with **CSV / Excel** download — gated by `local/aihub:viewusage`.
* 🛡️ **SSRF guard:** the configurable OpenAI-compatible endpoint is forced to HTTPS, with loopback / link-local / private ranges blocked and DNS A/AAAA anti-rebinding.
* 🧾 **Usage log + retention task:** one row per request (user, requesting component, what was generated, provider, model, key tier) and a scheduled task that purges logs past a configurable retention.
* 🔒 **Privacy-complete:** full Privacy provider for the usage log and personal preferences, with the three external destinations declared.

---

### 🧩 How consumers use it

A sibling plugin consumes the hub through a runtime guard and keeps its own `core_ai` fallback:

```php
// Availability: hub keys first, then the consumer's own core_ai fallback.
public static function has_ai(): bool {
    if (class_exists(\local_aihub\ai::class) && \local_aihub\ai::is_available()) {
        return true;                       // BYOK via the hub
    }
    return self::has_core_ai();            // works without the hub
}

// Generation.
$result = \local_aihub\ai::generate_text(
    '',                    // system prompt (optional)
    $prompt,               // user prompt
    false,                 // JSON mode
    'your_frankenstyle',   // 4th arg: caller component, for the usage log
    'Short label'          // 5th arg: what is being generated, shown in the admin report
);
if ($result['success']) {
    // $result['data'] is RAW, untrusted text — validate and format_text() it.
}
```

The returned text is **raw and untrusted**: validate its structure and pass it through `format_text()` before displaying or persisting it.

---

### 🔗 Key resolution (BYOK ladder)

The hub resolves a key **tier by tier** and stops at the first tier that holds a key:

| Tier | Source |
|------|--------|
| 1 | **Personal key** — the user's own key, when personal keys are enabled and the user has `local/aihub:usepersonalkey` |
| 2 | **Site key** — the admin key set in the hub settings |

**Within the chosen tier**, providers are tried in the order **Gemini → Groq → OpenAI-compatible** (first key found is used; if its call fails, the next provider in the same tier is tried). When no tier holds a key, `generate_text()` returns `success = false` — the hub never falls back to `core_ai`; that decision belongs to the consumer.

---

### 📦 Requirements

| Component | Version |
|-----------|---------|
| Moodle    | 4.5+    |
| PHP       | 8.1+    |

No bundled third-party libraries. The AI providers are external **services**, declared in the Privacy provider — not in `thirdpartylibs.xml`.

---

### 🛠️ Installation

1. Download the `.zip` file or clone this repository.
2. Extract the folder into your Moodle `local/` directory.
3. Rename the folder to `aihub` (if necessary). Final path: `your-moodle/local/aihub/`
4. Visit **Site administration → Notifications** to complete installation.

---

### 📖 Usage

1. **Configure site keys (admin):** *Site administration → Plugins → Local plugins → AI Hub → Settings*. Set any of the Gemini, Groq or OpenAI-compatible keys, and (optionally) enable **personal API keys**.
2. **Add a personal key (user):** users who have the `local/aihub:usepersonalkey` capability get a **My AI keys** entry in their preferences, where they store their own key (write-only) and see their recent usage.
3. **Review site-key usage (admin):** the **AI usage report** (under the AI Hub category, capability `local/aihub:viewusage`) lists every request served by the site keys across all users, with CSV and Excel download.
4. **Consume from a plugin (developer):** call `\local_aihub\ai::generate_text()` behind a `class_exists()` guard, keeping your own `core_ai` fallback (see *How consumers use it*).

---

### 🧪 Automated Tests

The hub ships with a PHPUnit and Behat suite; every CI push runs against the matrix (Moodle 4.5 → 5.x, PostgreSQL & MariaDB).

#### PHPUnit — Unit & Integration Tests

| Test file | Cases | What is covered |
|-----------|------:|----------------|
| `tests/local/keys_test.php` | 5 | OpenAI base-URL/model defaults; personal-key get/save/clear roundtrip; `personal_keys_allowed` honouring the toggle **and** the capability; key resolution personal → site; `has_any_key` across personal/site keys |
| `tests/local/client_test.php` | 5 | `is_safe_url` SSRF cases (http, loopback, private range, DNS rebinding); `resolve_openai_url` appends `/chat/completions`; personal tier wins over site; provider fall-through within a tier; no key → `success=false` (no real HTTP) |
| `tests/local/usage_log_test.php` | 3 | Record insert (with `keysource`, empty model nulled); site-scoped readers excluding personal/untagged rows; recent-per-user filtering |
| `tests/local/export_test.php` | 2 | Personal usage export (all rows, every column); site-keys report export including the user column and excluding personal usage |
| `tests/ai_test.php` | 3 | `is_available` states; a successful generation logs the calling component, the description and the key tier; a failed generation logs nothing |
| `tests/privacy_provider_test.php` | 4 | Metadata declaration; context/user discovery; `export_user_data` (log rows + **redacted** key value); per-user deletion with isolation |
| **Total** | **22** | |

```bash
vendor/bin/phpunit --testsuite local_aihub
```

#### Behat — Acceptance Tests

| Feature file | Scenarios | What is covered |
|--------------|----------:|----------------|
| `tests/behat/mykeys.feature` | 2 | A provider starts unconfigured; saving a personal key marks it configured **without revealing the stored value** |
| **Total** | **2** | |

```bash
php admin/tool/behat/cli/init.php
vendor/bin/behat --tags=@local_aihub --profile=chrome
```

---

### 🔐 Security & Compliance

* Capability- and opt-in-gated personal keys (`local/aihub:usepersonalkey` **and** the site toggle).
* **Write-only** personal keys — never returned to the page after saving, even under *Log in as*.
* Site keys stored with `admin_setting_configpasswordunmask`; the SSRF guard protects the configurable endpoint.
* `require_sesskey()` on the key form; capability `local/aihub:viewusage` on the usage report.
* Full Privacy API coverage (export/delete of the usage log and personal preferences) with the three external destinations declared.

---

### 🔎 Third-party Service Disclosure

The hub transmits prompt text to a third-party AI provider **only when a key is configured and a generation is requested**.

#### Is an API key required?

No. The plugin installs and runs without any key — it simply reports that no source is available, and consumers fall back to their own `core_ai` integration. No external request is made until a site or personal key is set.

#### Supported providers

- **Google Gemini** — https://ai.google.dev/
- **Groq** — https://console.groq.com/
- **OpenAI-compatible APIs** — any provider that follows the OpenAI API format (OpenRouter, self-hosted models via LM Studio, an Ollama proxy, etc.)

These services operate under their own terms of service and privacy policies.

#### How to obtain an API key

API keys are created directly on the provider's official website. Both Gemini and Groq currently offer free usage tiers (pricing policies may change). The hub does **not** provide API keys.

#### Where keys are configured

1. **Personal key** — set by each user in *My AI keys* (preferences), when personal keys are enabled and the user has the capability.
2. **Site key** — set by the admin in *Site administration → Plugins → Local plugins → AI Hub*.

#### Data Transmission

When a key resolves to a provider, the **prompt text is transmitted** to that provider's API to generate the response:

- **Google Gemini** — `generativelanguage.googleapis.com`
- **Groq** — `api.groq.com`
- **OpenAI-compatible** — the endpoint configured by the admin or user (default `api.openai.com`)

The hub stores a **usage log** (who requested, which component, a short label of what was generated, provider, model, key tier and time) but **does not store prompts or AI responses**. All external destinations are declared in the plugin's Privacy provider.

---

### 📄 License

This project is licensed under the **GNU General Public License v3 (GPLv3)**.

**Copyright:** 2026 Jean Lúcio

---

## Português

A **Central de IA** é um pequeno intermediário **BYOK (traga sua própria chave)** para o Moodle. Ela permite que os plugins da própria instituição gerem texto através de chaves de API de IA compartilhadas, sem que cada plugin reimplemente o transporte HTTP, o guard de SSRF, a escada de provedores ou um armazenamento de chaves.

É um **plugin de serviço para desenvolvedores**: expõe uma fachada PHP de uma chamada que qualquer plugin irmão consome com um guard `class_exists()`. Também dá ao administrador um lugar para guardar chaves de site, permite que o usuário traga sua chave pessoal (opt-in) e relata como as chaves do site estão sendo usadas.

<details>
<summary><b>📑 Índice</b></summary>

- [✨ Funcionalidades](#-funcionalidades)
- [🧩 Como os consumidores usam](#-como-os-consumidores-usam)
- [🔗 Resolução de chave (escada BYOK)](#-resolução-de-chave-escada-byok)
- [📦 Requisitos](#-requisitos)
- [🛠️ Instalação](#️-instalação)
- [📖 Como Usar](#-como-usar)
- [🧪 Testes Automatizados](#-testes-automatizados)
- [🔐 Segurança e Conformidade](#-segurança-e-conformidade)
- [🔎 Divulgação de Serviço de Terceiros](#-divulgação-de-serviço-de-terceiros)
  - [Uma chave de API é obrigatória?](#uma-chave-de-api-é-obrigatória)
  - [Provedores suportados](#provedores-suportados)
  - [Como obter uma chave de API](#como-obter-uma-chave-de-api)
  - [Onde as chaves são configuradas](#onde-as-chaves-são-configuradas)
  - [Transmissão de Dados](#transmissão-de-dados)
- [📄 Licença](#-licença)

</details>

---

### ✨ Funcionalidades

* 🔑 **Armazenamento BYOK:** chaves de **site** (admin) e chaves **pessoais** opcionais (por usuário, opt-in) para **Gemini**, **Groq** e qualquer endpoint **compatível com OpenAI**.
* 🪜 **Resolução pessoal → site:** o hub tenta a chave do próprio usuário primeiro, depois a de site, expondo um único resultado ao chamador.
* 🧩 **Fachada de uma chamada:** `\local_aihub\ai::generate_text()` e `is_available()` — consumidas pelos plugins irmãos por dependência **soft** (`class_exists`), sem dependência dura.
* 🚫 **Não embrulha o `core_ai`:** cada consumidor mantém o próprio fallback para `core_ai`, então um site que já tem `core_ai` configurado **não precisa de nada extra** — o hub continua opcional.
* 👁️ **Chaves pessoais write-only:** uma vez salva, a chave pessoal **nunca é devolvida ao navegador** — a página mostra só o status *configurada / não configurada*, fechando a leitura via *Entrar como*.
* 🧑‍💻 **Página self-service:** *Minhas chaves de IA* (nas preferências do usuário) para adicionar/substituir/remover chaves pessoais e revisar o próprio uso recente.
* 📊 **Relatório de uso para o admin:** todas as requisições atendidas pelas **chaves do site**, de todos os usuários, com download **CSV / Excel** — protegido por `local/aihub:viewusage`.
* 🛡️ **Guard SSRF:** o endpoint compatível com OpenAI configurável é forçado a HTTPS, com bloqueio de loopback / link-local / faixas privadas e anti-rebinding de DNS A/AAAA.
* 🧾 **Log de uso + task de retenção:** uma linha por requisição (usuário, componente solicitante, o que foi gerado, provedor, modelo, tier da chave) e uma task agendada que limpa logs além de uma retenção configurável.
* 🔒 **Privacidade completa:** Privacy provider completo para o log de uso e as preferências pessoais, com os três destinos externos declarados.

---

### 🧩 Como os consumidores usam

Um plugin irmão consome o hub através de um guard em runtime e mantém o próprio fallback para `core_ai`:

```php
// Disponibilidade: chaves do hub primeiro, depois o fallback core_ai do consumidor.
public static function has_ai(): bool {
    if (class_exists(\local_aihub\ai::class) && \local_aihub\ai::is_available()) {
        return true;                       // BYOK via hub
    }
    return self::has_core_ai();            // funciona sem o hub
}

// Geração.
$result = \local_aihub\ai::generate_text(
    '',                    // prompt de sistema (opcional)
    $prompt,               // prompt do usuário
    false,                 // modo JSON
    'your_frankenstyle',   // 4º arg: componente chamador, para o log de uso
    'Rótulo curto'         // 5º arg: o que está sendo gerado, exibido no relatório de admin
);
if ($result['success']) {
    // $result['data'] é texto CRU e não-confiável — valide e passe por format_text().
}
```

O texto devolvido é **cru e não-confiável**: valide sua estrutura e passe por `format_text()` antes de exibir ou persistir.

---

### 🔗 Resolução de chave (escada BYOK)

O hub resolve a chave **tier por tier** e para no primeiro tier que tiver uma chave:

| Tier | Fonte |
|------|-------|
| 1 | **Chave pessoal** — a chave do próprio usuário, quando as chaves pessoais estão habilitadas e o usuário tem `local/aihub:usepersonalkey` |
| 2 | **Chave de site** — a chave de admin definida nas configurações do hub |

**Dentro do tier escolhido**, os provedores são tentados na ordem **Gemini → Groq → compatível com OpenAI** (a primeira chave encontrada é usada; se a chamada falhar, o próximo provedor do mesmo tier é tentado). Quando nenhum tier tem chave, `generate_text()` retorna `success = false` — o hub nunca cai para o `core_ai`; essa decisão é do consumidor.

---

### 📦 Requisitos

| Componente | Versão |
|------------|--------|
| Moodle     | 4.5+   |
| PHP        | 8.1+   |

Nenhuma biblioteca de terceiros empacotada. Os provedores de IA são **serviços** externos, declarados no Privacy provider — não em `thirdpartylibs.xml`.

---

### 🛠️ Instalação

1. Baixe o arquivo `.zip` ou clone este repositório.
2. Extraia a pasta para o diretório `local/` do seu Moodle.
3. Renomeie a pasta para `aihub` (se necessário). Caminho final: `seu-moodle/local/aihub/`
4. Acesse **Administração do site → Notificações** para concluir a instalação.

---

### 📖 Como Usar

1. **Configurar chaves de site (admin):** *Administração do site → Plugins → Plugins locais → Central de IA → Configurações*. Defina qualquer uma das chaves Gemini, Groq ou compatível com OpenAI e, opcionalmente, habilite as **chaves de API pessoais**.
2. **Adicionar uma chave pessoal (usuário):** usuários com a capability `local/aihub:usepersonalkey` ganham uma entrada **Minhas chaves de IA** nas preferências, onde guardam a própria chave (write-only) e veem o uso recente.
3. **Revisar o uso das chaves de site (admin):** o **Relatório de uso de IA do site** (sob a categoria Central de IA, capability `local/aihub:viewusage`) lista todas as requisições atendidas pelas chaves do site, de todos os usuários, com download CSV e Excel.
4. **Consumir de um plugin (desenvolvedor):** chame `\local_aihub\ai::generate_text()` atrás de um guard `class_exists()`, mantendo o próprio fallback para `core_ai` (ver *Como os consumidores usam*).

---

### 🧪 Testes Automatizados

O hub vem com uma suíte PHPUnit e Behat; todo push de CI roda contra a matriz (Moodle 4.5 → 5.x, PostgreSQL & MariaDB).

#### PHPUnit — Testes Unitários e de Integração

| Arquivo de teste | Casos | O que cobre |
|------------------|------:|-------------|
| `tests/local/keys_test.php` | 5 | Defaults de URL/modelo OpenAI; roundtrip get/save/clear da chave pessoal; `personal_keys_allowed` respeitando o toggle **e** a capability; resolução pessoal → site; `has_any_key` entre chaves pessoal/site |
| `tests/local/client_test.php` | 5 | Casos de SSRF do `is_safe_url` (http, loopback, faixa privada, DNS rebinding); `resolve_openai_url` anexa `/chat/completions`; tier pessoal vence o de site; fall-through de provedor dentro de um tier; sem chave → `success=false` (sem HTTP real) |
| `tests/local/usage_log_test.php` | 3 | Inserção do registro (com `keysource`, modelo vazio nulado); leitores por chave de site excluindo linhas pessoais/sem tag; filtragem recente por usuário |
| `tests/local/export_test.php` | 2 | Export do uso pessoal (todas as linhas, todas as colunas); export do relatório de chaves de site incluindo a coluna de usuário e excluindo o uso pessoal |
| `tests/ai_test.php` | 3 | Estados de `is_available`; uma geração bem-sucedida registra o componente chamador, a descrição e o tier da chave; uma geração com falha não registra nada |
| `tests/privacy_provider_test.php` | 4 | Declaração de metadata; descoberta de contexto/usuário; `export_user_data` (linhas do log + valor da chave **redigido**); deleção por usuário com isolamento |
| **Total** | **22** | |

```bash
vendor/bin/phpunit --testsuite local_aihub
```

#### Behat — Testes de Aceitação

| Arquivo de feature | Cenários | O que cobre |
|--------------------|---------:|-------------|
| `tests/behat/mykeys.feature` | 2 | Um provedor começa não configurado; salvar uma chave pessoal marca-a como configurada **sem revelar o valor guardado** |
| **Total** | **2** | |

```bash
php admin/tool/behat/cli/init.php
vendor/bin/behat --tags=@local_aihub --profile=chrome
```

---

### 🔐 Segurança e Conformidade

* Chaves pessoais protegidas por capability e opt-in (`local/aihub:usepersonalkey` **e** o toggle de site).
* Chaves pessoais **write-only** — nunca devolvidas à página após salvas, mesmo sob *Entrar como*.
* Chaves de site guardadas com `admin_setting_configpasswordunmask`; o guard SSRF protege o endpoint configurável.
* `require_sesskey()` no formulário de chaves; capability `local/aihub:viewusage` no relatório de uso.
* Cobertura completa da Privacy API (export/delete do log de uso e das preferências pessoais) com os três destinos externos declarados.

---

### 🔎 Divulgação de Serviço de Terceiros

O hub transmite o texto do prompt a um provedor de IA de terceiros **apenas quando uma chave está configurada e uma geração é solicitada**.

#### Uma chave de API é obrigatória?

Não. O plugin instala e funciona sem nenhuma chave — apenas informa que não há fonte disponível, e os consumidores caem para a própria integração com `core_ai`. Nenhuma requisição externa é feita até que uma chave de site ou pessoal seja definida.

#### Provedores suportados

- **Google Gemini** — https://ai.google.dev/
- **Groq** — https://console.groq.com/
- **APIs compatíveis com OpenAI** — qualquer provedor que siga o formato da API OpenAI (OpenRouter, modelos auto-hospedados via LM Studio, um proxy Ollama, etc.)

Esses serviços operam sob seus próprios termos de serviço e políticas de privacidade.

#### Como obter uma chave de API

As chaves de API são criadas diretamente no site oficial do provedor. Gemini e Groq atualmente oferecem camadas de uso gratuitas (as políticas de preço podem mudar). O hub **não** fornece chaves de API.

#### Onde as chaves são configuradas

1. **Chave pessoal** — definida por cada usuário em *Minhas chaves de IA* (preferências), quando as chaves pessoais estão habilitadas e o usuário tem a capability.
2. **Chave de site** — definida pelo admin em *Administração do site → Plugins → Plugins locais → Central de IA*.

#### Transmissão de Dados

Quando uma chave é resolvida para um provedor, o **texto do prompt é transmitido** à API desse provedor para gerar a resposta:

- **Google Gemini** — `generativelanguage.googleapis.com`
- **Groq** — `api.groq.com`
- **Compatível com OpenAI** — o endpoint configurado pelo admin ou usuário (padrão `api.openai.com`)

O hub guarda um **log de uso** (quem solicitou, qual componente, um rótulo curto do que foi gerado, provedor, modelo, tier da chave e horário), mas **não guarda prompts nem respostas da IA**. Todos os destinos externos estão declarados no Privacy provider do plugin.

---

### 📄 Licença

Este projeto está licenciado sob a **GNU General Public License v3 (GPLv3)**.

**Copyright:** 2026 Jean Lúcio
