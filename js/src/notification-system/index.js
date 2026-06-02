/* global MutationObserver */

/**
 * External dependencies
 */
import { createRoot } from '@wordpress/element';

/**
 * Internal dependencies
 */
import NotificationsPanel from './notifications-panel';

const BANNER_CLASS = 'woocommerce-marketing-introduction-banner';
const CONTAINER_CLASS = 'gla-notification-system-container';

let currentRoot = null;

function mount( banner ) {
	let container = document.querySelector( `.${ CONTAINER_CLASS }` );

	if ( container && currentRoot ) {
		return;
	}

	if ( ! container ) {
		container = document.createElement( 'div' );
		container.className = CONTAINER_CLASS;
		banner.insertAdjacentElement( 'afterend', container );
	}

	if ( currentRoot ) {
		currentRoot.unmount();
	}

	currentRoot = createRoot( container );
	currentRoot.render( <NotificationsPanel /> );
}

const observer = new MutationObserver( () => {
	const banner = document.querySelector( `.${ BANNER_CLASS }` );
	if ( banner ) {
		mount( banner );
	}
} );

observer.observe( document.body, {
	childList: true,
	subtree: true,
} );

const existingBanner = document.querySelector( `.${ BANNER_CLASS }` );
if ( existingBanner ) {
	mount( existingBanner );
}
