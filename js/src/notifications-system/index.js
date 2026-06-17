/**
 * External dependencies
 */
import { dispatch, resolveSelect, useDispatch } from '@wordpress/data';
import { createElement } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { STORE_KEY as GLA_STORE_KEY } from '~/data/constants';
import { STORE_KEY as MARKETING_NOTIFICATIONS_STORE_KEY } from './woo-marketing-notifications-slot/constants';
import Notification from './notification';
import useNotificationsSystemMap from './useNotificationsSystemMap';

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
		const { dismissNotification } = useDispatch(
			MARKETING_NOTIFICATIONS_STORE_KEY
		);
		const config = notificationMap[ id ];

		if ( ! config ) {
			return null;
		}

		const { title, description, action } = config;

		const handleDismiss = () => {
			dismissNotification( id );
		};

		return createElement( Notification, {
			id,
			title,
			description,
			action,
			triggeredAt,
			onDismiss: handleDismiss,
		} );
	};
}

/**
 * Initializes the notifications system by fetching notifications from the GLA store
 * and registering them in the marketing notifications store.
 */
async function initNotifications() {
	const glaNotifications =
		await resolveSelect( GLA_STORE_KEY ).getNotifications();

	if ( ! glaNotifications.length ) {
		return;
	}

	const { registerNotifications } = dispatch(
		MARKETING_NOTIFICATIONS_STORE_KEY
	);

	const notifications = glaNotifications.map( ( { id, triggered_at } ) => {
		return {
			id,
			triggered_at, // The triggered_at timestamp is used for sorting notifications in the marketing notifications store.
			component: createNotificationComponent( id, triggered_at ),
		};
	} );

	registerNotifications( notifications );
}

initNotifications();
