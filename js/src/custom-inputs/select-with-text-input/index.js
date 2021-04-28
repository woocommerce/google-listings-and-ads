jQuery(
	function ( $ ) {
		'use strict';
		const addEvent = () => {
			$( 'div.select-with-text-input select' ).change(
				function () {
					const custom_input = $( this ).parents( 'div.select-with-text-input' ).find( 'input[type=text]' );
					if ( '_gla_custom_value' === $( this ).val() ) {
						custom_input.show();
					} else {
						custom_input.hide();
					}
				}
			);
		}

		$( '#woocommerce-product-data' ).on( 'woocommerce_variations_loaded', addEvent );
		$( document.body ).on( 'woocommerce_variations_added', addEvent );
		addEvent();
	}
);
