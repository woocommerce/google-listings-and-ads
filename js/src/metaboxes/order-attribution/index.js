/**
 * External dependencies
 */
import { createRoot } from '@wordpress/element';

/**
 * Internal dependencies
 */
import OrderAttributionSlot from './order-attribution-slot';

document.addEventListener( 'DOMContentLoaded', () => {
	const orderAttributionBox = document.querySelector(
		'#woocommerce-order-source-data .inside'
	);
	if ( ! orderAttributionBox ) {
		return;
	}

	const glaElement = document.createElement( 'div' );

	const root = createRoot( glaElement );
	root.render( <OrderAttributionSlot /> );

	orderAttributionBox.appendChild( glaElement );
} );
