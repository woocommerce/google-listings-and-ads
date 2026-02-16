/**
 * External dependencies
 */
import { createRoot, lazy, Suspense } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { glaData } from '~/constants';

const GoogleAdsPromo = lazy( () =>
	import( /* webpackChunkName: "google-ads-promo" */ './google-ads-promo' )
);

document.addEventListener( 'DOMContentLoaded', () => {
	// Only render the component if the order attribution source is Google
	if ( glaData?.orderAttributionSource !== 'google' ) {
		return;
	}

	const orderAttributionBox = document.querySelector(
		'#woocommerce-order-source-data .inside'
	);

	if ( ! orderAttributionBox ) {
		return;
	}

	// Create empty div to serve as mount point for React components
	const glaElement = document.createElement( 'div' );

	const root = createRoot( glaElement );

	root.render(
		<Suspense>
			<GoogleAdsPromo />
		</Suspense>
	);

	orderAttributionBox.prepend( glaElement );
} );
