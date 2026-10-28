( function ( blocks, element, blockEditor, components ) {
	'use strict';

	var el = element.createElement;
	var useBlockProps = blockEditor.useBlockProps;
	var SelectControl = components.SelectControl;

	blocks.registerBlockType( 'infleet/form', {
		edit: function ( props ) {
			var formId = props.attributes.formId || 0;
			var setAttributes = props.setAttributes;
			var forms = window.infleetFormBlockForms || [];
			var options = [ { label: 'Selecione um formulário', value: 0 } ].concat(
				forms.map( function ( form ) {
					return { label: form.title, value: form.id };
				} )
			);

			var aviso =
				formId > 0
					? el( 'p', { style: { marginTop: '8px', color: '#646970' } }, 'O formulário aparece no site após publicar a página.' )
					: el( 'p', { style: { marginTop: '8px', color: '#b32d2e' } }, 'Escolha um Formulário publicado para exibir a captura.' );

			return el(
				'div',
				useBlockProps( { className: 'infleet-form-block-editor' } ),
				el( SelectControl, {
					label: 'Formulário',
					value: formId,
					options: options,
					onChange: function ( value ) {
						setAttributes( { formId: parseInt( value, 10 ) || 0 } );
					},
				} ),
				aviso
			);
		},
		save: function () {
			return null;
		},
	} );
} )( window.wp.blocks, window.wp.element, window.wp.blockEditor, window.wp.components );
