<?php
/**
 * Plugin Name: INFLEET Leads
 * Description: Registra o tipo de conteudo Lead, onde ficam os contatos capturados pelo site.
 * Version:     1.0.0
 * Author:      INFLEET
 *
 * @package infleet
 */

/**
 * Registra o tipo de conteudo Lead.
 *
 * Lead nao e conteudo publico: nao gera URL no site, nao entra na busca
 * e nao e exposto pela REST API. So existe dentro do admin.
 */
function infleet_register_lead_post_type() {
	register_post_type(
		'lead',
		array(
			'labels'              => array(
				'name'          => 'Leads',
				'singular_name' => 'Lead',
				'menu_name'     => 'Leads',
				'all_items'     => 'Todos os leads',
				'edit_item'     => 'Editar lead',
				'view_item'     => 'Ver lead',
				'search_items'  => 'Buscar leads',
				'not_found'     => 'Nenhum lead por aqui ainda.',
			),
			'public'              => false,
			'publicly_queryable'  => false,
			'exclude_from_search' => true,
			'show_ui'             => true,
			'show_in_menu'        => true,
			'show_in_rest'        => false,
			'has_archive'         => false,
			'rewrite'             => false,
			'menu_icon'           => 'dashicons-groups',
			'menu_position'       => 26,
			'supports'            => array( 'title', 'custom-fields' ),
			'capability_type'     => 'post',
			'map_meta_cap'        => true,
		)
	);
}
add_action( 'init', 'infleet_register_lead_post_type' );

/**
 * Colunas úteis na listagem de Leads no admin.
 *
 * @param string[] $columns Colunas padrão.
 * @return string[]
 */
function infleet_lead_admin_columns( $columns ) {
	$new = array();
	foreach ( $columns as $key => $label ) {
		$new[ $key ] = $label;
		if ( 'title' === $key ) {
			$new['lead_email'] = 'E-mail';
			$new['lead_form']  = 'Formulário';
		}
	}
	return $new;
}
add_filter( 'manage_lead_posts_columns', 'infleet_lead_admin_columns' );

/**
 * @param string $column  Nome da coluna.
 * @param int    $post_id ID do Lead.
 */
function infleet_lead_admin_column_content( $column, $post_id ) {
	if ( 'lead_email' === $column ) {
		$email = get_post_meta( $post_id, 'email', true );
		echo esc_html( is_string( $email ) ? $email : '' );
		return;
	}

	if ( 'lead_form' === $column ) {
		$form_id = (int) get_post_meta( $post_id, 'form_id', true );
		if ( $form_id <= 0 ) {
			echo '—';
			return;
		}
		$title = get_the_title( $form_id );
		if ( current_user_can( 'edit_post', $form_id ) ) {
			printf(
				'<a href="%s">%s</a>',
				esc_url( get_edit_post_link( $form_id, 'raw' ) ),
				esc_html( $title ? $title : '(sem título)' )
			);
		} else {
			echo esc_html( $title ? $title : '(sem título)' );
		}
	}
}
add_action( 'manage_lead_posts_custom_column', 'infleet_lead_admin_column_content', 10, 2 );
