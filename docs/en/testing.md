# 🧪 Automated Tests

The hub ships with a PHPUnit and Behat suite; every CI push runs against the matrix (Moodle 4.5 → 5.x, PostgreSQL & MariaDB).

### PHPUnit — Unit & Integration Tests

| Test file | Cases | What is covered |
|-----------|------:|----------------|
| `tests/local/keys_test.php` | 8 | OpenAI base-URL/model defaults, including the fallback reached only when an admin blanks the setting; personal-key get/save/clear roundtrip; personal OpenAI-compatible URL/model roundtrip; `personal_keys_allowed` honouring the toggle **and** the capability; key resolution personal → site; `has_any_key` across personal and site keys |
| `tests/local/client_test.php` | 21 | `is_safe_url` SSRF cases (http, loopback, private range, missing host, public IP); DNS-rebinding branch stubbed via `dns_stub_client`; `resolve_openai_url` appends `/chat/completions`; personal tier wins over site; provider fall-through within a tier (Gemini → Groq → DeepSeek); attempts are reported back, including a failure covered by a later success and attempts spanning both tiers; the request each provider assembles — Gemini's own system-instruction and JSON-mode fields versus the chat-completions shape, per-provider endpoint and model; the OpenAI leg resolving personal-then-site URL and model field by field; response parsing per provider, error unwrapping, transport failure, and an unexpected body |
| `tests/local/usage_log_test.php` | 8 | Record insert (with `keysource`, empty model nulled); a failed attempt keeping its reason; the outcome filter narrowing both readers in either direction; site-scoped readers excluding personal/untagged rows; the export reader returning every row past the screen's cap; `display_name` naming a person, the system or an unresolved id |
| `tests/local/export_test.php` | 3 | Personal usage export (all rows, every column); a failed attempt exporting its outcome and reason; site-keys report export including the user column and excluding personal usage |
| `tests/ai_test.php` | 6 | `is_available` states; a successful generation logs the calling component, the description and the key tier; no provider called logs nothing; **every attempt is logged, so a failure covered by a later success leaves two rows**; `report_usage()` writing a row for a consumer that resolved its own key, success or failure |
| `tests/db_upgrade_test.php` | 3 | The upgrade step adds the column with the type, length and nullability `install.xml` declares; existing rows keep their data and read as successes; a second run is a no-op |
| `tests/lib_test.php` | 4 | The navigation entry is added on the user's own preferences page, and not on someone else's, not without the capability, and not when personal keys are disabled site-wide |
| `tests/privacy_provider_test.php` | 9 | Metadata declaration; context/user discovery; `export_user_data` (log rows + **redacted** key value); the readers refusing a non-user context and a user with no rows; per-user deletion, whole-context deletion and approved-user-list deletion, each with isolation |
| `tests/output/mykeys_test.php` | 4 | Per-provider personal-key status (set/unset) with no key value ever placed in the template context; usage rows carrying the right provider icon, including the fallback for an unrecognised provider; a failed attempt showing its reason; the page rendering end to end with no stored key in the markup |
| `tests/output/report_test.php` | 9 | Site-keys report rows carry the requesting user's name and correct provider icon, excluding personal-key rows; empty state; failed rows carrying their reason; each of the three filters narrowing to its own outcome, with exactly one reading as active and an unrecognised one falling back to the full list; the per-filter empty wording; a request with no user attributed to the system; a row written before the failure columns existed still reading as a success; the page rendering end to end |
| `tests/task/purge_old_logs_test.php` | 3 | Rows older than the configured retention are deleted, newer rows are kept; a retention of 0 keeps every row indefinitely; the task names itself from a language string |
| **Total** | **78** | |

```bash
vendor/bin/phpunit --testsuite local_aihub
```

**Line coverage by class (PHPUnit + Xdebug):**

| Class | Line coverage |
|-------|:-------------:|
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
| **Overall** | **96%** |

The transport layer used to sit outside the suite, on the grounds that it makes live HTTP calls.
That reasoning covered more ground than it should have: building a request and parsing a response
are not network operations, and leaving them to the mock meant the part of the plugin that talks
to providers — including the code that turns an API refusal into the sentence an administrator
reads — was the one part nothing checked. Two fixtures reach it now: `recording_client` runs the
real `call_*` methods and captures what they assembled, and `stub_transport_client` answers from a
canned transport so the parsing runs for real, through a single seam (`make_curl()`).

What is left uncovered is genuinely out of reach of a unit test:

- **`local\export`** — `download()` and `download_site()` hand off to the dataformat API and then
  `die()`. The rows they carry are covered; the response itself is covered by Behat below.
- **`local\client`** — `resolve_dns()` performs a real DNS lookup, and `make_curl()` exists to be
  replaced. The decision that depends on the lookup, including the DNS-rebinding branch, is fully
  covered through `dns_stub_client`.
- **`ai`** — one line: the guard that refuses `set_client_for_testing()` outside a test, which by
  definition cannot run inside one.

### Behat — Acceptance Tests

| Feature file | Scenarios | What is covered |
|--------------|----------:|----------------|
| `tests/behat/mykeys.feature` | 5 | A provider starts unconfigured; saving a personal key marks it configured **without revealing the stored value**; a failed attempt of the user's own showing its reason; the personal history downloading as CSV and as Excel |
| `tests/behat/report.feature` | 7 | Both attempts of one request listed with the reason the first failed; the failures filter hiding what worked, the successes filter hiding what failed, and returning to the full list; a request made outside a session attributed rather than shown as an id; the report downloading as CSV and as Excel |
| **Total** | **12** | |

```bash
php admin/tool/behat/cli/init.php
vendor/bin/behat --tags=@local_aihub --profile=chrome
```
