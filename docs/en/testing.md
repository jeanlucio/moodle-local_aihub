# 🧪 Automated Tests

The hub ships with a PHPUnit and Behat suite; every CI push runs against the matrix (Moodle 4.5 → 5.x, PostgreSQL & MariaDB).

### PHPUnit — Unit & Integration Tests

| Test file | Cases | What is covered |
|-----------|------:|----------------|
| `tests/local/keys_test.php` | 6 | OpenAI base-URL/model defaults; personal-key get/save/clear roundtrip; personal OpenAI-compatible URL/model roundtrip; `personal_keys_allowed` honouring the toggle **and** the capability; key resolution personal → site; `has_any_key` across personal/site keys |
| `tests/local/client_test.php` | 9 | `is_safe_url` SSRF cases (http, loopback, private range, public IP); DNS-rebinding branch stubbed via `dns_stub_client` — a resolved private IP is blocked, a resolved public IP passes, no records resolves through; `resolve_openai_url` appends `/chat/completions`; personal tier wins over site; provider fall-through within a tier (Gemini → Groq, and Gemini/Groq → DeepSeek); no key → `success=false` (no real HTTP) |
| `tests/local/usage_log_test.php` | 3 | Record insert (with `keysource`, empty model nulled); site-scoped readers excluding personal/untagged rows; recent-per-user filtering |
| `tests/local/export_test.php` | 2 | Personal usage export (all rows, every column); site-keys report export including the user column and excluding personal usage |
| `tests/ai_test.php` | 4 | `is_available` states; a successful generation logs the calling component, the description and the key tier; a failed generation logs nothing; `report_usage()` writes a log row for a consumer that resolved its own key |
| `tests/privacy_provider_test.php` | 4 | Metadata declaration; context/user discovery; `export_user_data` (log rows + **redacted** key value); per-user deletion with isolation |
| `tests/output/mykeys_test.php` | 2 | Per-provider personal-key status (set/unset) with no key value ever placed in the template context; recent usage rows carry the correct provider icon, including the fallback for an unrecognised provider |
| `tests/output/report_test.php` | 2 | Site-keys report rows carry the requesting user's name and correct provider icon, excluding personal-key rows; empty state when there is no site-keys usage |
| `tests/task/purge_old_logs_test.php` | 2 | Rows older than the configured retention are deleted, newer rows are kept; a retention of 0 keeps every row indefinitely |
| **Total** | **34** | |

```bash
vendor/bin/phpunit --testsuite local_aihub
```

**Line coverage by class (PHPUnit + Xdebug):**

| Class | Line coverage |
|-------|:-------------:|
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
| **Overall** | **73%** |

Two classes are intentionally not fully unit-tested, both for the same reason — the untested lines
are live network calls, not business logic:

- **`local\client`**: `call_gemini()`, `call_groq()`, `call_deepseek()`, `call_openai_compatible()`
  and `http_post()` make real HTTP calls to external providers, so they are never unit-tested
  against the live network. `tests/fixtures/mock_client.php` overrides them to test the
  key-resolution ladder in isolation; the transport methods themselves are verified by hand against
  the real APIs before each release instead. The DNS-rebinding branch of `is_safe_url()` used to
  fall in the same bucket (a real `dns_get_record()` call), but its resolution step was extracted
  into `resolve_dns()`, which `tests/fixtures/dns_stub_client.php` overrides — so that branch is
  now fully covered without any real DNS lookup.
- **`output\renderer`**: its only method delegates a single line to `render_from_template()`.
  Asserting the resulting HTML is a job for the Behat suite below (`mykeys.feature`), not PHPUnit.

### Behat — Acceptance Tests

| Feature file | Scenarios | What is covered |
|--------------|----------:|----------------|
| `tests/behat/mykeys.feature` | 2 | A provider starts unconfigured; saving a personal key marks it configured **without revealing the stored value** |
| **Total** | **2** | |

```bash
php admin/tool/behat/cli/init.php
vendor/bin/behat --tags=@local_aihub --profile=chrome
```
