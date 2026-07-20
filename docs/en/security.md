# 🔐 Security & Compliance

* Capability- and opt-in-gated personal keys (`local/aihub:usepersonalkey` **and** the site toggle).
* **Write-only** personal keys — never returned to the page after saving, even under *Log in as*.
* Site keys stored with `admin_setting_configpasswordunmask`; the SSRF guard protects the configurable endpoint.
* `require_sesskey()` on the key form; capability `local/aihub:viewusage` on the usage report.
* Full Privacy API coverage (export/delete of the usage log and personal preferences) with the external destinations declared.
