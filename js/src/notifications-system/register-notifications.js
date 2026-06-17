/**
 * External dependencies
 */
import { dispatch, resolveSelect, useDispatch } from '@wordpress/data';
import { createElement } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { STORE_KEY } from '~/data/constants';
import Notification from './notification';
import useNotificationsSystemMap from './useNotificationsSystemMap';
import { STORE_NAME } from './woo-marketing-notifications-slot/constants';

/**
 * Creates a notification component.
 *
 * @param {string} id Notification ID, used to call dismissNotification on dismiss.
 * @param {number} triggeredAt Unix timestamp (seconds) when the notification was triggered.
 * @return {Function} A function that returns a React component.
 */
function createNotificationComponent( id, triggeredAt ) {
	return function NotificationComponent() {
		const notificationMap = useNotificationsSystemMap();
		const config = notificationMap[ id ];
		const { dismissNotification } = useDispatch( STORE_NAME );

		if ( ! config ) {
			return null;
		}

		const handleDismiss = () => {
			return dismissNotification( id );
		};

		return createElement( Notification, {
			id,
			triggeredAt,
			onDismiss: handleDismiss,
			...config,
		} );
	};
}

async function initNotifications() {
	const glaNotifications =
		await resolveSelect( STORE_KEY ).getNotifications();

	if ( ! glaNotifications.length ) {
		return;
	}

	const { registerNotifications } = dispatch( STORE_NAME );

	const notifications = glaNotifications.map( ( { id, triggered_at } ) => {
		return {
			id,
			component: createNotificationComponent( id, triggered_at ),
			triggeredAt: triggered_at,
		};
	} );

	registerNotifications( notifications );
}

initNotifications();
