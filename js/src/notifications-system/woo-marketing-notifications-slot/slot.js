/**
 * External dependencies
 */
import { createRoot } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { MULTICHANNEL_CLASS, BANNER_CLASS, CONTAINER_CLASS } from './constants';
import { registerStore } from './data';
import NotificationsPanel from './components/notifications-panel';

/**
 * Mounts the NotificationsPanel into the multichannel section.
 *
 * Returns false if another plugin has already mounted the slot or if the
 * multichannel element is not yet in the DOM.
 */
function mount( multichannel ) {
	let container = document.querySelector( `.${ CONTAINER_CLASS }` );

	if ( container ) {
		// Another plugin has already mounted the slot; nothing to do.
		return false;
	}

	container = document.createElement( 'div' );
	container.className = CONTAINER_CLASS;

	const banner = multichannel.querySelector( `.${ BANNER_CLASS }` );

	// Place the notifications container immediately after the introduction
	// banner if one exists, otherwise prepend it to the top of the
	// multichannel section so it is always the first thing the user sees.
	if ( banner ) {
		banner.insertAdjacentElement( 'afterend', container );
	} else {
		multichannel.insertBefore( container, multichannel.firstChild );
	}

	createRoot( container ).render( <NotificationsPanel /> );
	return true;
}

/**
 * Observes the DOM for the multichannel section and mounts the slot when found.
 *
 * Also handles the case where the section is already present at call time.
 */
function observeAndMount() {
	const observer = new MutationObserver( () => {
		const multichannel = document.querySelector(
			`.${ MULTICHANNEL_CLASS }`
		);
		if ( multichannel && mount( multichannel ) ) {
			observer.disconnect();
		}
	} );

	observer.observe( document.body, { childList: true, subtree: true } );

	const existingMultichannel = document.querySelector(
		`.${ MULTICHANNEL_CLASS }`
	);

	if ( existingMultichannel ) {
		mount( existingMultichannel );
		observer.disconnect();
	}
}

export default function init() {
	registerStore();
	observeAndMount();
}
