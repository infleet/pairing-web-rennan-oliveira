# 01 — Formulário no admin e captura pública grava Lead

**What to build:** Marketing (Editor ou Administrador) cria e edita um **Formulário** no admin com título da seção, subtítulo, rótulo do botão e mensagem de sucesso. No site, um visitante vê a seção de captura com os quatro campos fixos (nome, e-mail, telefone, tamanho da frota) renderizada a partir desse Formulário, envia dados válidos e um **Lead** aparece no admin com nome, e-mail, telefone, frota e referência ao Formulário de origem. Envios inválidos no servidor não criam Lead; envios válidos usam fluxo seguro (nonce, sanitização, pós-redirect-get, mensagem de sucesso configurável — sem ecoar POST cru). A listagem de Leads no admin mostra informação útil (por exemplo e-mail e qual Formulário originou). O CPT Lead permanece interno (não público no front). Para demonstrar o fluxo neste ticket, a LP Frota Conectada pode ainda invocar o renderizador compartilhado a partir do template, desde que o markup estático e inseguro atual seja removido.

**Blocked by:** None — can start immediately.

**Status:** ready-for-agent

- [ ] Formulário é um tipo de conteúdo só admin, com os quatro textos editáveis e labels em português; Editor e Administrador conseguem criar/editar.
- [ ] Visitante anônimo submete nome + e-mail válidos (+ telefone e frota) e um Lead é criado com meta correta, incluindo vínculo ao Formulário.
- [ ] Submissão sem nonce, com e-mail inválido ou nome vazio não persiste Lead (validação no servidor).
- [ ] Após sucesso, a página mostra a mensagem de sucesso do Formulário via PRG, sem XSS por dados do POST.
- [ ] Lista de Leads no admin exibe colunas que ajudam triagem (e-mail e Formulário de origem).
- [ ] Assinante ou papel inferior não acessa telas de Formulários/Leads no admin.
