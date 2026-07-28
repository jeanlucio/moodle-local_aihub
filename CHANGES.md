# Changelog — local_aihub

All notable changes to this plugin are documented here.

---

## [v1.3.0] — 2026-07-28

### Added
- Failed provider calls are now recorded, with the reason the provider itself gave. A
  request that falls through to another provider leaves one row per attempt, so a key that
  has stopped working no longer hides behind whichever provider answered it.
- Filters on the administrator usage report: all attempts, failures only, or successes only.
- An outcome column on the usage report and on the user's own history, showing the reason
  under a failed attempt. Both CSV and Excel exports carry the same two columns.

### Changed
- The usage log records one row per **provider call** rather than one per request. A single
  generation that tried two providers therefore appears twice, once for each.
- `\local_aihub\ai::report_usage()` accepts the outcome and a failure reason, so a consumer
  that resolves a hub key itself can report its failures too. Both arguments are optional
  and default to a success, leaving existing callers unaffected.

### Fixed
- Requests made outside a user session (cron or a CLI script) showed a bare `0` in the
  report's user column; they are now attributed to the system.
- The usage log's privacy metadata declared seven of its eight columns; it now declares
  every column it stores, including the outcome and the failure reason.

---

## [v1.2.1] — 2026-07-21

### Fixed
- Administrator usage report: the DeepSeek icon was missing from the report's provider
  icon list, so DeepSeek-served requests showed no provider icon.

### Changed
- Replaced the plugin icon with a diagram glyph that better represents what the plugin
  does.

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
