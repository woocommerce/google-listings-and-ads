/* global MutationObserver */

/**
 * External dependencies
 */
import { createRoot } from '@wordpress/element';

/**
 * Internal dependencies
 */
import NotificationsPanel from './notifications-panel';

const MULTICHANNEL_CLASS = 'woocommerce-marketing-overview-multichannel';
const BANNER_CLASS = 'woocommerce-marketing-introduction-banner';
const CONTAINER_CLASS = 'gla-notification-system-container';

let currentRoot = null;

function mount( multichannel ) {
	let container = document.querySelector( `.${ CONTAINER_CLASS }` );

	if ( container && currentRoot ) {
		return;
	}

	if ( ! container ) {
		container = document.createElement( 'div' );
		container.className = CONTAINER_CLASS;

		const banner = multichannel.querySelector( `.${ BANNER_CLASS }` );

		if ( banner ) {
			banner.insertAdjacentElement( 'afterend', container );
		} else {
			multichannel.insertBefore( container, multichannel.firstChild );
		}
	}

	if ( currentRoot ) {
		currentRoot.unmount();
	}

	currentRoot = createRoot( container );
	currentRoot.render( <NotificationsPanel /> );
}

const observer = new MutationObserver( () => {
	const multichannel = document.querySelector( `.${ MULTICHANNEL_CLASS }` );
	if ( multichannel ) {
		mount( multichannel );
	}
} );

observer.observe( document.body, {
	childList: true,
	subtree: true,
} );

const existingMultichannel = document.querySelector(
	`.${ MULTICHANNEL_CLASS }`
);
if ( existingMultichannel ) {
	mount( existingMultichannel );
}
