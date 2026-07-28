# 🧪 Testes Automatizados

O hub vem com uma suíte PHPUnit e Behat; todo push de CI roda contra a matriz (Moodle 4.5 → 5.x, PostgreSQL & MariaDB).

### PHPUnit — Testes Unitários e de Integração

| Arquivo de teste | Casos | O que cobre |
|------------------|------:|-------------|
| `tests/local/keys_test.php` | 8 | Defaults de URL/modelo OpenAI, incluindo o fallback que só é alcançado quando um admin apaga a configuração; roundtrip get/save/clear da chave pessoal; roundtrip da URL/modelo pessoal compatível com OpenAI; `personal_keys_allowed` respeitando o toggle **e** a capability; resolução pessoal → site; `has_any_key` entre chaves pessoal e de site |
| `tests/local/client_test.php` | 21 | Casos de SSRF do `is_safe_url` (http, loopback, faixa privada, host ausente, IP público); branch de DNS rebinding simulado via `dns_stub_client`; `resolve_openai_url` anexa `/chat/completions`; tier pessoal vence o de site; fall-through de provedor dentro de um tier (Gemini → Groq → DeepSeek); as tentativas são devolvidas, incluindo uma falha encoberta por um sucesso posterior e tentativas abrangendo os dois tiers; a requisição que cada provedor monta — os campos próprios de instrução de sistema e modo JSON do Gemini versus o formato chat-completions, endpoint e modelo por provedor; a perna do OpenAI resolvendo URL e modelo pessoal-depois-site campo a campo; parsing de resposta por provedor, extração de erro, falha de transporte e corpo inesperado |
| `tests/local/usage_log_test.php` | 8 | Inserção do registro (com `keysource`, modelo vazio nulado); uma tentativa com falha guardando seu motivo; o filtro de somente falhas nos dois leitores; leitores por chave de site excluindo linhas pessoais/sem tag; o leitor de export devolvendo todas as linhas, além do limite da tela; `display_name` nomeando uma pessoa, o sistema ou um id que não resolve |
| `tests/local/export_test.php` | 3 | Export do uso pessoal (todas as linhas, todas as colunas); uma tentativa com falha exportando situação e motivo; export do relatório de chaves de site incluindo a coluna de usuário e excluindo o uso pessoal |
| `tests/ai_test.php` | 6 | Estados de `is_available`; uma geração bem-sucedida registra o componente chamador, a descrição e o tier da chave; nenhum provedor chamado não registra nada; **toda tentativa é registrada, então uma falha encoberta por um sucesso posterior deixa duas linhas**; `report_usage()` gravando uma linha para um consumidor que resolveu a própria chave, com sucesso ou falha |
| `tests/db_upgrade_test.php` | 3 | O passo de upgrade adiciona a coluna com o tipo, o tamanho e a nulabilidade que o `install.xml` declara; linhas existentes mantêm seus dados e são lidas como sucessos; uma segunda execução é no-op |
| `tests/lib_test.php` | 4 | A entrada de navegação é adicionada na página de preferências do próprio usuário, e não na de outra pessoa, nem sem a capability, nem com as chaves pessoais desligadas no site |
| `tests/privacy_provider_test.php` | 9 | Declaração de metadata; descoberta de contexto/usuário; `export_user_data` (linhas do log + valor da chave **redigido**); os leitores recusando um contexto que não é de usuário e um usuário sem linhas; deleção por usuário, deleção do contexto inteiro e deleção por lista aprovada, cada uma com isolamento |
| `tests/output/mykeys_test.php` | 4 | Status de chave pessoal por provedor (definida/não definida), sem nunca colocar o valor da chave no contexto do template; linhas de uso com o ícone de provedor correto, incluindo o fallback para provedor não reconhecido; uma tentativa com falha exibindo seu motivo; a página renderizando de ponta a ponta sem chave armazenada no HTML |
| `tests/output/report_test.php` | 7 | Linhas do relatório de chaves de site com o nome do usuário solicitante e o ícone de provedor correto, excluindo linhas de chave pessoal; estado vazio; linhas com falha carregando seu motivo; a visão de somente falhas e sua própria mensagem de vazio; uma requisição sem usuário atribuída ao sistema; uma linha gravada antes de as colunas de falha existirem ainda sendo lida como sucesso; a página renderizando de ponta a ponta |
| `tests/task/purge_old_logs_test.php` | 3 | Linhas mais antigas que a retenção configurada são apagadas, linhas mais recentes são mantidas; retenção 0 mantém todas as linhas indefinidamente; a tarefa se nomeia a partir de uma string de idioma |
| **Total** | **76** | |

```bash
vendor/bin/phpunit --testsuite local_aihub
```

**Cobertura de linhas por classe (PHPUnit + Xdebug):**

| Classe | Cobertura de linhas |
|--------|:--------------------:|
| `ai` | 95% |
| `local\client` | 91% |
| `local\export` | 86% |
| `local\keys` | 100% |
| `local\usage_log` | 100% |
| `output\mykeys` | 100% |
| `output\renderer` | 100% |
| `output\report` | 100% |
| `privacy\provider` | 100% |
| `task\purge_old_logs` | 100% |
| `lib.php` | 100% |
| **Total** | **96%** |

A camada de transporte ficava fora da suíte, sob o argumento de que faz chamadas HTTP reais. Esse
argumento cobria mais terreno do que devia: montar uma requisição e interpretar uma resposta não
são operações de rede, e deixá-las para o dublê significava que a parte do plugin que conversa com
os provedores — inclusive o código que transforma uma recusa da API na frase que o administrador
lê — era a única que nada verificava. Duas fixtures alcançam isso agora: `recording_client` roda os
`call_*` reais e captura o que eles montaram, e `stub_transport_client` responde a partir de um
transporte simulado, para o parsing rodar de verdade, através de uma única costura (`make_curl()`).

O que continua descoberto está genuinamente fora do alcance de um teste unitário:

- **`local\export`** — `download()` e `download_site()` repassam para a API de dataformat e então
  chamam `die()`. As linhas que eles carregam estão cobertas; a resposta em si é coberta pelo Behat
  abaixo.
- **`local\client`** — `resolve_dns()` faz uma consulta DNS real, e `make_curl()` existe para ser
  substituído. A decisão que depende da consulta, incluindo o branch de DNS rebinding, está
  totalmente coberta via `dns_stub_client`.
- **`ai`** — uma linha: a guarda que recusa `set_client_for_testing()` fora de um teste, que por
  definição não pode rodar dentro de um.

### Behat — Testes de Aceitação

| Arquivo de feature | Cenários | O que cobre |
|--------------------|---------:|-------------|
| `tests/behat/mykeys.feature` | 5 | Um provedor começa não configurado; salvar uma chave pessoal marca-a como configurada **sem revelar o valor guardado**; uma tentativa com falha do próprio usuário exibindo seu motivo; o histórico pessoal baixando em CSV e em Excel |
| `tests/behat/report.feature` | 6 | As duas tentativas de uma mesma requisição listadas, com o motivo pelo qual a primeira falhou; o filtro de falhas escondendo o que funcionou e voltando à lista completa; uma requisição feita fora de uma sessão atribuída em vez de exibida como id; o relatório baixando em CSV e em Excel |
| **Total** | **11** | |

```bash
php admin/tool/behat/cli/init.php
vendor/bin/behat --tags=@local_aihub --profile=chrome
```
