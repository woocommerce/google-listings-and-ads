/**
 * External dependencies
 */
import { createRoot, lazy, Suspense } from '@wordpress/element';

/**
 * Internal dependencies
 */
const GoogleAdsPromo = lazy( () =>
	import( /* webpackChunkName: "google-ads-promo" */ './google-ads-promo' )
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
			<GoogleAdsPromo />
		</Suspense>
	);

	orderAttributionBox.prepend( glaElement );
} );
