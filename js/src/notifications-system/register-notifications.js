/**
 * External dependencies
 */
import { dispatch, resolveSelect } from '@wordpress/data';
import { createElement } from '@wordpress/element';

/**
 * Internal dependencies
 */
import Notification from './notification';
import useNotificationsSystemMap from './useNotificationsSystemMap';

const GLA_STORE = 'woocommerce/google-listings-and-ads';
const NOTIFICATIONS_STORE = 'woocommerce/marketing-notifications-system';

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

		if ( ! config ) {
			return null;
		}

		const handleDismiss = () =>
			dispatch( NOTIFICATIONS_STORE ).dismissNotification( id );

		return createElement( Notification, {
			id,
			triggeredAt,
			onDismiss: handleDismiss,
			...config,
		} );
	};
}

async function registerNotifications() {
	const glaNotifications =
		await resolveSelect( GLA_STORE ).getNotifications();
	const { registerNotifications: registerAll } =
		dispatch( NOTIFICATIONS_STORE );

	registerAll(
		glaNotifications.map( ( { id, triggered_at } ) => ( {
			id,
			component: createNotificationComponent( id, triggered_at ),
			triggeredAt: triggered_at,
		} ) )
	);
}

registerNotifications();
