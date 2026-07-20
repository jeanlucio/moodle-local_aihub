# 🔐 Segurança e Conformidade

* Chaves pessoais protegidas por capability e opt-in (`local/aihub:usepersonalkey` **e** o toggle de site).
* Chaves pessoais **write-only** — nunca devolvidas à página após salvas, mesmo sob *Entrar como*.
* Chaves de site guardadas com `admin_setting_configpasswordunmask`; o guard SSRF protege o endpoint configurável.
* `require_sesskey()` no formulário de chaves; capability `local/aihub:viewusage` no relatório de uso.
* Cobertura completa da Privacy API (export/delete do log de uso e das preferências pessoais) com os destinos externos declarados.
