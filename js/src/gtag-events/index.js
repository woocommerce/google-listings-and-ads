/**
 * External dependencies
 */
import { addAction } from '@wordpress/hooks';

/**
 * Internal dependencies
 */
import { namespace, actionPrefix } from './constants';
import {
	getPriceObject,
	getProductObject,
	retrievedVariation,
	trackAddToCartEvent,
} from './utils';

/* global jQuery */

addAction(
	`${ actionPrefix }-cart-add-item`,
	namespace,
	( { product, quantity = 1 } ) => {
		trackAddToCartEvent( product, quantity );
	}
);

const addToCartClick = function ( event ) {
	const data = event.target.dataset;
	const product = getProductObject( { id: data.product_id } );

	trackAddToCartEvent( product, data.quantity || 1 );
};

const singleAddToCartClick = function ( event ) {
	const cartForm = event.target.closest( 'form.cart' );
	if ( ! cartForm ) {
		return;
	}

	const addToCart = cartForm.querySelector( '[name=add-to-cart]' );
	if ( ! addToCart ) {
		return;
	}

	const variationId = cartForm.querySelector( '[name=variation_id]' );
	const quantity = cartForm.querySelector( '[name=quantity]' );

	const product = getProductObject( {
		id: parseInt( variationId ? variationId.value : addToCart.value, 10 ),
	} );

	if ( variationId && cartForm.dataset.product_variations ) {
		const variations = JSON.parse( cartForm.dataset.product_variations );
		const variation = Array.isArray( variations )
			? variations.find( ( entry ) => entry.variation_id === product.id )
			: null;

		if ( variation ) {
			product.name = variation.display_name;
			product.prices = getPriceObject( variation.display_price );
		}
	}

	trackAddToCartEvent(
		product,
		quantity ? parseInt( quantity.value, 10 ) : 1
	);
};

// Register for add_to_cart click events.
window.onload = function () {
	document
		.querySelectorAll(
			'.add_to_cart_button:not( .product_type_variable, .product_type_grouped )'
		)
		.forEach( ( button ) => {
			button.addEventListener( 'click', addToCartClick );
		} );

	document
		.querySelectorAll( '.single_add_to_cart_button' )
		.forEach( ( button ) => {
			button.addEventListener( 'click', singleAddToCartClick );
		} );
};

// Register for jQuery event to update product data.
if ( typeof jQuery === 'function' ) {
	jQuery( document ).on(
		'found_variation',
		'form.cart',
		function ( event, variation ) {
			retrievedVariation( variation );
		}
	);
}
