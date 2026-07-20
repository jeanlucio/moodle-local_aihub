# 📖 Usage

1. **Configure site keys (admin):** *Site administration → Plugins → Local plugins → AI Hub → Settings*. Set any of the Gemini, Groq, DeepSeek or OpenAI-compatible keys, and (optionally) enable **personal API keys**.
2. **Add a personal key (user):** users who have the `local/aihub:usepersonalkey` capability get a **My AI keys** entry in their preferences, where they store their own key (write-only) and see their recent usage.
3. **Review site-key usage (admin):** the **AI usage report** (under the AI Hub category, capability `local/aihub:viewusage`) lists every request served by the site keys across all users, with CSV and Excel download.
4. **Consume from a plugin (developer):** call `\local_aihub\ai::generate_text()` behind a `class_exists()` guard, keeping your own `core_ai` fallback (see *How consumers use it*).
