<?php
/**
 * Semeia o ambiente local com o conteudo de demonstracao.
 *
 * Rodado por 'npm run seed', que chama 'wp eval-file'.
 * Idempotente: rodar de novo atualiza o conteudo, nao duplica pagina.
 *
 * @package infleet
 */

if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
	return;
}

$infleet_paginas = array(
	array(
		'slug'    => 'home',
		'titulo'  => 'Home',
		'arquivo' => 'home.html',
	),
	array(
		'slug'    => 'frota-conectada',
		'titulo'  => 'Frota Conectada',
		'arquivo' => 'frota-conectada.html',
	),
	array(
		'slug'    => 'gestao-de-combustivel',
		'titulo'  => 'Gestão de Combustível',
		'arquivo' => 'gestao-de-combustivel.html',
	),
);

// Identidade do site.
update_option( 'blogname', 'INFLEET' );
update_option( 'blogdescription', 'O Copiloto Inteligente para Gestores de Frota' );
update_option( 'timezone_string', 'America/Sao_Paulo' );
update_option( 'start_of_week', 1 );

// URLs limpas, como em producao.
if ( '/%postname%/' !== get_option( 'permalink_structure' ) ) {
	update_option( 'permalink_structure', '/%postname%/' );
}

// Tema.
if ( 'infleet' !== get_stylesheet() ) {
	switch_theme( 'infleet' );
	WP_CLI::log( 'Tema INFLEET ativado.' );
}

// Paginas.
$infleet_ids = array();

foreach ( $infleet_paginas as $infleet_pagina ) {
	$infleet_caminho = __DIR__ . '/content/' . $infleet_pagina['arquivo'];

	if ( ! file_exists( $infleet_caminho ) ) {
		WP_CLI::error( 'Arquivo de conteúdo não encontrado: ' . $infleet_caminho );
	}

	$infleet_conteudo = file_get_contents( $infleet_caminho );
	$infleet_existente = get_page_by_path( $infleet_pagina['slug'] );

	$infleet_dados = array(
		'post_title'   => $infleet_pagina['titulo'],
		'post_name'    => $infleet_pagina['slug'],
		'post_content' => $infleet_conteudo,
		'post_status'  => 'publish',
		'post_type'    => 'page',
		'post_author'  => 1,
	);

	if ( $infleet_existente ) {
		$infleet_dados['ID'] = $infleet_existente->ID;
		$infleet_id          = wp_update_post( $infleet_dados, true );
		$infleet_acao        = 'atualizada';
	} else {
		$infleet_id   = wp_insert_post( $infleet_dados, true );
		$infleet_acao = 'criada';
	}

	if ( is_wp_error( $infleet_id ) ) {
		WP_CLI::error( 'Falha na página ' . $infleet_pagina['slug'] . ': ' . $infleet_id->get_error_message() );
	}

	$infleet_ids[ $infleet_pagina['slug'] ] = $infleet_id;
	WP_CLI::log( sprintf( 'Página %s %s (ID %d).', $infleet_pagina['slug'], $infleet_acao, $infleet_id ) );
}

// Home estatica, como em producao.
update_option( 'show_on_front', 'page' );
update_option( 'page_on_front', $infleet_ids['home'] );
update_option( 'page_for_posts', 0 );

// Menu principal.
$infleet_menu = wp_get_nav_menu_object( 'Menu principal' );

if ( ! $infleet_menu ) {
	$infleet_menu_id = wp_create_nav_menu( 'Menu principal' );

	if ( is_wp_error( $infleet_menu_id ) ) {
		WP_CLI::error( 'Falha ao criar o menu: ' . $infleet_menu_id->get_error_message() );
	}

	foreach ( array( 'home' => 'Home', 'frota-conectada' => 'Frota Conectada', 'gestao-de-combustivel' => 'Gestão de Combustível' ) as $infleet_slug => $infleet_rotulo ) {
		wp_update_nav_menu_item(
			$infleet_menu_id,
			0,
			array(
				'menu-item-title'     => $infleet_rotulo,
				'menu-item-object'    => 'page',
				'menu-item-object-id' => $infleet_ids[ $infleet_slug ],
				'menu-item-type'      => 'post_type',
				'menu-item-status'    => 'publish',
			)
		);
	}

	WP_CLI::log( 'Menu principal criado.' );
} else {
	$infleet_menu_id = $infleet_menu->term_id;
}

