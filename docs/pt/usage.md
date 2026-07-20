# 📖 Como Usar

1. **Configurar chaves de site (admin):** *Administração do site → Plugins → Plugins locais → Central de IA → Configurações*. Defina qualquer uma das chaves Gemini, Groq, DeepSeek ou compatível com OpenAI e, opcionalmente, habilite as **chaves de API pessoais**.
2. **Adicionar uma chave pessoal (usuário):** usuários com a capability `local/aihub:usepersonalkey` ganham uma entrada **Minhas chaves de IA** nas preferências, onde guardam a própria chave (write-only) e veem o uso recente.
3. **Revisar o uso das chaves de site (admin):** o **Relatório de uso de IA do site** (sob a categoria Central de IA, capability `local/aihub:viewusage`) lista todas as requisições atendidas pelas chaves do site, de todos os usuários, com download CSV e Excel.
4. **Consumir de um plugin (desenvolvedor):** chame `\local_aihub\ai::generate_text()` atrás de um guard `class_exists()`, mantendo o próprio fallback para `core_ai` (ver *Como os consumidores usam*).
