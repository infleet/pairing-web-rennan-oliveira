Status: ready-for-agent

# Formulário de LP gerenciável por marketing

## Problem Statement

O time de marketing precisa publicar e ajustar formulários de captura em landing pages de campanha sem depender de ticket ou deploy de código. Hoje a LP Frota Conectada usa um formulário estático embutido no tema: os textos não são editáveis no admin, o envio não grava um Lead no WordPress, e o feedback pós-envio expõe dados de forma insegura. Não existe um componente reutilizável que marketing possa posicionar em novas LPs.

## Solution

Introduzir um **Formulário** como entidade configurável no admin WordPress (CPT dedicado), renderizado no site por um **bloco Gutenberg** que marketing insere no conteúdo da **LP**. Os campos de captura permanecem fixos (nome, e-mail corporativo, telefone, tamanho da frota); marketing edita título e subtítulo da seção, rótulo do botão e mensagem de sucesso. Cada envio válido cria um **Lead** no CPT já previsto para contatos internos, com referência ao Formulário de origem. Editores e administradores gerenciam Formulários e consultam Leads; visitantes anônimos apenas enviam. A LP Frota Conectada deixa de hardcodar o formulário no template e passa a usar o bloco no conteúdo semeado.

## User Stories

1. As a marketing editor, I want to create a new Formulário in the admin, so that I can reuse the same capture section across multiple campaign LPs without developer involvement.
2. As a marketing editor, I want to edit the section title of a Formulário, so that the headline on the LP matches the campaign message.
3. As a marketing editor, I want to edit the section subtitle of a Formulário, so that I can clarify what happens after the visitor submits.
4. As a marketing editor, I want to edit the submit button label, so that the call-to-action fits the campaign tone.
5. As a marketing editor, I want to edit the success message shown after submission, so that expectations about follow-up are accurate.
6. As a marketing editor, I want to insert an INFLEET form block on any LP page in Gutenberg, so that I control placement without theme changes.
7. As a marketing editor, I want to select which Formulário a block renders, so that different LPs can use different copy while sharing the same field set.
8. As a marketing editor, I want changes to Formulário copy to appear on the public site immediately after save, so that I do not wait for deploys.
9. As a marketing editor, I want to add a form to a new LP by adding the block only, so that launching a new campaign page does not require a developer ticket.
10. As a site visitor, I want to see nome, e-mail, telefone, and frota fields on the form, so that I can request contact with minimal friction.
11. As a site visitor, I want client-side hints when required fields are missing or e-mail is invalid, so that I can fix mistakes before submitting.
12. As a site visitor, I want the server to reject invalid submissions even if client checks are bypassed, so that only usable Leads are stored.
13. As a site visitor, I want a clear success state after a valid submission, so that I know the request was received.
14. As a site visitor, I want not to see my raw input echoed unsafely on the page, so that my data is handled responsibly.
15. As a marketing editor, I want each submission stored as a Lead, so that sales or ops can follow up from one place in WordPress.
16. As a marketing editor, I want each Lead to show which Formulário it came from, so that I can attribute interest to the right campaign asset.
17. As a marketing editor, I want to browse Leads in the admin list, so that I can review new contacts without database access.
18. As a marketing editor, I want Lead records to include nome, e-mail, telefone, and frota, so that I have enough context for outreach.
19. As an administrator, I want the same Formulário and Lead capabilities as an editor for this feature, so that role boundaries stay simple.
20. As an administrator, I want Leads to remain non-public on the front site, so that contact data is not exposed via URLs or search.
21. As a subscriber or lower role, I want no access to Formulários or Leads admin screens, so that contact data stays restricted to editorial staff.
22. As a developer maintaining the pairing repo, I want the Frota Conectada demo LP to use the block after seed, so that the environment demonstrates the marketing workflow end-to-end.
23. As a developer, I want form styling consistent with the existing Frota Conectada form section, so that the migration does not regress visual design.
24. As a security-conscious maintainer, I want submissions protected with nonces and sanitization, so that CSRF and injection risks are reduced.
25. As a maintainer, I want post-redirect-get after successful submit, so that refreshing the page does not duplicate Leads.
26. As a marketing editor, I want the admin labels for Formulários and Leads in Portuguese, so that the CMS matches how the team works.
27. As a site visitor on Frota Conectada, I want the hero and body content unchanged in behavior, so that only the form section moves to the new component.
28. As a marketing editor, I want unpublished or invalid Formulário references handled safely in the block, so that broken LPs fail gracefully rather than exposing errors.
29. As a maintainer, I want the Lead post type to remain internal-only (no public queries, no public REST exposure), so that the existing privacy posture of the Lead CPT is preserved.
30. As an AFK agent implementing the feature, I want an automated integration check via HTTP submit and WP-CLI assertions, so that regressions in Lead creation are caught without manual admin clicks every time.

## Implementation Decisions

