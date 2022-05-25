/**
 * External dependencies
 */
import { addAction } from '@wordpress/hooks';

/**
 * Internal dependencies
 */
import { namespace, actionPrefix } from './constants';
import { getPriceObject, trackAddToCartEvent } from './utils';

addAction(
	`${ actionPrefix }-cart-add-item`,
	namespace,
	( { product, quantity = 1 } ) => {
		trackAddToCartEvent( product, quantity );
	}
);

const addToCartClick = function ( event ) {
	const data = event.target.dataset;

	trackAddToCartEvent( { id: data.product_id }, data.quantity || 1 );
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

	const product = {
		id: parseInt( variationId ? variationId.value : addToCart.value, 10 ),
	};

	if ( variationId && cartForm.dataset.product_variations ) {
		const variations = JSON.parse( cartForm.dataset.product_variations );
		const variation = Array.isArray( variations )
			? variations.find( ( entry ) => entry.variation_id === product.id )
			: null;

		if ( variation ) {
			product.prices = getPriceObject( variation.display_price );
		}
	}

	trackAddToCartEvent(
		product,
		quantity ? parseInt( quantity.value, 10 ) : 1
	);
};

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
