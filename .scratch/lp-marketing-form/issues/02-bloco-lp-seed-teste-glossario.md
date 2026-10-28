# 02 — Bloco na LP, seed demo, teste de integração e glossário

**What to build:** Marketing posiciona a captura em qualquer **LP** com o bloco Gutenberg **Formulário INFLEET**, escolhendo qual Formulário renderizar, reutilizando o renderizador e o pipeline de submissão do ticket 01. Validação no cliente (HTML5 + JS leve) complementa a do servidor. Estilos da seção saem do template Frota Conectada e vão para o CSS do tema. A LP Frota Conectada fica só com hero + conteúdo (formulário só via bloco). Referência inválida a Formulário falha de forma graciosa. O seed idempotente cria o Formulário demo Frota Conectada, injeta o bloco na página `frota-conectada` e o ambiente demonstra o fluxo de marketing de ponta a ponta. Há check automatizado GET+POST HTTP e assert WP-CLI de Lead; `CONTEXT.md` documenta **Formulário**, **Lead** e **LP**.

**Blocked by:** 01 — Formulário no admin e captura pública grava Lead

**Status:** ready-for-agent

- [ ] Bloco `infleet/form` com seleção de Formulário no editor; front exibe copy e quatro campos; submissão cria Lead como no ticket 01.
- [ ] Validação no cliente + servidor autoritativo; template Frota sem formulário hardcoded; estilos equivalentes ao protótipo via tema.
- [ ] Marketing adiciona bloco a LP nova no editor e vê formulário no front após publicar.
- [ ] Seed cria/atualiza Formulário demo e bloco na LP Frota Conectada; `/frota-conectada/` usa bloco, não template estático.
- [ ] Teste de integração HTTP+WP-CLI documentado falha se Lead ou meta (incl. Formulário de origem) estiverem incorretos.
- [ ] `CONTEXT.md` define Formulário, Lead e LP (só domínio).
