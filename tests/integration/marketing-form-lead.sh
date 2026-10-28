#!/usr/bin/env bash
#
# Integração: submissão pública do formulário de captura → Lead persistido.
# Requer ambiente de pé (npm start) e seed (npm run seed).

set -euo pipefail

cd "$(dirname "$0")/../.."

porta="${WP_ENV_PORT:-8888}"
base="http://localhost:${porta}"
url="${base}/frota-conectada/"

wp_cli() {
	npx wp-env run cli wp "$@"
}

echo "Semeando ambiente…"
wp_cli eval-file wp-content/seed/seed.php >/dev/null

contar_leads() {
	wp_cli post list --post_type=lead --post_status=publish --format=count 2>/dev/null | tr -d '\r\n'
}

html="$(curl -sS "$url")"

nonce="$(printf '%s' "$html" | grep -o 'name="_wpnonce" value="[^"]*"' | head -1 | sed 's/.*value="\([^"]*\)".*/\1/')"
form_id="$(printf '%s' "$html" | grep -o 'name="infleet_form_id" value="[^"]*"' | head -1 | sed 's/.*value="\([^"]*\)".*/\1/')"

if [[ -z "$nonce" || -z "$form_id" ]]; then
	echo "FALHA: não foi possível obter nonce e form_id em ${url}" >&2
	exit 1
fi

antes="$(contar_leads)"

http_code="$(curl -sS -o /dev/null -w '%{http_code}' -X POST "$url" \
	--data-urlencode "infleet_form_submit=1" \
	--data-urlencode "infleet_form_id=${form_id}" \
	--data-urlencode "_wpnonce=${nonce}" \
	--data-urlencode "nome=Teste Integracao" \
	--data-urlencode "email=teste.integracao@empresa.com.br" \
	--data-urlencode "telefone=11999998888" \
	--data-urlencode "frota=10-50" \
	-L)"

if [[ "$http_code" != "200" ]]; then
	echo "FALHA: POST válido retornou HTTP ${http_code}, esperado 200" >&2
	exit 1
fi

depois="$(contar_leads)"

if [[ "$depois" -ne $((antes + 1)) ]]; then
	echo "FALHA: esperado +1 Lead (antes=${antes}, depois=${depois})" >&2
	exit 1
fi

lead_id="$(wp_cli post list --post_type=lead --orderby=date --order=desc --field=ID --posts_per_page=1 2>/dev/null | tr -d '\r\n')"

# Título do Lead = nome informado.
titulo="$(wp_cli post get "$lead_id" --field=post_title 2>/dev/null | tr -d '\r\n')"
if [[ "$titulo" != "Teste Integracao" ]]; then
	echo "FALHA: título do Lead '${titulo}', esperado 'Teste Integracao'" >&2
	exit 1
fi

meta_email="$(wp_cli post meta get "$lead_id" email --format=plain 2>/dev/null | tr -d '\r\n')"
meta_form="$(wp_cli post meta get "$lead_id" form_id --format=plain 2>/dev/null | tr -d '\r\n')"

if [[ "$meta_email" != "teste.integracao@empresa.com.br" ]]; then
	echo "FALHA: meta email '${meta_email}' incorreta" >&2
	exit 1
fi

if [[ "$meta_form" != "$form_id" ]]; then
	echo "FALHA: meta form_id '${meta_form}', esperado '${form_id}'" >&2
	exit 1
fi

# Submissão inválida (nome vazio) não deve criar Lead.
antes_invalido="$(contar_leads)"
curl -sS -o /dev/null -X POST "$url" \
	--data-urlencode "infleet_form_submit=1" \
	--data-urlencode "infleet_form_id=${form_id}" \
	--data-urlencode "_wpnonce=${nonce}" \
	--data-urlencode "nome=" \
	--data-urlencode "email=invalido" \
	-L
depois_invalido="$(contar_leads)"

if [[ "$depois_invalido" -ne "$antes_invalido" ]]; then
	echo "FALHA: submissão inválida criou Lead" >&2
	exit 1
fi

echo "OK: integração marketing-form → Lead passou."
