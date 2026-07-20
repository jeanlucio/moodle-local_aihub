# 🧪 Testes Automatizados

O hub vem com uma suíte PHPUnit e Behat; todo push de CI roda contra a matriz (Moodle 4.5 → 5.x, PostgreSQL & MariaDB).

### PHPUnit — Testes Unitários e de Integração

| Arquivo de teste | Casos | O que cobre |
|------------------|------:|-------------|
| `tests/local/keys_test.php` | 6 | Defaults de URL/modelo OpenAI; roundtrip get/save/clear da chave pessoal; roundtrip da URL/modelo pessoal compatível com OpenAI; `personal_keys_allowed` respeitando o toggle **e** a capability; resolução pessoal → site; `has_any_key` entre chaves pessoal/site |
| `tests/local/client_test.php` | 9 | Casos de SSRF do `is_safe_url` (http, loopback, faixa privada, IP público); branch de DNS rebinding simulado via `dns_stub_client` — IP resolvido privado é bloqueado, IP resolvido público passa, sem registros passa; `resolve_openai_url` anexa `/chat/completions`; tier pessoal vence o de site; fall-through de provedor dentro de um tier (Gemini → Groq, e Gemini/Groq → DeepSeek); sem chave → `success=false` (sem HTTP real) |
| `tests/local/usage_log_test.php` | 3 | Inserção do registro (com `keysource`, modelo vazio nulado); leitores por chave de site excluindo linhas pessoais/sem tag; filtragem recente por usuário |
| `tests/local/export_test.php` | 2 | Export do uso pessoal (todas as linhas, todas as colunas); export do relatório de chaves de site incluindo a coluna de usuário e excluindo o uso pessoal |
| `tests/ai_test.php` | 4 | Estados de `is_available`; uma geração bem-sucedida registra o componente chamador, a descrição e o tier da chave; uma geração com falha não registra nada; `report_usage()` grava uma linha de log para um consumidor que resolveu a própria chave |
| `tests/privacy_provider_test.php` | 4 | Declaração de metadata; descoberta de contexto/usuário; `export_user_data` (linhas do log + valor da chave **redigido**); deleção por usuário com isolamento |
| `tests/output/mykeys_test.php` | 2 | Status de chave pessoal por provedor (definida/não definida), sem nunca colocar o valor da chave no contexto do template; linhas de uso recente com o ícone de provedor correto, incluindo o fallback pra provedor não reconhecido |
| `tests/output/report_test.php` | 2 | Linhas do relatório de chaves de site com o nome do usuário solicitante e o ícone de provedor correto, excluindo linhas de chave pessoal; estado vazio quando não há uso de chaves de site |
| `tests/task/purge_old_logs_test.php` | 2 | Linhas mais antigas que a retenção configurada são apagadas, linhas mais recentes são mantidas; retenção 0 mantém todas as linhas indefinidamente |
| **Total** | **34** | |

```bash
vendor/bin/phpunit --testsuite local_aihub
```

**Cobertura de linhas por classe (PHPUnit + Xdebug):**

| Classe | Cobertura de linhas |
|--------|:--------------------:|
| `ai` | 85% |
| `local\client` | 36% |
| `local\export` | 83% |
| `local\keys` | 92% |
| `local\usage_log` | 85% |
| `output\mykeys` | 100% |
| `output\renderer` | 0% |
| `output\report` | 100% |
| `privacy\provider` | 86% |
| `task\purge_old_logs` | 86% |
| **Total** | **73%** |

Duas classes não são testadas por unidade por completo, ambas pelo mesmo motivo — as linhas não
cobertas são chamadas de rede reais, não lógica de negócio:

- **`local\client`**: `call_gemini()`, `call_groq()`, `call_deepseek()`, `call_openai_compatible()`
  e `http_post()` fazem chamadas HTTP reais a provedores externos, então nunca são testadas por
  unidade contra a rede real. O `tests/fixtures/mock_client.php` sobrescreve esses métodos pra
  testar a escada de resolução de chave isoladamente; os próprios métodos de transporte são
  verificados manualmente contra as APIs reais antes de cada release. O branch de DNS rebinding do
  `is_safe_url()` caía na mesma categoria (uma chamada real a `dns_get_record()`), mas a etapa de
  resolução foi extraída pra `resolve_dns()`, que o `tests/fixtures/dns_stub_client.php`
  sobrescreve — então esse branch agora está totalmente coberto sem nenhuma consulta DNS real.
- **`output\renderer`**: seu único método delega uma única linha pra `render_from_template()`.
  Verificar o HTML resultante é papel da suíte Behat abaixo (`mykeys.feature`), não do PHPUnit.

### Behat — Testes de Aceitação

| Arquivo de feature | Cenários | O que cobre |
|--------------------|---------:|-------------|
| `tests/behat/mykeys.feature` | 2 | Um provedor começa não configurado; salvar uma chave pessoal marca-a como configurada **sem revelar o valor guardado** |
| **Total** | **2** | |

```bash
php admin/tool/behat/cli/init.php
vendor/bin/behat --tags=@local_aihub --profile=chrome
```
