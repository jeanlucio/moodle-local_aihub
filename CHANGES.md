# Changelog — local_aihub

All notable changes to this plugin are documented here.

---

## [v1.2.0] — 2026-07-20

### Added
- **DeepSeek** support: site and personal API keys, added to the provider ladder between
  Groq and the OpenAI-compatible slot. Contributed by Smat Learn (Smartlearn-edu).

---

## [v1.1.0] — 2026-07-20

### Added
- `\local_aihub\ai::report_usage()`: lets a consumer that resolves a hub key directly
  (bypassing `generate_text()` for a request shape it does not support) still report that
  use in the site usage report.

### Changed
- Groq requests now use `openai/gpt-oss-120b` instead of the deprecated
  `llama-3.3-70b-versatile`, which Groq is decommissioning on 2026-08-16.

---

## [v1.0.0] — 2026-06-29

First public release.

### Added
- BYOK (bring your own key) broker for the institution's own Moodle plugins: stores
  **site** API keys (admin) and optional **personal** API keys (per user, opt-in) for
  **Gemini**, **Groq** and any **OpenAI-compatible** endpoint.
- One-call PHP facade `\local_aihub\ai::generate_text()` and `is_available()`, resolving
  the key **personal first, then site**. The hub does not wrap `core_ai`, so consumers keep
  their own fallback and a `core_ai`-only site needs no extra setup.
- Self-service **My AI keys** page: personal keys are **write-only** (never shown again
  after saving, only a configured / not-configured status), plus an optional
  OpenAI-compatible base URL and model, and the user's recent usage history.
- Administrator **site-keys usage report** with CSV and Excel download, listing the requests
  served by the site keys across all users, gated by `local/aihub:viewusage`.
- SSRF guard on the configurable OpenAI-compatible endpoint (HTTPS only; blocks
  loopback / link-local / private ranges with DNS anti-rebinding).
- Full Privacy provider (usage log and personal preferences, with the three external
  destinations declared) and a scheduled task that purges usage logs past a configurable
  retention.
