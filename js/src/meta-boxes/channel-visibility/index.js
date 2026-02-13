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
	const channelVisibilityBox = document.querySelector(
		'#channel_visibility .inside'
	);
	if ( ! channelVisibilityBox ) {
		return;
	}

	const glaElement = document.createElement( 'div' );

	const root = createRoot( glaElement );
	root.render(
		<Suspense>
			<GoogleAdsPromo />
		</Suspense>
	);

	channelVisibilityBox.prepend( glaElement );
} );
