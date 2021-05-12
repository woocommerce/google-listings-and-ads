window.jQuery( function ( $ ) {
	'use strict';

	const toggleCustomInput = ( selectInput ) => {
		const customInput = selectInput
			.parents( 'div.select-with-text-input' )
			.find( '.custom-input' );
		if ( selectInput.val() === '_gla_custom_value' ) {
			customInput.show();
		} else {
			customInput.hide();
		}
	};

	const init = () => {
		const selectWithTextInputBoxes = $(
			'div.select-with-text-input select'
		);
		selectWithTextInputBoxes.each( function ( i, input ) {
			toggleCustomInput( $( input ) );
		} );
		selectWithTextInputBoxes.change( function () {
			toggleCustomInput( $( this ) );
		} );
	};

	$( '#woocommerce-product-data' ).on(
		'woocommerce_variations_loaded',
		init
	);
	$( document.body ).on( 'woocommerce_variations_added', init );
	init();
} );
