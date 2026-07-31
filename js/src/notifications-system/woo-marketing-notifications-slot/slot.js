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

/**
 * Creates a self-contained controller for the WooCommerce Marketing notifications slot.
 *
 * A persistent MutationObserver on `document.body` calls `sync()` on every DOM
 * change. `sync()` finds the multichannel section and delegates to `mount()`,
 * which positions the NotificationsPanel container inside it. The observer is
 * never disconnected because the marketing page itself mounts and unmounts as a
 * React component. Before each mount, stale roots are torn down via
 * `unmountIfContainerDetached()`.
 *
 * @return {Function} init — call once per page load to activate the slot.
 */
function createSlot() {
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
	 * Insert the container immediately after the banner, or prepend it to the
	 * multichannel section when no banner is present.
	 *
	 * @param {HTMLElement} multichannel
	 * @param {HTMLElement} container
	 */
	function placeContainer( multichannel, container ) {
		const banner = multichannel.querySelector( `.${ BANNER_CLASS }` );

		if ( banner ) {
			banner.insertAdjacentElement( 'afterend', container );
		} else {
			multichannel.insertBefore( container, multichannel.firstChild );
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
	 * @param {HTMLElement} multichannel
	 */
	function mount( multichannel ) {
		unmountIfContainerDetached();

		const existingInMultichannel = multichannel.querySelector(
			`.${ CONTAINER_CLASS }`
		);

		if ( existingInMultichannel ) {
			repositionContainer( multichannel, existingInMultichannel );
			mountedContainer = existingInMultichannel;
			return;
		}

		const existingGlobal = document.querySelector(
			`.${ CONTAINER_CLASS }`
		);

		if ( existingGlobal ) {
			// Another plugin has already mounted the slot elsewhere.
			return;
		}

		const container = document.createElement( 'div' );
		container.className = CONTAINER_CLASS;

		// Place the notifications container immediately after the introduction
		// banner if one exists, otherwise prepend it to the top of the
		// multichannel section so it is always the first thing the user sees.
		placeContainer( multichannel, container );

		root = createRoot( container );
		root.render( <NotificationsPanel /> );
		mountedContainer = container;
	}

	/**
	 * Sync the slot with the current marketing overview DOM.
	 */
	function sync() {
		const multichannel = document.querySelector(
			`.${ MULTICHANNEL_CLASS }`
		);

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
		const observer = new MutationObserver( debounce( sync, 0 ) );

		observer.observe( document.body, { childList: true, subtree: true } );

		sync();
	}

	return function init() {
		registerStore();
		observeAndMount();
	};
}

export default createSlot();
