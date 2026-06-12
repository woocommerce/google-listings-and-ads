/**
 * External dependencies
 */
import { createRoot } from '@wordpress/element';

/**
 * Internal dependencies
 */
import NotificationsPanel from './notifications-panel';
import './index.scss';

const MULTICHANNEL_CLASS = 'woocommerce-marketing-overview-multichannel';
const BANNER_CLASS = 'woocommerce-marketing-introduction-banner';
const CONTAINER_CLASS = 'gla-notification-system-container';

let currentRoot = null;

/**
 * Mounts the NotificationsPanel into the multichannel marketing overview element.
 * Creates a container and inserts it after the introduction banner, or prepends
 * it if no banner is present. Disconnects the observer once mounted.
 *
 * @param {Element} multichannel The multichannel marketing overview element.
 * @param {MutationObserver} mountObserver The observer to disconnect after mounting.
 */
function mount( multichannel, mountObserver ) {
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
	mountObserver.disconnect();
}

const observer = new MutationObserver( ( _, mountObserver ) => {
	const multichannel = document.querySelector( `.${ MULTICHANNEL_CLASS }` );
	if ( multichannel ) {
		mount( multichannel, mountObserver );
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
	mount( existingMultichannel, observer );
}