- **Domain entities**
  - **Formulário**: admin-managed definition; stores section copy (title, subtitle, button label, success message) and identity; not a submission.
  - **Lead**: one visitor submission; stores the four fixed capture fields plus a reference to the originating Formulário (no page URL stored).
  - **LP**: standard WordPress page whose content may include the form block.

- **Formulário storage**
  - Register a non-public custom post type for Formulários, visible in admin, not exposed on the front as standalone URLs.
  - Persist the four editable copy fields as post meta (or equivalent admin fields).
  - Capabilities align with standard post capabilities so **Editor** and **Administrator** can create and edit Formulários.

- **Lead storage**
  - Reuse the existing **Lead** custom post type (internal, non-public, not REST-public).
  - On valid submit: create Lead with display name as the post title; store e-mail, telefone, frota, and `form_id` as meta.
  - Admin list table enhancements: show e-mail and linked Formulário title where practical.

- **Submission pipeline**
  - Single dedicated POST shape (discriminated fields: form id, nonce, submit flag, field names aligned with current Frota form).
  - Verify nonce; sanitize all inputs; require non-empty nome and valid e-mail server-side.
  - Use post/redirect/get for success (and error if implemented via redirect args or flash mechanism)—never render raw POST into HTML.

- **Front rendering**
  - Dynamic Gutenberg block (namespace `infleet/form`) with a `formId` attribute.
  - Server-side render callback outputs the form markup, hidden identifiers, nonce, and fixed inputs with appropriate HTML5 types and required attributes where decided.
  - Editor experience: select Formulário from a dropdown populated from published Formulários (localized script data acceptable; REST exposure on Formulário CPT not required).

- **Theme and LP migration**
  - Remove hardcoded form section from the Frota Conectada page template; keep hero and main content loop.
  - Move form section styles from inline template CSS into theme stylesheet with neutral class names suitable for all LPs.
  - Seed script creates a demo Formulário with Frota Conectada copy and appends the block to the Frota Conectada page content with the correct `formId`.

- **Validation**
  - Client: HTML5 validation plus lightweight JS for messaging (replace alert-only UX).
  - Server: authoritative validation for required fields and e-mail format.

- **Roles**
  - **Editor** and **Administrator**: manage Formulários and view Leads (confirmed in grilling; supersedes the initial “admins only” note for Leads).
  - Lower roles: no admin access to Formulários/Leads menus.

- **Module boundaries**
  - Must-use plugin layer owns CPT registration, submission handling, block registration, and Lead list enhancements.
  - Theme owns shared visual styles for the form section; block render uses those classes.

- **Build tooling**
  - Block editor script may use WordPress-packaged `wp-*` script dependencies without introducing a full `@wordpress/scripts` build in v1.

## Testing Decisions

- **What makes a good test here**: assert **observable outcomes**—HTTP responses and persisted Lead records—not internal function names, hook order, or block registration details.
- **Primary seam (confirmed)**: end-to-end **HTTP integration** after seeding the environment:
  1. GET the public LP URL that contains the form block; parse HTML for the nonce and form action fields.
  2. POST a valid anonymous submission (nome, e-mail, telefone, frota, form id).
  3. Assert via **WP-CLI** that exactly one new Lead exists with expected title and meta (including `form_id`).
  4. Optional second POST with invalid e-mail or empty nome asserts no new Lead (or error redirect), proving server validation.
- **Module under test**: the public submission → Lead persistence path (single seam across Formulário block render + submission handler + Lead CPT).
- **Prior art**: none in repo (no PHPUnit; `testsEnvironment` disabled in wp-env). This integration script becomes the first automated behavioral check; manual admin/block checklist remains supplementary for editor UX.
- **Manual supplementary checks**: Editor can edit Formulário copy and see it on front; block appears in editor; Lead menu visible to Editor; anonymous users cannot access admin Lead screens; Lead CPT remains non-queryable on front.

## Out of Scope

- Dynamic field builder (add/remove/reorder fields or field types).
- Contact Form 7 or other third-party form plugins.
- E-mail notifications on new Lead.
- CSV export, CRM sync, webhooks.
- Storing page URL or UTM parameters on Lead (only Formulário reference).
- Rate limiting, CAPTCHA, or advanced bot protection.
- Custom “Marketing” role with finer-grained caps (Editor + Administrator suffice for v1).
- Shortcode embed (block only for v1; shared renderer may be reused internally).
- Formal ADR unless maintainers explicitly request documentation of CPT vs plugin trade-off.

## Further Notes

- Initial Frota Conectada implementation is a prototype of **behavior only**; it must be replaced, not extended, for persistence and security.
- Glossary terms Formulário, Lead, and LP should be recorded in `CONTEXT.md` when implementation starts (domain-only definitions, no code references).
- If compliance later requires admin-only Lead visibility, capabilities can be tightened without changing the public form seam.
- Plan B if block editor work runs long: internal shortcode sharing the same render callback was discussed but not selected for v1.
