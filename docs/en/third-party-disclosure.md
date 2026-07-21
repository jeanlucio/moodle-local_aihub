# 🔎 Third-party Service Disclosure

The hub transmits prompt text to a third-party AI provider **only when a key is configured and a generation is requested**.

### Is an API key required?

No. The plugin installs and runs without any key — it simply reports that no source is available, and consumers fall back to their own `core_ai` integration. No external request is made until a site or personal key is set.

### Supported providers

- **Google Gemini** — https://ai.google.dev/ — model: `gemini-flash-latest` (rolling alias, always Google's current Flash release; fixed, not configurable)
- **Groq** — https://console.groq.com/ — model: `openai/gpt-oss-120b` (fixed, not configurable; see *Groq model choice* below)
- **DeepSeek** — https://deepseek.com/ — model: `deepseek-v4-flash` (fixed, not configurable)
- **OpenAI-compatible APIs** — any provider that follows the OpenAI API format (OpenRouter, self-hosted models via LM Studio, an Ollama proxy, etc.) — model: configurable per key (site setting / personal key), defaults to `gpt-4o-mini` when left empty

These services operate under their own terms of service and privacy policies.

#### Groq model choice

The Groq slot calls a single fixed model, `openai/gpt-oss-120b`. Groq periodically deprecates
specific model IDs (e.g. `llama-3.3-70b-versatile`, decommissioned 2026-08-16) and expects
migration to a named replacement. Among the free-tier options Groq recommended for this migration,
`openai/gpt-oss-120b` was chosen over `qwen/qwen3.6-27b` for stronger instruction-following and
more reliable JSON-mode output across the varied prompts sent by different consumer plugins.

### How to obtain an API key

API keys are created directly on the provider's official website. Gemini, Groq and DeepSeek currently offer free usage tiers or trial credits (pricing policies may change). The hub does **not** provide API keys.

### Where keys are configured

1. **Personal key** — set by each user in *My AI keys* (preferences), when personal keys are enabled and the user has the capability.
2. **Site key** — set by the admin in *Site administration → Plugins → Local plugins → AI Hub*.

### Data Transmission

When a key resolves to a provider, the **prompt text is transmitted** to that provider's API to generate the response:

- **Google Gemini** — `generativelanguage.googleapis.com`
- **Groq** — `api.groq.com`
- **DeepSeek** — `api.deepseek.com`
- **OpenAI-compatible** — the endpoint configured by the admin or user (default `api.openai.com`)

The hub stores a **usage log** (who requested, which component, a short label of what was generated, provider, model, key tier and time) but **does not store prompts or AI responses**. All external destinations are declared in the plugin's Privacy provider.

### Demo credentials

Not applicable — no credentials are required to install or use the hub. Every AI feature stays
inert until a site or personal key is configured.
