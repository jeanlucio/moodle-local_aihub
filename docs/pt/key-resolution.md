# 🔗 Resolução de chave (escada BYOK)

O hub tenta as chaves **grupo por grupo**: primeiro as chaves pessoais do usuário, depois as do site. Se nenhum provedor do primeiro grupo responder, tenta o grupo seguinte.

| Tier | Fonte |
|------|-------|
| 1 | **Chave pessoal** — a chave do próprio usuário, quando as chaves pessoais estão habilitadas e o usuário tem `local/aihub:usepersonalkey` |
| 2 | **Chave de site** — a chave de admin definida nas configurações do hub |

**Dentro de cada grupo**, os provedores são tentados nesta ordem:

1. a **ordem do próprio usuário**, arrastada em *Minhas chaves de IA* (se houver);
2. a **ordem do site**, arrastada pelo administrador na página *Ordem dos provedores* (se houver);
3. o que ainda faltar, na ordem de fábrica **Gemini → Groq → DeepSeek → compatível com OpenAI**.

Um provedor sem chave é pulado. A ordem organiza os provedores *dentro* de um grupo; ela nunca coloca as chaves do site na frente das chaves pessoais do usuário. Sem nenhuma escolha, a ordem é a de fábrica. Quando nenhum grupo tem chave, `generate_text()` retorna `success = false` — o hub nunca cai para o `core_ai`; essa decisão é do consumidor.

## Provedores que falharam recentemente

Cada requisição começa do zero, então um provedor que esgota o tempo custaria o tempo limite inteiro em toda requisição. O hub, por isso, lembra por alguns minutos (configuração **Minutos para lembrar de um provedor com falha**, 5 por padrão, `0` desliga) que um provedor falhou, e o tenta **por último** da próxima vez.

| Falha | Lembrada? |
|-------|:---------:|
| Sem resposta (tempo esgotado, conexão), HTTP 5xx, HTTP 429 (cota) | Sim |
| HTTP 401, 403, 404 (chave ou endpoint recusado) | Sim, até as configurações ou as chaves do usuário mudarem |
| HTTP 400, 413, 422 e outros 4xx; uma resposta sem texto | **Não** — dependem do pedido, não do provedor |

- A memória só **move** um provedor para o fim do seu grupo. Se todos falharam recentemente, todos continuam sendo tentados, então uma requisição nunca falha por causa dela.
- As chaves do site compartilham uma memória; as chaves pessoais de cada usuário têm a sua, então a chave com problema de um usuário não atrasa os outros.
- Uma resposta limpa a marca na hora. Salvar as chaves do site, o endpoint ou o modelo (administrador), ou as chaves, o endpoint ou o modelo de um usuário, limpa a memória correspondente.
