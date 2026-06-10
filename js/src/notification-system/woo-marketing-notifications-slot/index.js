/* global MutationObserver */

import { registerStore, select, useSelect } from '@wordpress/data';
import { createRoot } from '@wordpress/element';

const STORE_NAME = 'woocommerce/marketing-notifications-system';

// First plugin to run registers the store; others skip this block.
if ( ! select( STORE_NAME ) ) {
	registerStore( STORE_NAME, {
		reducer( state = [], action ) {
			if ( action.type === 'REGISTER_NOTIFICATION' ) {
				return [ ...state, action.notification ];
			}
			return state;
		},

		actions: {
			registerNotification: ( notification ) => ( {
				type: 'REGISTER_NOTIFICATION',
				notification,
			} ),
		},

		selectors: {
			getNotifications: ( state ) =>
				[ ...state ].sort(
					( a, b ) => b.triggeredAt - a.triggeredAt
				),
		},
	} );
}

function NotificationSystemSlot() {
	const notifications = useSelect( ( sel ) =>
		sel( STORE_NAME ).getNotifications()
	);

	if ( ! notifications.length ) return null;

	return notifications.map( ( notification, i ) => (
		<notification.component key={ i } />
	) );
}

const MULTICHANNEL_CLASS = 'woocommerce-marketing-overview-multichannel';
const BANNER_CLASS = 'woocommerce-marketing-introduction-banner';
const CONTAINER_CLASS = 'woocommerce-marketing-notifications-container';

let currentRoot = null;

function mount( multichannel ) {
	let container = document.querySelector( `.${ CONTAINER_CLASS }` );

	if ( container && currentRoot ) return; // another plugin already mounted

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
}

const observer = new MutationObserver( () => {
	const multichannel = document.querySelector( `.${ MULTICHANNEL_CLASS }` );
	if ( multichannel ) {
		mount( multichannel );
	}
} );

observer.observe( document.body, { childList: true, subtree: true } );

const existingMultichannel = document.querySelector(
	`.${ MULTICHANNEL_CLASS }`
);
if ( existingMultichannel ) {
	mount( existingMultichannel );
}
