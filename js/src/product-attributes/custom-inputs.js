/**
 * External dependencies
 */
import $ from 'jquery';

/**
 * Internal dependencies
 */
import selectWithText from './select-with-text-input';

$( function () {
	'use strict';

	const init = () => {
		selectWithText( $ );
	};

	$( '#woocommerce-product-data' ).on(
		'woocommerce_variations_loaded',
		init
	);
	$( document.body ).on( 'woocommerce_variations_added', init );
	init();
} );
