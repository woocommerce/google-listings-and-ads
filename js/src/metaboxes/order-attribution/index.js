/**
 * External dependencies
 */
import { createRoot, lazy, Suspense } from '@wordpress/element';

/**
 * Internal dependencies
 */
const OrderAttributionSlot = lazy( () =>
	import(
		/* webpackChunkName: "order-attribution-slot" */ './order-attribution-slot'
	)
);

document.addEventListener( 'DOMContentLoaded', () => {
	const orderAttributionBox = document.querySelector(
		'#woocommerce-order-source-data .inside'
	);
	if ( ! orderAttributionBox ) {
		return;
	}

	const glaElement = document.createElement( 'div' );

	const root = createRoot( glaElement );
	root.render(
		<Suspense>
			<OrderAttributionSlot />
		</Suspense>
	);

	orderAttributionBox.prepend( glaElement );
} );
