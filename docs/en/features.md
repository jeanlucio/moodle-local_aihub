# ✨ Features

* 🔑 **BYOK key store:** **site** keys (admin) and optional **personal** keys (per user, opt-in) for **Gemini**, **Groq**, **DeepSeek** and any **OpenAI-compatible** endpoint.
* 🪜 **Personal → site resolution:** the hub tries the user's own key first, then the site key, exposing a single result to the caller.
* 🧩 **One-call facade:** `\local_aihub\ai::generate_text()` and `is_available()` — consumed by sibling plugins through a soft dependency (`class_exists`), with no hard dependency entry.
* 🚫 **Does not wrap `core_ai`:** each consumer keeps its own `core_ai` fallback, so a site that already has `core_ai` configured needs **no extra setup** — the hub stays optional.
* 👁️ **Write-only personal keys:** once saved, a personal key is **never returned to the browser** — the page only reports a *configured / not configured* status, closing the read vector via *Log in as*.
* 🧑‍💻 **Self-service page:** *My AI keys* (in the user's preferences) to add/replace/remove personal keys and review one's own recent usage.
* 📊 **Administrator usage report:** every request served by the **site keys**, across all users, with **CSV / Excel** download — gated by `local/aihub:viewusage`.
* 🛡️ **SSRF guard:** the configurable OpenAI-compatible endpoint is forced to HTTPS, with loopback / link-local / private ranges blocked and DNS A/AAAA anti-rebinding.
* 🧾 **Usage log + retention task:** one row per request (user, requesting component, what was generated, provider, model, key tier) and a scheduled task that purges logs past a configurable retention.
* 🔒 **Privacy-complete:** full Privacy provider for the usage log and personal preferences, with the three external destinations declared.
