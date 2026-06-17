/**
 * External dependencies
 */
import { createReduxStore, register, select } from '@wordpress/data';
import { createRoot } from '@wordpress/element';

/**
 * Internal dependencies
 */
import {
	STORE_KEY,
	REGISTER_NOTIFICATIONS,
	DISMISS_NOTIFICATION,
	MULTICHANNEL_CLASS,
	BANNER_CLASS,
	CONTAINER_CLASS,
} from './constants';
import NotificationsPanel from './notifications-panel';
import './index.scss';

/**
 * This bundle is registered in PHP under the handle 'woocommerce-marketing-notifications-system-slot'.
 * It may be loaded by multiple independent plugins. WordPress's wp_register_script ensures the JS
 * file is only loaded once, but as a second safeguard we only register the shared
 * data store if it hasn't been registered already — whichever plugin loads first
 * wins, and all others use the same store instance.
 */
if ( ! select( STORE_KEY ) ) {
	register(
		createReduxStore( STORE_KEY, {
			reducer( state = [], action ) {
				switch ( action.type ) {
					case REGISTER_NOTIFICATIONS:
						return [ ...state, ...action.notifications ];
					case DISMISS_NOTIFICATION:
						return state.filter( ( notification ) => {
							return notification.id !== action.id;
						} );
					default:
						return state;
				}
			},

			actions: {
				registerNotifications: ( notifications ) => {
					return {
						type: REGISTER_NOTIFICATIONS,
						notifications,
					};
				},
				dismissNotification: ( id ) => {
					return {
						type: DISMISS_NOTIFICATION,
						id,
					};
				},
			},

			selectors: {
				getNotifications: ( state ) => {
					return [ ...state ].sort(
						( a, b ) => b.triggered_at - a.triggered_at
					);
				},
			},
		} )
	);
}

let currentRoot = null;

function mount( multichannel ) {
	let container = document.querySelector( `.${ CONTAINER_CLASS }` );

	if ( container && currentRoot ) {
		// Another plugin has already mounted the slot; nothing to do.
		return false;
	}

	if ( ! container ) {
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
	}

	if ( currentRoot ) {
		currentRoot.unmount();
	}

	currentRoot = createRoot( container );
	currentRoot.render( <NotificationsPanel /> );
	return true;
}

const observer = new MutationObserver( () => {
	const multichannel = document.querySelector( `.${ MULTICHANNEL_CLASS }` );
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
