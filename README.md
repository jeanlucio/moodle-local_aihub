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

**AI Hub** is a small **BYOK (bring your own key)** broker for Moodle. It lets the institution's own plugins generate text through shared AI API keys — Gemini, Groq, DeepSeek or any OpenAI-compatible endpoint — without each plugin reimplementing the HTTP transport, the SSRF guard, the provider ladder or a key store.

It is a **service plugin for developers**: it exposes a one-call PHP facade that any sibling plugin consumes with a `class_exists()` guard. It also gives administrators a place to store site keys, lets users bring their own personal key (opt-in), and reports how the site keys are being used.

📚 **[Full documentation](https://jeanlucio.github.io/moodle-local_aihub/)** — features, the developer facade, the BYOK key-resolution ladder, usage guide, the full test suite, and security & third-party disclosure details.

### 🔒 Third-party Service Disclosure

An API key is **optional** and the plugin installs and works without one. When a key is
configured and a generation is requested, the prompt text is sent to the matching provider
(Gemini, Groq, DeepSeek, or the configured OpenAI-compatible endpoint) — the hub never contacts
a provider on its own.

* **Cost:** None required to install or use the hub itself. Gemini, Groq and DeepSeek currently
  offer free usage tiers or trial credits (pricing policies may change); any cost beyond that is
  set entirely by the chosen provider.
* **API keys:** Not provided by the hub. Obtain a key directly from the provider's own website
  and configure it as a site key at **Site administration > Plugins > Local plugins > AI Hub**,
  or, if personal keys are enabled, as a personal key in *My AI keys* (user preferences).
* **Demo credentials:** Not applicable — no credentials are required to install or use the hub;
  every AI feature stays inert until a key is configured.

Full disclosure, including exact model ids and data destinations:
[Third-party Service Disclosure](https://jeanlucio.github.io/moodle-local_aihub/#third-party-disclosure).

### 📦 Requirements

| Component | Version |
|-----------|---------|
| Moodle    | 4.5+    |
| PHP       | 8.1+    |

### 🛠️ Installation & Configuration

1. Download the `.zip` file or clone this repository.
2. Extract the folder into your Moodle `local/` directory.
3. Rename the folder to `aihub` (if necessary).
   Final path:
   `your-moodle/local/aihub/`
4. Visit **Site administration > Notifications** to complete installation.

Configuring a provider key is optional — the plugin installs and works without one, simply
reporting that no source is available to any consumer plugin. To enable AI generation, set a
site key at **Site administration > Plugins > Local plugins > AI Hub**, as covered in the
[Usage](https://jeanlucio.github.io/moodle-local_aihub/#usage) section of the full
documentation.

### 🆘 Support

Found a bug or have a question? Open an issue on the
[issue tracker](https://github.com/jeanlucio/moodle-local_aihub/issues).

### 📄 License

This project is licensed under the **GNU General Public License v3 (GPLv3)**.

**Copyright:** 2026 Jean Lúcio

### 👤 Maintainer

Maintained by [Jean Lúcio](https://github.com/jeanlucio).

[⬆️ Back to top](#english)

---

## Português

A **Central de IA** é um pequeno intermediário **BYOK (traga sua própria chave)** para o Moodle. Ela permite que os plugins da própria instituição gerem texto através de chaves de API de IA compartilhadas — Gemini, Groq, DeepSeek ou qualquer endpoint compatível com OpenAI — sem que cada plugin reimplemente o transporte HTTP, o guard de SSRF, a escada de provedores ou um armazenamento de chaves.

É um **plugin de serviço para desenvolvedores**: expõe uma fachada PHP de uma chamada que qualquer plugin irmão consome com um guard `class_exists()`. Também dá ao administrador um lugar para guardar chaves de site, permite que o usuário traga sua chave pessoal (opt-in) e relata como as chaves do site estão sendo usadas.

📚 **[Documentação completa](https://jeanlucio.github.io/moodle-local_aihub/pt.html)** — funcionalidades, a fachada para desenvolvedores, a escada de resolução de chave BYOK, guia de uso, a suíte completa de testes, e detalhes de segurança e divulgação a terceiros.

### 🔒 Divulgação de Serviço de Terceiros

Uma chave de API é **opcional** e o plugin instala e funciona sem nenhuma. Quando uma chave está
configurada e uma geração é solicitada, o texto do prompt é enviado ao provedor correspondente
(Gemini, Groq, DeepSeek, ou o endpoint compatível com OpenAI configurado) — o hub nunca contata um
provedor por conta própria.

* **Custo:** Nenhum é exigido para instalar ou usar o hub em si. Gemini, Groq e DeepSeek
  atualmente oferecem camadas gratuitas de uso ou créditos de teste (políticas de preço podem
  mudar); qualquer custo além disso é definido inteiramente pelo provedor escolhido.
* **Chaves de API:** Não são fornecidas pelo hub. Obtenha uma chave diretamente no site do
  provedor e configure-a como chave de site em **Administração do site > Plugins > Plugins
  locais > AI Hub**, ou, se as chaves pessoais estiverem habilitadas, como chave pessoal em
  *Minhas chaves de IA* (preferências do usuário).
* **Credenciais de demonstração:** Não aplicável — nenhuma credencial é exigida para instalar ou
  usar o hub; todo recurso de IA fica inerte até que uma chave seja configurada.

Divulgação completa, com os ids de modelo exatos e destinos dos dados:
[Divulgação de Serviço de Terceiros](https://jeanlucio.github.io/moodle-local_aihub/pt.html#third-party-disclosure).

### 📦 Requisitos

| Componente | Versão |
|------------|--------|
| Moodle     | 4.5+   |
| PHP        | 8.1+   |

### 🛠️ Instalação e Configuração

1. Baixe o arquivo `.zip` ou clone este repositório.
2. Extraia a pasta para o diretório `local/` do seu Moodle.
3. Renomeie a pasta para `aihub` (se necessário).
   Caminho final:
   `seu-moodle/local/aihub/`
4. Acesse **Administração do site > Notificações** para concluir a instalação.

Configurar uma chave de provedor é opcional — o plugin instala e funciona sem nenhuma, apenas
informando que nenhuma fonte está disponível para os plugins consumidores. Para habilitar a
geração por IA, defina uma chave de site em **Administração do site > Plugins > Plugins locais
> AI Hub**, conforme explicado na seção
[Como Usar](https://jeanlucio.github.io/moodle-local_aihub/pt.html#usage) da documentação
completa.

### 🆘 Suporte

Encontrou um bug ou tem alguma dúvida? Abra uma issue no
[rastreador de issues](https://github.com/jeanlucio/moodle-local_aihub/issues).

### 📄 Licença

Este projeto é licenciado sob a **GNU General Public License v3 (GPLv3)**.

**Copyright:** 2026 Jean Lúcio

### 👤 Mantenedor

Mantido por [Jean Lúcio](https://github.com/jeanlucio).

[⬆️ Voltar ao topo](#português)
