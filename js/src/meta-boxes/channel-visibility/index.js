/**
 * External dependencies
 */
import { createRoot, lazy, Suspense } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { glaData } from '~/constants';

const GoogleAdsPromo = lazy( () =>
	import(
		/* webpackChunkName: "channel-visibility-google-ads-promo" */ './google-ads-promo'
	)
);

document.addEventListener( 'DOMContentLoaded', () => {
	// Only render the component if the channel visibility is set
	if (
		! glaData?.channelVisibility ||
		Object.keys( glaData.channelVisibility ).length === 0
	) {
		return;
	}

	const channelVisibilityBox = document.querySelector(
		'#gla-channel-visibility-box'
	);

	if ( ! channelVisibilityBox ) {
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

	channelVisibilityBox.prepend( glaElement );
} );
