# Changelog — local_aihub

All notable changes to this plugin are documented here.

---

## [v1.3.4] — 2026-10-10

### Security
- Requests to the AI providers now verify the server's certificate. Moodle's HTTP client leaves
  that check off by default, so someone on the network path could present a certificate of their
  own and receive the API key sent with the request.
- Requests to an OpenAI-compatible endpoint no longer follow redirects, and they connect only to
  the addresses the endpoint was checked against. Before, a public endpoint could redirect the
  request, API key included, to a host that was never checked, or change its DNS answer between
  the check and the connection.
- The endpoint check now also refuses internal addresses it used to let through: IPv6 addresses
  that carry an IPv4 one (such as `::ffff:127.0.0.1`), IPv6 addresses written in brackets, the
  shared address space `100.64.0.0/10` where some clouds serve instance metadata, and a host that
  does not resolve. Hosts and ports blocked in the site's HTTP security settings are refused when
  the endpoint is saved.

### Changed
- DeepSeek requests now use the `deepseek-flash` model. DeepSeek no longer lists
  `deepseek-v4-flash`, which only answered through a temporary compatibility route.
- An OpenAI-compatible endpoint with a self-signed certificate, on a port the site does not allow
  (only 80 and 443 by default), or known only to the server's hosts file is no longer accepted.

### Fixed
- A plugin reporting its own usage through `\local_aihub\ai::report_usage()` with a provider name
  longer than 40 characters, or a key source longer than 20, made the usage log insert fail.
  Values are now cut to the size of their columns.

## [v1.3.3] — 2026-10-08

### Added
- The history on *My AI keys*, and its CSV and Excel downloads, now say whether each request
  used your own key or the site's key. Entries recorded before this version leave it blank.

### Fixed
- Teachers whose role comes from a course enrolment, which is the usual case, could not use
  personal AI keys: the capability was only looked for at the site level, so *My AI keys*, its
  link in Preferences and the key itself were available to administrators and to managers with
  a site-level role, and to nobody else. The capability now counts when it is held at the site
  level or in any course.
- An answer that arrives without any text, such as a prompt the provider blocked, a generation
  it interrupted or an error page served by a gateway, was recorded as a success and stopped
  the search for another provider. It is now a failed attempt that carries the reason the
  provider gave, so the next provider is tried.
- An OpenAI-compatible endpoint that requests refuse (plain http, localhost, a private network)
  was accepted when saved and then ignored on every request, leaving nothing in the usage log.
  It is now refused when saved, in the site settings and in *My AI keys*, with the reason, and
  an attempt made with one is recorded as a failure. An address that cannot be read no longer
  erases the endpoint that was already saved.
- A long model name or description made the usage log insert fail after the text had already
  been generated, losing it. Values are now cut to the size of their columns, and the personal
  model name is limited to 100 characters.
- The personal data export now includes the result and the error message of each attempt,
  which the privacy metadata already declared.
- Exporting the site usage report no longer loads the whole log into memory.
- On Moodle 4.5 the red "failed" badge showed dark text on red, about 3:1; it now has white
  text, 5.3:1.

## [v1.3.2] — 2026-09-27

### Fixed
- On Moodle 4.5, a user holding `local/aihub:viewusage` without full site administration
  rights (a manager, by default) was denied access to the site AI usage report, even through
  its direct URL, and saw a "parent does not exist!" debugging notice. The report now appears
  under Site administration → Reports for them.

## [v1.3.1] — 2026-09-25

### Confirmed
- Tested and confirmed compatible with Moodle 5.3.

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
