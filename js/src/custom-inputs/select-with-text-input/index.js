jQuery(
	function ( $ ) {
		'use strict';

		const toggleCustomInput = ( selectInput ) => {
			let custom_input = selectInput.parents( 'div.select-with-text-input' ).find( '.custom-input' );
			if ( '_gla_custom_value' === selectInput.val() ) {
				custom_input.show();
			} else {
				custom_input.hide();
			}
		};

		const init = () => {
			let selectWithTextInputBoxes = $( 'div.select-with-text-input select' );
			selectWithTextInputBoxes.each(
				function (i, input) {
					toggleCustomInput( $( input ) );
				}
			);
			selectWithTextInputBoxes.change(
				function () {
					toggleCustomInput( $( this ) );
				}
			);
		}

		$( '#woocommerce-product-data' ).on( 'woocommerce_variations_loaded', init );
		$( document.body ).on( 'woocommerce_variations_added', init );
		init();
	}
);
