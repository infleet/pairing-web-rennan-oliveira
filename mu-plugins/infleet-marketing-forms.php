<?php
/**
 * Plugin Name: INFLEET Formulários de captura
 * Description: Formulários gerenciáveis no admin, renderização pública e gravação de Leads.
 * Version:     1.0.0
 * Author:      INFLEET
 *
 * @package infleet
 */

const INFLEET_FORM_POST_TYPE = 'formulario';

const INFLEET_FORM_META_SECTION_TITLE   = 'section_title';
const INFLEET_FORM_META_SECTION_SUBTITLE = 'section_subtitle';
const INFLEET_FORM_META_BUTTON_LABEL    = 'button_label';
const INFLEET_FORM_META_SUCCESS_MESSAGE = 'success_message';

const INFLEET_LEAD_META_EMAIL    = 'email';
const INFLEET_LEAD_META_TELEFONE = 'telefone';
const INFLEET_LEAD_META_FROTA    = 'frota';
const INFLEET_LEAD_META_FORM_ID  = 'form_id';

/**
 * Registra o tipo de conteúdo Formulário (somente admin).
 */
function infleet_register_formulario_post_type() {
	register_post_type(
		INFLEET_FORM_POST_TYPE,
		array(
			'labels'              => array(
				'name'               => 'Formulários',
				'singular_name'      => 'Formulário',
				'menu_name'          => 'Formulários',
				'add_new'            => 'Adicionar formulário',
				'add_new_item'       => 'Adicionar formulário',
				'edit_item'          => 'Editar formulário',
				'new_item'           => 'Novo formulário',
				'view_item'          => 'Ver formulário',
				'search_items'       => 'Buscar formulários',
				'not_found'          => 'Nenhum formulário encontrado.',
				'not_found_in_trash' => 'Nenhum formulário na lixeira.',
			),
			'public'              => false,
			'publicly_queryable'  => false,
			'exclude_from_search' => true,
			'show_ui'             => true,
			'show_in_menu'        => true,
			'show_in_rest'        => false,
			'has_archive'         => false,
			'rewrite'             => false,
			'menu_icon'           => 'dashicons-feedback',
			'menu_position'       => 25,
			'supports'            => array( 'title' ),
			'capability_type'     => 'post',
			'map_meta_cap'        => true,
		)
	);
}
add_action( 'init', 'infleet_register_formulario_post_type' );

/**
 * Metadados editáveis do Formulário.
 *
 * @return array<string, string>
 */
function infleet_formulario_meta_fields() {
	return array(
		INFLEET_FORM_META_SECTION_TITLE   => 'Título da seção',
		INFLEET_FORM_META_SECTION_SUBTITLE => 'Subtítulo da seção',
		INFLEET_FORM_META_BUTTON_LABEL    => 'Rótulo do botão',
		INFLEET_FORM_META_SUCCESS_MESSAGE => 'Mensagem de sucesso',
	);
}

/**
 * Caixa de meta no admin do Formulário.
 */
function infleet_formulario_meta_box() {
	add_meta_box(
		'infleet-formulario-copy',
		'Textos da seção de captura',
		'infleet_formulario_meta_box_render',
		INFLEET_FORM_POST_TYPE,
		'normal',
		'high'
	);
}
add_action( 'add_meta_boxes', 'infleet_formulario_meta_box' );

/**
 * @param WP_Post $post Post do Formulário.
 */
function infleet_formulario_meta_box_render( $post ) {
	wp_nonce_field( 'infleet_save_formulario_meta', 'infleet_formulario_meta_nonce' );

	foreach ( infleet_formulario_meta_fields() as $key => $label ) {
		$value = get_post_meta( $post->ID, $key, true );
		printf(
			'<p><label for="infleet_%1$s"><strong>%2$s</strong></label><br>',
			esc_attr( $key ),
			esc_html( $label )
		);
		if ( INFLEET_FORM_META_SUCCESS_MESSAGE === $key ) {
			printf(
				'<textarea id="infleet_%1$s" name="infleet_form_meta[%1$s]" rows="3" class="large-text">%2$s</textarea></p>',
				esc_attr( $key ),
				esc_textarea( $value )
			);
		} else {
			printf(
				'<input type="text" id="infleet_%1$s" name="infleet_form_meta[%1$s]" value="%2$s" class="large-text"></p>',
				esc_attr( $key ),
				esc_attr( $value )
			);
		}
	}
}

/**
 * @param int $post_id ID do post sendo salvo.
 */
