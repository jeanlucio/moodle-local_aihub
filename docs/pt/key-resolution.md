# 🔗 Resolução de chave (escada BYOK)

O hub resolve a chave **tier por tier** e para no primeiro tier que tiver uma chave:

| Tier | Fonte |
|------|-------|
| 1 | **Chave pessoal** — a chave do próprio usuário, quando as chaves pessoais estão habilitadas e o usuário tem `local/aihub:usepersonalkey` |
| 2 | **Chave de site** — a chave de admin definida nas configurações do hub |

**Dentro do tier escolhido**, os provedores são tentados na ordem **Gemini → Groq → DeepSeek → compatível com OpenAI** (a primeira chave encontrada é usada; se a chamada falhar, o próximo provedor do mesmo tier é tentado). Quando nenhum tier tem chave, `generate_text()` retorna `success = false` — o hub nunca cai para o `core_ai`; essa decisão é do consumidor.
