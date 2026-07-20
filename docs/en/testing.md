# 🧪 Automated Tests

The hub ships with a PHPUnit and Behat suite; every CI push runs against the matrix (Moodle 4.5 → 5.x, PostgreSQL & MariaDB).

### PHPUnit — Unit & Integration Tests

| Test file | Cases | What is covered |
|-----------|------:|----------------|
| `tests/local/keys_test.php` | 6 | OpenAI base-URL/model defaults; personal-key get/save/clear roundtrip; personal OpenAI-compatible URL/model roundtrip; `personal_keys_allowed` honouring the toggle **and** the capability; key resolution personal → site; `has_any_key` across personal/site keys |
| `tests/local/client_test.php` | 6 | `is_safe_url` SSRF cases (http, loopback, private range, DNS rebinding); `resolve_openai_url` appends `/chat/completions`; personal tier wins over site; provider fall-through within a tier (Gemini → Groq, and Gemini/Groq → DeepSeek); no key → `success=false` (no real HTTP) |
| `tests/local/usage_log_test.php` | 3 | Record insert (with `keysource`, empty model nulled); site-scoped readers excluding personal/untagged rows; recent-per-user filtering |
| `tests/local/export_test.php` | 2 | Personal usage export (all rows, every column); site-keys report export including the user column and excluding personal usage |
| `tests/ai_test.php` | 4 | `is_available` states; a successful generation logs the calling component, the description and the key tier; a failed generation logs nothing; `report_usage()` writes a log row for a consumer that resolved its own key |
| `tests/privacy_provider_test.php` | 4 | Metadata declaration; context/user discovery; `export_user_data` (log rows + **redacted** key value); per-user deletion with isolation |
| **Total** | **25** | |

```bash
vendor/bin/phpunit --testsuite local_aihub
```

**Line coverage by class (PHPUnit + Xdebug):**

| Class | Line coverage |
|-------|:-------------:|
| `ai` | 85% |
| `local\client` | 31% |
| `local\export` | 83% |
| `local\keys` | 92% |
| `local\usage_log` | 85% |
| `privacy\provider` | 86% |
| **Overall** | **51%** |

`local\client`'s low number is by design, not a gap: `call_gemini()`, `call_groq()`, `call_deepseek()`,
`call_openai_compatible()` and `http_post()` make real HTTP calls to external providers, so they are
never unit-tested against the live network. `tests/fixtures/mock_client.php` overrides them to test
the key-resolution ladder in isolation; the transport methods themselves are verified by hand against
the real APIs before each release instead.

### Behat — Acceptance Tests

| Feature file | Scenarios | What is covered |
|--------------|----------:|----------------|
| `tests/behat/mykeys.feature` | 2 | A provider starts unconfigured; saving a personal key marks it configured **without revealing the stored value** |
| **Total** | **2** | |

```bash
php admin/tool/behat/cli/init.php
vendor/bin/behat --tags=@local_aihub --profile=chrome
```
