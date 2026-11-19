/* Compass admin. No dependencies. */
( function () {
	'use strict';

	var A = window.CompassAdmin || {};

	// A switch shows or hides the options that belong to it.
	document.addEventListener( 'change', function ( e ) {
		if ( e.target.matches && e.target.matches( '.pd-switch input' ) ) {
			var row = e.target.closest( '.pd-row' );
			if ( row ) {
				row.classList.toggle( 'is-off', ! e.target.checked );
			}
		}
		if ( e.target.name === 'cp[org_type]' ) {
			syncOrg();
		}
		if ( e.target.matches && e.target.matches( '[data-code]' ) ) {
			syncCode();
		}
	} );

	// Business details only for "Local business".
	function syncOrg() {
		var picked = document.querySelector( 'input[name="cp[org_type]"]:checked' );
		document.querySelectorAll( '[data-show-when]' ).forEach( function ( el ) {
			el.hidden = ! picked || picked.value !== el.getAttribute( 'data-show-when' );
		} );
	}
	syncOrg();

	// "410 Gone" needs no destination.
	function syncCode() {
		var code = document.querySelector( '[data-code]' );
		var target = document.querySelector( '[data-target]' );
		if ( code && target ) {
			target.disabled = '410' === code.value;
			target.required = '410' !== code.value;
		}
	}
	syncCode();


	// Media library picker for image fields.
	document.addEventListener( 'click', function ( e ) {
		var b = e.target.closest( '[data-pick]' );
		if ( ! b || ! window.wp || ! wp.media ) {
			return;
		}
		e.preventDefault();
		var input = document.getElementById( b.getAttribute( 'data-pick' ) );
		var frame = wp.media( { title: A.chooseImage, button: { text: A.useImage }, library: { type: 'image' }, multiple: false } );
		frame.on( 'select', function () {
			var a = frame.state().get( 'selection' ).first().toJSON();
			input.value = a.url;
		} );
		frame.open();
	} );
} )();
