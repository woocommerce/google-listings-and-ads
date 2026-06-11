/**
 * External dependencies
 */
import { createReduxStore, register, select } from '@wordpress/data';
import { createRoot } from '@wordpress/element';

/**
 * Internal dependencies
 */
import useNotifications from './useNotifications';

const STORE_NAME = 'woocommerce/marketing-notifications-system';
const ACTION_REGISTER_NOTIFICATION = 'REGISTER_NOTIFICATION';

/**
 * This bundle may be loaded by multiple independent plugins (e.g. Google Listings
 * & Ads, Reddit for WooCommerce). WordPress's wp_register_script ensures the JS
 * file is only loaded once, but as a second safeguard we only register the shared
 * data store if it hasn't been registered already — whichever plugin loads first
 * wins, and all others use the same store instance.
 */
const marketingNotificationsStore = createReduxStore( STORE_NAME, {
	reducer( state = [], action ) {
		if ( action.type === ACTION_REGISTER_NOTIFICATION ) {
			return [ ...state, action.notification ];
		}
		return state;
	},

	actions: {
		registerNotification: ( notification ) => {
			return { type: ACTION_REGISTER_NOTIFICATION, notification };
		},
	},

	selectors: {
		getNotifications: ( state ) => {
			return [ ...state ].sort(
				( a, b ) => b.triggeredAt - a.triggeredAt
			);
		},
	},
} );

if ( ! select( STORE_NAME ) ) {
	register( marketingNotificationsStore );
}

function NotificationSystemSlot() {
	const notifications = useNotifications();

	if ( ! notifications?.length ) {
		return null;
	}

	return notifications.map( ( notification, i ) => {
		const NotificationComponent = notification.component;
		return <NotificationComponent key={ i } />;
	} );
}

const MULTICHANNEL_CLASS = 'woocommerce-marketing-overview-multichannel';
const BANNER_CLASS = 'woocommerce-marketing-introduction-banner';
const CONTAINER_CLASS = 'woocommerce-marketing-notifications-container';

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
	currentRoot.render( <NotificationSystemSlot /> );
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