function infleet_formulario_save_meta( $post_id ) {
	if ( ! isset( $_POST['infleet_formulario_meta_nonce'] ) ) {
		return;
	}
	if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['infleet_formulario_meta_nonce'] ) ), 'infleet_save_formulario_meta' ) ) {
		return;
	}
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}
	if ( INFLEET_FORM_POST_TYPE !== get_post_type( $post_id ) ) {
		return;
	}
	if ( ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}

	$raw = isset( $_POST['infleet_form_meta'] ) && is_array( $_POST['infleet_form_meta'] )
		? wp_unslash( $_POST['infleet_form_meta'] )
		: array();

	foreach ( array_keys( infleet_formulario_meta_fields() ) as $key ) {
		if ( INFLEET_FORM_META_SUCCESS_MESSAGE === $key ) {
			$value = sanitize_textarea_field( $raw[ $key ] ?? '' );
		} else {
			$value = isset( $raw[ $key ] ) ? sanitize_text_field( $raw[ $key ] ) : '';
		}
		update_post_meta( $post_id, $key, $value );
	}
}
add_action( 'save_post_' . INFLEET_FORM_POST_TYPE, 'infleet_formulario_save_meta' );

/**
 * Valores padrão de copy quando meta está vazia.
 *
 * @param int $form_id ID do Formulário.
 * @return array<string, string>
 */
function infleet_formulario_copy( $form_id ) {
	$defaults = array(
		INFLEET_FORM_META_SECTION_TITLE   => 'Fale conosco',
		INFLEET_FORM_META_SECTION_SUBTITLE => 'Preencha o formulário abaixo.',
		INFLEET_FORM_META_BUTTON_LABEL    => 'Enviar',
		INFLEET_FORM_META_SUCCESS_MESSAGE => 'Recebemos seu contato. Em breve nossa equipe retorna.',
	);

	$copy = array();
	foreach ( $defaults as $key => $default ) {
		$stored     = get_post_meta( $form_id, $key, true );
		$copy[ $key ] = ( is_string( $stored ) && '' !== $stored ) ? $stored : $default;
	}

	return $copy;
}

/**
 * Formulário publicado e utilizável no front.
 *
 * @param int $form_id ID do Formulário.
 */
function infleet_formulario_is_available( $form_id ) {
	$post = get_post( $form_id );
	return $post instanceof WP_Post
		&& INFLEET_FORM_POST_TYPE === $post->post_type
		&& 'publish' === $post->post_status;
}

/**
 * Busca Formulário publicado pelo slug (post_name).
 *
 * @param string $slug Slug do Formulário.
 * @return int 0 se não encontrado.
 */
function infleet_get_formulario_id_by_slug( $slug ) {
	$posts = get_posts(
		array(
			'post_type'      => INFLEET_FORM_POST_TYPE,
			'name'           => $slug,
			'post_status'    => 'publish',
			'posts_per_page' => 1,
			'fields'         => 'ids',
		)
	);

	return ! empty( $posts ) ? (int) $posts[0] : 0;
}

/**
 * Renderiza a seção de captura para um Formulário.
 *
 * @param int $form_id ID do Formulário.
 * @return string HTML ou string vazia se inválido.
 */
function infleet_render_capture_form( $form_id ) {
	$form_id = (int) $form_id;
	if ( ! infleet_formulario_is_available( $form_id ) ) {
		return '';
	}

	$copy       = infleet_formulario_copy( $form_id );
	$show_success = isset( $_GET['infleet_lead_ok'] ) && (int) $_GET['infleet_lead_ok'] === $form_id;

	ob_start();
	?>
	<section class="infleet-capture-form">
		<div class="infleet-capture-form__inner">
			<h2><?php echo esc_html( $copy[ INFLEET_FORM_META_SECTION_TITLE ] ); ?></h2>
			<p><?php echo esc_html( $copy[ INFLEET_FORM_META_SECTION_SUBTITLE ] ); ?></p>

			<?php if ( $show_success ) : ?>
				<p class="infleet-capture-form__ok" role="status">
					<?php echo esc_html( $copy[ INFLEET_FORM_META_SUCCESS_MESSAGE ] ); ?>
				</p>
			<?php else : ?>
				<form method="post" class="infleet-capture-form__form" novalidate>
					<input type="hidden" name="infleet_form_submit" value="1">
					<input type="hidden" name="infleet_form_id" value="<?php echo esc_attr( (string) $form_id ); ?>">
					<?php wp_nonce_field( 'infleet_form_submit_' . $form_id, '_wpnonce' ); ?>

					<label class="screen-reader-text" for="infleet-nome-<?php echo esc_attr( (string) $form_id ); ?>">Nome completo</label>
					<input type="text" id="infleet-nome-<?php echo esc_attr( (string) $form_id ); ?>" name="nome" placeholder="Nome completo" required autocomplete="name">

					<label class="screen-reader-text" for="infleet-email-<?php echo esc_attr( (string) $form_id ); ?>">E-mail corporativo</label>
					<input type="email" id="infleet-email-<?php echo esc_attr( (string) $form_id ); ?>" name="email" placeholder="E-mail corporativo" required autocomplete="email">

					<label class="screen-reader-text" for="infleet-telefone-<?php echo esc_attr( (string) $form_id ); ?>">Telefone</label>
					<input type="tel" id="infleet-telefone-<?php echo esc_attr( (string) $form_id ); ?>" name="telefone" placeholder="Telefone" autocomplete="tel">

					<label class="screen-reader-text" for="infleet-frota-<?php echo esc_attr( (string) $form_id ); ?>">Tamanho da frota</label>
					<input type="text" id="infleet-frota-<?php echo esc_attr( (string) $form_id ); ?>" name="frota" placeholder="Tamanho da frota">

					<button type="submit"><?php echo esc_html( $copy[ INFLEET_FORM_META_BUTTON_LABEL ] ); ?></button>
				</form>
			<?php endif; ?>
		</div>
	</section>
	<?php
	return (string) ob_get_clean();
}

