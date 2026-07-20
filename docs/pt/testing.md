# 🧪 Testes Automatizados

O hub vem com uma suíte PHPUnit e Behat; todo push de CI roda contra a matriz (Moodle 4.5 → 5.x, PostgreSQL & MariaDB).

### PHPUnit — Testes Unitários e de Integração

| Arquivo de teste | Casos | O que cobre |
|------------------|------:|-------------|
| `tests/local/keys_test.php` | 6 | Defaults de URL/modelo OpenAI; roundtrip get/save/clear da chave pessoal; roundtrip da URL/modelo pessoal compatível com OpenAI; `personal_keys_allowed` respeitando o toggle **e** a capability; resolução pessoal → site; `has_any_key` entre chaves pessoal/site |
| `tests/local/client_test.php` | 6 | Casos de SSRF do `is_safe_url` (http, loopback, faixa privada, DNS rebinding); `resolve_openai_url` anexa `/chat/completions`; tier pessoal vence o de site; fall-through de provedor dentro de um tier (Gemini → Groq, e Gemini/Groq → DeepSeek); sem chave → `success=false` (sem HTTP real) |
| `tests/local/usage_log_test.php` | 3 | Inserção do registro (com `keysource`, modelo vazio nulado); leitores por chave de site excluindo linhas pessoais/sem tag; filtragem recente por usuário |
| `tests/local/export_test.php` | 2 | Export do uso pessoal (todas as linhas, todas as colunas); export do relatório de chaves de site incluindo a coluna de usuário e excluindo o uso pessoal |
| `tests/ai_test.php` | 4 | Estados de `is_available`; uma geração bem-sucedida registra o componente chamador, a descrição e o tier da chave; uma geração com falha não registra nada; `report_usage()` grava uma linha de log para um consumidor que resolveu a própria chave |
| `tests/privacy_provider_test.php` | 4 | Declaração de metadata; descoberta de contexto/usuário; `export_user_data` (linhas do log + valor da chave **redigido**); deleção por usuário com isolamento |
| **Total** | **25** | |

```bash
vendor/bin/phpunit --testsuite local_aihub
```

**Cobertura de linhas por classe (PHPUnit + Xdebug):**

| Classe | Cobertura de linhas |
|--------|:--------------------:|
| `ai` | 85% |
| `local\client` | 31% |
| `local\export` | 83% |
| `local\keys` | 92% |
| `local\usage_log` | 85% |
| `privacy\provider` | 86% |
| **Total** | **51%** |

O número baixo de `local\client` é intencional, não uma lacuna: `call_gemini()`, `call_groq()`,
`call_deepseek()`, `call_openai_compatible()` e `http_post()` fazem chamadas HTTP reais a
provedores externos, então nunca são testados por unidade contra a rede real. O
`tests/fixtures/mock_client.php` sobrescreve esses métodos pra testar a escada de resolução de
chave isoladamente; os próprios métodos de transporte são verificados manualmente contra as APIs
reais antes de cada release.

### Behat — Testes de Aceitação

| Arquivo de feature | Cenários | O que cobre |
|--------------------|---------:|-------------|
| `tests/behat/mykeys.feature` | 2 | Um provedor começa não configurado; salvar uma chave pessoal marca-a como configurada **sem revelar o valor guardado** |
| **Total** | **2** | |

```bash
php admin/tool/behat/cli/init.php
vendor/bin/behat --tags=@local_aihub --profile=chrome
```
