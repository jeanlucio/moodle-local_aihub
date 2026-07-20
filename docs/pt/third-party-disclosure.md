# 🔎 Divulgação de Serviço de Terceiros

O hub transmite o texto do prompt a um provedor de IA de terceiros **apenas quando uma chave está configurada e uma geração é solicitada**.

### Uma chave de API é obrigatória?

Não. O plugin instala e funciona sem nenhuma chave — apenas informa que não há fonte disponível, e os consumidores caem para a própria integração com `core_ai`. Nenhuma requisição externa é feita até que uma chave de site ou pessoal seja definida.

### Provedores suportados

- **Google Gemini** — https://ai.google.dev/ — modelo: `gemini-flash-latest` (alias rolante, sempre a versão Flash atual do Google; fixo, não configurável)
- **Groq** — https://console.groq.com/ — modelo: `openai/gpt-oss-120b` (fixo, não configurável; ver *Escolha do modelo Groq* abaixo)
- **DeepSeek** — https://deepseek.com/ — modelo: `deepseek-v4-flash` (fixo, não configurável)
- **APIs compatíveis com OpenAI** — qualquer provedor que siga o formato da API OpenAI (OpenRouter, modelos auto-hospedados via LM Studio, um proxy Ollama, etc.) — modelo: configurável por chave (config de site / chave pessoal), padrão `gpt-4o-mini` quando vazio

Esses serviços operam sob seus próprios termos de serviço e políticas de privacidade.

#### Escolha do modelo Groq

O slot da Groq chama um único modelo fixo, `openai/gpt-oss-120b`. A Groq descontinua periodicamente
IDs de modelo específicos (ex.: `llama-3.3-70b-versatile`, descomissionado em 16/08/2026) e espera
migração para um substituto nomeado. Entre as opções gratuitas recomendadas pela Groq para essa
migração, o `openai/gpt-oss-120b` foi escolhido em vez do `qwen/qwen3.6-27b` por seguir instruções
melhor e ser mais confiável em modo JSON entre os prompts variados enviados pelos diferentes
plugins consumidores.

### Como obter uma chave de API

As chaves de API são criadas diretamente no site oficial do provedor. Gemini, Groq e DeepSeek atualmente oferecem camadas de uso gratuitas ou créditos de teste (as políticas de preço podem mudar). O hub **não** fornece chaves de API.

### Onde as chaves são configuradas

1. **Chave pessoal** — definida por cada usuário em *Minhas chaves de IA* (preferências), quando as chaves pessoais estão habilitadas e o usuário tem a capability.
2. **Chave de site** — definida pelo admin em *Administração do site → Plugins → Plugins locais → Central de IA*.

### Transmissão de Dados

Quando uma chave é resolvida para um provedor, o **texto do prompt é transmitido** à API desse provedor para gerar a resposta:

- **Google Gemini** — `generativelanguage.googleapis.com`
- **Groq** — `api.groq.com`
- **DeepSeek** — `api.deepseek.com`
- **Compatível com OpenAI** — o endpoint configurado pelo admin ou usuário (padrão `api.openai.com`)

O hub guarda um **log de uso** (quem solicitou, qual componente, um rótulo curto do que foi gerado, provedor, modelo, tier da chave e horário), mas **não guarda prompts nem respostas da IA**. Todos os destinos externos estão declarados no Privacy provider do plugin.
