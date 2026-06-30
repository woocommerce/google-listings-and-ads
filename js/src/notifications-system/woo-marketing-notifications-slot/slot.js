/**
 * External dependencies
 */
import { createRoot } from '@wordpress/element';
import debounce from 'lodash/debounce';

/**
 * Internal dependencies
 */
import { MULTICHANNEL_CLASS, BANNER_CLASS, CONTAINER_CLASS } from './constants';
import { registerStore } from './data';
import NotificationsPanel from './components/notifications-panel';
import './slot.scss';

let root = null;
let mountedContainer = null;

/**
 * Unmount the React root and clear slot state.
 */
function unmount() {
	if ( root ) {
		root.unmount();
		root = null;
	}

	mountedContainer = null;
}

/**
 * Remove a stale root when React has detached the container from the DOM.
 */
function unmountIfContainerDetached() {
	if ( mountedContainer && ! mountedContainer.isConnected ) {
		unmount();
	}
}

/**
 * Ensure the notifications container sits immediately after the banner,
 * or at the top of the multichannel section when no banner is present.
 *
 * @param {HTMLElement} multichannel
 * @param {HTMLElement} container
 */
function repositionContainer( multichannel, container ) {
	const banner = multichannel.querySelector( `.${ BANNER_CLASS }` );

	if ( banner ) {
		if ( container.previousElementSibling !== banner ) {
			banner.insertAdjacentElement( 'afterend', container );
		}

		return;
	}

	if ( multichannel.firstChild !== container ) {
		multichannel.insertBefore( container, multichannel.firstChild );
	}
}

/**
 * Mounts the NotificationsPanel into the multichannel section.
 *
 * Returns false if another plugin has already mounted the slot elsewhere.
 *
 * @param {HTMLElement} multichannel
 * @return {boolean} Whether a new slot container was mounted.
 */
function mount( multichannel ) {
	unmountIfContainerDetached();

	const existingInMultichannel = multichannel.querySelector(
		`.${ CONTAINER_CLASS }`
	);

	if ( existingInMultichannel ) {
		repositionContainer( multichannel, existingInMultichannel );
		mountedContainer = existingInMultichannel;
		return false;
	}

	const existingGlobal = document.querySelector( `.${ CONTAINER_CLASS }` );

	if ( existingGlobal ) {
		// Another plugin has already mounted the slot elsewhere.
		return false;
	}

	const container = document.createElement( 'div' );
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

	root = createRoot( container );
	root.render( <NotificationsPanel /> );
	mountedContainer = container;

	return true;
}

/**
 * Sync the slot with the current marketing overview DOM.
 */
function sync() {
	const multichannel = document.querySelector( `.${ MULTICHANNEL_CLASS }` );

	if ( ! multichannel ) {
		return;
	}

	mount( multichannel );
}

/**
 * Observes the DOM for the multichannel section and keeps the slot in sync.
 *
 * The observer stays connected so the panel can remount when React removes
 * and re-inserts the marketing banner without creating duplicate containers.
 */
function observeAndMount() {
	const observer = new MutationObserver( debounce( sync ) );

	observer.observe( document.body, { childList: true, subtree: true } );

	sync();
}

export default function init() {
	registerStore();
	observeAndMount();
}
