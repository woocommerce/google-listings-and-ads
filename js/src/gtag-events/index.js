/**
 * External dependencies
 */
import { addAction } from '@wordpress/hooks';

/**
 * Internal dependencies
 */
import { namespace, actionPrefix } from './constants';
import { trackAddToCartEvent } from './utils';

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

	const quantity = cartForm.querySelector( '[name=quantity]' );
	const product = cartForm.querySelector( '[name=add-to-cart]' );
	const variation = cartForm.querySelector( '[name=variation_id]' );

	if ( product ) {
		trackAddToCartEvent(
			{ id: variation ? variation.value : product.value },
			quantity ? parseInt( quantity.value, 10 ) : 1
		);
	}
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
