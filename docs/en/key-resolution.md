# 🔗 Key resolution (BYOK ladder)

The hub tries the keys **group by group**: first the user's personal keys, then the site's. If no provider in the first group answers, the next group is tried.

| Tier | Source |
|------|--------|
| 1 | **Personal key** — the user's own key, when personal keys are enabled and the user has `local/aihub:usepersonalkey` |
| 2 | **Site key** — the admin key set in the hub settings |

**Within each group**, providers are tried in this order:

1. the **user's own order**, dragged on *My AI keys* (if any);
2. the **site's order**, dragged by the administrator on the *Provider order* page (if any);
3. whatever is still missing, in the built-in order **Gemini → Groq → DeepSeek → OpenAI-compatible**.

A provider without a key is skipped. The order arranges the providers *inside* a group; it never puts the site keys ahead of the user's personal ones. With nothing chosen the order is the built-in one. When no group holds a key, `generate_text()` returns `success = false` — the hub never falls back to `core_ai`; that decision belongs to the consumer.

## Providers that failed recently

Each request starts from scratch, so a provider that times out would otherwise cost its full timeout on every request. The hub therefore remembers, for a few minutes (setting **Minutes to remember a failing provider**, 5 by default, `0` turns it off), that a provider failed, and tries it **last** the next time.

| Failure | Remembered? |
|---------|:-----------:|
| No answer (timeout, connection), HTTP 5xx, HTTP 429 (quota) | Yes |
| HTTP 401, 403, 404 (key or endpoint refused) | Yes, until the settings or the user's keys change |
| HTTP 400, 413, 422 and other 4xx; an answer with no text | **No** — they depend on the request, not on the provider |

- The memory only **moves** a provider to the end of its group. If every provider failed recently, all of them are still tried, so a request never fails because of it.
- The site's keys share one memory; each user's personal keys have their own, so one user's failing key does not slow anyone else down.
- An answer clears the mark at once. Saving the site keys, the endpoint or the model (administrator), or a user's own keys, endpoint or model, clears the matching memory.
