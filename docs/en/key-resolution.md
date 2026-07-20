# 🔗 Key resolution (BYOK ladder)

The hub resolves a key **tier by tier** and stops at the first tier that holds a key:

| Tier | Source |
|------|--------|
| 1 | **Personal key** — the user's own key, when personal keys are enabled and the user has `local/aihub:usepersonalkey` |
| 2 | **Site key** — the admin key set in the hub settings |

**Within the chosen tier**, providers are tried in the order **Gemini → Groq → DeepSeek → OpenAI-compatible** (first key found is used; if its call fails, the next provider in the same tier is tried). When no tier holds a key, `generate_text()` returns `success = false` — the hub never falls back to `core_ai`; that decision belongs to the consumer.
