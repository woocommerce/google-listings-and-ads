/**
 * External dependencies
 */
import { dispatch } from '@wordpress/data';
import apiFetch from '@wordpress/api-fetch';
import { createElement, useState } from '@wordpress/element';

/**
 * Internal dependencies
 */
import Notification from './notification';
import useNotificationsSystemMap from './useNotificationsSystemMap';

const API_NAMESPACE = '/wc/gla';
const NOTIFICATIONS_STORE = 'woocommerce/marketing-notifications-system';

/**
 * Creates a self-contained React component for a single notification.
 *
 * The component looks up its display config from the notifications map at
 * render time and hides itself immediately when dismissed, calling DELETE
 * on the GLA notifications endpoint so the dismissal is persisted.
 *
 * @param {string} id          Notification ID.
 * @param {number} triggeredAt Unix timestamp (seconds) when the notification was triggered.
 * @return {Function} React component.
 */
function createNotificationComponent( id, triggeredAt ) {
	return function NotificationComponent() {
		const [ hidden, setHidden ] = useState( false );
		const notificationMap = useNotificationsSystemMap();
		const config = notificationMap[ id ];

		if ( hidden || ! config ) {
			return null;
		}

		const handleDismiss = () => {
			setHidden( true );
			apiFetch( {
				path: `${ API_NAMESPACE }/notifications/${ id }`,
				method: 'DELETE',
			} );
		};

		return createElement( Notification, {
			id,
			triggeredAt,
			onDismiss: handleDismiss,
			...config,
		} );
	};
}

async function registerNotifications() {
	const notifications = await apiFetch( {
		path: `${ API_NAMESPACE }/notifications`,
	} );

	const { registerNotification } = dispatch( NOTIFICATIONS_STORE );

	notifications.forEach( ( { id, triggered_at } ) => {
		registerNotification( {
			id,
			component: createNotificationComponent( id, triggered_at ),
			triggeredAt: triggered_at,
		} );
	} );
}

registerNotifications();
