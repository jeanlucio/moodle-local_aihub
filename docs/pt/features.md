# ✨ Funcionalidades

* 🔑 **Armazenamento BYOK:** chaves de **site** (admin) e chaves **pessoais** opcionais (por usuário, opt-in) para **Gemini**, **Groq**, **DeepSeek** e qualquer endpoint **compatível com OpenAI**.
* 🪜 **Resolução pessoal → site:** o hub tenta a chave do próprio usuário primeiro, depois a de site, expondo um único resultado ao chamador.
* 🧩 **Fachada de uma chamada:** `\local_aihub\ai::generate_text()` e `is_available()` — consumidas pelos plugins irmãos por dependência **soft** (`class_exists`), sem dependência dura.
* 🚫 **Não embrulha o `core_ai`:** cada consumidor mantém o próprio fallback para `core_ai`, então um site que já tem `core_ai` configurado **não precisa de nada extra** — o hub continua opcional.
* 👁️ **Chaves pessoais write-only:** uma vez salva, a chave pessoal **nunca é devolvida ao navegador** — a página mostra só o status *configurada / não configurada*, fechando a leitura via *Entrar como*.
* 🧑‍💻 **Página self-service:** *Minhas chaves de IA* (nas preferências do usuário) para adicionar/substituir/remover chaves pessoais e revisar o próprio uso recente.
* 📊 **Relatório de uso para o admin:** todas as requisições atendidas pelas **chaves do site**, de todos os usuários, com download **CSV / Excel** — protegido por `local/aihub:viewusage`.
* 🛡️ **Guard SSRF:** o endpoint compatível com OpenAI configurável é forçado a HTTPS, com bloqueio de loopback / link-local / faixas privadas e anti-rebinding de DNS A/AAAA.
* 🧾 **Log de uso + task de retenção:** uma linha por requisição (usuário, componente solicitante, o que foi gerado, provedor, modelo, tier da chave) e uma task agendada que limpa logs além de uma retenção configurável.
* 🔒 **Privacidade completa:** Privacy provider completo para o log de uso e as preferências pessoais, com os destinos externos declarados.