/**
 * Processa submissão pública do formulário de captura.
 */
function infleet_handle_capture_form_submission() {
	if ( 'POST' !== ( $_SERVER['REQUEST_METHOD'] ?? '' ) ) {
		return;
	}
	if ( empty( $_POST['infleet_form_submit'] ) ) {
		return;
	}

	$form_id = isset( $_POST['infleet_form_id'] ) ? (int) $_POST['infleet_form_id'] : 0;
	if ( ! infleet_formulario_is_available( $form_id ) ) {
		return;
	}

	if ( empty( $_POST['_wpnonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['_wpnonce'] ) ), 'infleet_form_submit_' . $form_id ) ) {
		return;
	}

	$nome     = isset( $_POST['nome'] ) ? sanitize_text_field( wp_unslash( $_POST['nome'] ) ) : '';
	$email    = isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '';
	$telefone = isset( $_POST['telefone'] ) ? sanitize_text_field( wp_unslash( $_POST['telefone'] ) ) : '';
	$frota    = isset( $_POST['frota'] ) ? sanitize_text_field( wp_unslash( $_POST['frota'] ) ) : '';

	if ( '' === $nome || ! is_email( $email ) ) {
		return;
	}

	$lead_id = wp_insert_post(
		array(
			'post_type'   => 'lead',
			'post_title'  => $nome,
			'post_status' => 'publish',
		),
		true
	);

	if ( is_wp_error( $lead_id ) ) {
		return;
	}

	update_post_meta( $lead_id, INFLEET_LEAD_META_EMAIL, $email );
	update_post_meta( $lead_id, INFLEET_LEAD_META_TELEFONE, $telefone );
	update_post_meta( $lead_id, INFLEET_LEAD_META_FROTA, $frota );
	update_post_meta( $lead_id, INFLEET_LEAD_META_FORM_ID, $form_id );

	$redirect = wp_get_referer();
	if ( ! $redirect ) {
		$redirect = home_url( add_query_arg( array(), $_SERVER['REQUEST_URI'] ?? '/' ) );
	}

	$redirect = remove_query_arg( 'infleet_lead_ok', $redirect );
	$redirect = add_query_arg( 'infleet_lead_ok', (string) $form_id, $redirect );

	wp_safe_redirect( $redirect );
	exit;
}
add_action( 'template_redirect', 'infleet_handle_capture_form_submission', 0 );

/**
 * Bloco Gutenberg: Formulário INFLEET.
 *
 * @param array<string, mixed> $attributes Atributos do bloco.
 * @return string
 */
function infleet_render_form_block( $attributes ) {
	$form_id = isset( $attributes['formId'] ) ? (int) $attributes['formId'] : 0;
	if ( $form_id <= 0 || ! infleet_formulario_is_available( $form_id ) ) {
		return '';
	}

	// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- markup escapado em infleet_render_capture_form().
	return infleet_render_capture_form( $form_id );
}

/**
 * Registra o bloco dinâmico infleet/form.
 */
function infleet_register_form_block() {
	$editor_script = 'infleet-form-block-editor';

	wp_register_script(
		$editor_script,
		plugins_url( 'blocks/infleet-form/editor.js', __FILE__ ),
		array( 'wp-blocks', 'wp-element', 'wp-block-editor', 'wp-components' ),
		'1.0.0',
		true
	);

	register_block_type(
		'infleet/form',
		array(
			'api_version'     => 3,
			'title'           => 'Formulário INFLEET',
			'category'        => 'widgets',
			'icon'            => 'feedback',
			'attributes'      => array(
				'formId' => array(
					'type'    => 'number',
					'default' => 0,
				),
			),
			'editor_script'   => $editor_script,
			'render_callback' => 'infleet_render_form_block',
		)
	);
}
add_action( 'init', 'infleet_register_form_block' );

/**
 * Lista de Formulários publicados para o seletor do bloco no editor.
 */
function infleet_form_block_editor_data() {
	$forms = get_posts(
		array(
			'post_type'      => INFLEET_FORM_POST_TYPE,
			'post_status'    => 'publish',
			'posts_per_page' => 100,
			'orderby'        => 'title',
			'order'          => 'ASC',
		)
	);

	$list = array();
	foreach ( $forms as $form_post ) {
		$list[] = array(
			'id'    => $form_post->ID,
			'title' => $form_post->post_title,
		);
	}

	wp_localize_script( 'infleet-form-block-editor', 'infleetFormBlockForms', $list );
}
add_action( 'enqueue_block_editor_assets', 'infleet_form_block_editor_data' );
