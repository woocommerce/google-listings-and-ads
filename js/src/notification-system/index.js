/**
 * External dependencies
 */
import { createRoot } from '@wordpress/element';

/**
 * Internal dependencies
 */
import NotificationSystem from './notification-system';

const BANNER_SELECTOR = '.woocommerce-marketing-introduction-banner';
const CONTAINER_CLASS = 'gla-notification-system-container';

function insertNotificationSystem( banner ) {
	if ( banner.nextElementSibling?.classList.contains( CONTAINER_CLASS ) ) {
		return;
	}
	const container = document.createElement( 'div' );
	container.classList.add( CONTAINER_CLASS );
	banner.after( container );
	createRoot( container ).render( <NotificationSystem /> );
}

const observer = new MutationObserver( () => {
	const banner = document.querySelector( BANNER_SELECTOR );
	if ( banner ) {
		insertNotificationSystem( banner );
		observer.disconnect();
	}
} );

const banner = document.querySelector( BANNER_SELECTOR );
if ( banner ) {
	insertNotificationSystem( banner );
} else {
	observer.observe( document.body, { childList: true, subtree: true } );
}