set_theme_mod( 'nav_menu_locations', array( 'primary' => $infleet_menu_id ) );

// Remove o conteudo de exemplo do WordPress.
foreach ( array( 'hello-world', 'sample-page', 'privacy-policy' ) as $infleet_slug_exemplo ) {
	$infleet_exemplo = get_page_by_path( $infleet_slug_exemplo, OBJECT, array( 'post', 'page' ) );

	if ( $infleet_exemplo ) {
		wp_delete_post( $infleet_exemplo->ID, true );
		WP_CLI::log( 'Conteúdo de exemplo removido: ' . $infleet_slug_exemplo . '.' );
	}
}

// Formulário demo da LP Frota Conectada (captura gerenciável no admin).
$infleet_form_slug = 'frota-conectada';
$infleet_form_meta = array(
	'section_title'    => 'Fale com um especialista',
	'section_subtitle' => 'Deixe seus dados e nosso time entra em contato em até um dia útil.',
	'button_label'     => 'Quero falar com um especialista',
	'success_message'  => 'Recebemos seu contato. Nossa equipe retorna em até um dia útil.',
);

$infleet_form_existente = get_posts(
	array(
		'post_type'      => 'formulario',
		'name'           => $infleet_form_slug,
		'post_status'    => 'any',
		'posts_per_page' => 1,
	)
);

$infleet_form_dados = array(
	'post_title'  => 'Frota Conectada — captura',
	'post_name'   => $infleet_form_slug,
	'post_status' => 'publish',
	'post_type'   => 'formulario',
	'post_author' => 1,
);

if ( ! empty( $infleet_form_existente ) ) {
	$infleet_form_dados['ID'] = $infleet_form_existente[0]->ID;
	$infleet_form_id          = wp_update_post( $infleet_form_dados, true );
	$infleet_form_acao        = 'atualizado';
} else {
	$infleet_form_id   = wp_insert_post( $infleet_form_dados, true );
	$infleet_form_acao = 'criado';
}

if ( is_wp_error( $infleet_form_id ) ) {
	WP_CLI::error( 'Falha no Formulário Frota Conectada: ' . $infleet_form_id->get_error_message() );
}

foreach ( $infleet_form_meta as $infleet_meta_key => $infleet_meta_valor ) {
	update_post_meta( $infleet_form_id, $infleet_meta_key, $infleet_meta_valor );
}

WP_CLI::log( sprintf( 'Formulário Frota Conectada %s (ID %d).', $infleet_form_acao, $infleet_form_id ) );

// Bloco de captura na LP Frota Conectada (conteúdo Gutenberg, não template).
$infleet_frota_id = $infleet_ids['frota-conectada'];
$infleet_frota_content = (string) get_post_field( 'post_content', $infleet_frota_id );
$infleet_block = sprintf( '<!-- wp:infleet/form {"formId":%d} /-->', $infleet_form_id );

if ( false === strpos( $infleet_frota_content, 'wp:infleet/form' ) ) {
	$infleet_frota_content = rtrim( $infleet_frota_content ) . "\n\n" . $infleet_block . "\n";
	wp_update_post(
		array(
			'ID'           => $infleet_frota_id,
			'post_content' => $infleet_frota_content,
		),
		true
	);
	WP_CLI::log( 'Bloco Formulário INFLEET adicionado à página frota-conectada.' );
}

flush_rewrite_rules( true );

WP_CLI::success( 'Ambiente semeado.' );
