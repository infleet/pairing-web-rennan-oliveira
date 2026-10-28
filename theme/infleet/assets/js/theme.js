/**
 * Comportamentos do tema INFLEET.
 */
( function () {
	'use strict';

	var header = document.querySelector( '.site-header' );

	if ( ! header ) {
		return;
	}

	var aoRolar = function () {
		header.classList.toggle( 'site-header--compact', window.scrollY > 32 );
	};

	window.addEventListener( 'scroll', aoRolar, { passive: true } );
	aoRolar();

	document.querySelectorAll( '.infleet-capture-form__form' ).forEach( function ( form ) {
		form.addEventListener( 'submit', function ( event ) {
			if ( form.checkValidity() ) {
				return;
			}
			event.preventDefault();
			var aviso = form.querySelector( '.infleet-capture-form__error' );
			if ( ! aviso ) {
				aviso = document.createElement( 'p' );
				aviso.className = 'infleet-capture-form__error';
				aviso.setAttribute( 'role', 'alert' );
				form.insertBefore( aviso, form.firstChild );
			}
			aviso.textContent = 'Preencha nome e e-mail válidos antes de enviar.';
			form.reportValidity();
		} );
	} );
}() );
