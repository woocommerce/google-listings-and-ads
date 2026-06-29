/**
 * External dependencies
 */
import { resolveSelect } from '@wordpress/data';
import { createElement } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { STORE_KEY } from '~/data/constants';
import {
	initMarketingNotificationsSlot,
	registerNotificationsInMarketingSlot,
	useDismissNotificationFromMarketingSlot,
} from './woo-marketing-notifications-slot';
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
		const dismissNotification = useDismissNotificationFromMarketingSlot();
		const config = notificationMap[ id ];

		if ( ! config ) {
			return null;
		}

		const { title, description, actions, isReady } = config;

		const handleDismiss = () => {
			dismissNotification( id );
		};

		return createElement( Notification, {
			id,
			title,
			description,
			actions,
			triggeredAt,
			isReady,
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
		await resolveSelect( STORE_KEY ).getNotifications();

	if ( ! glaNotifications.length ) {
		return;
	}

	const notifications = glaNotifications.map( ( { id, triggered_at } ) => {
		return {
			id,
			triggered_at, // The triggered_at timestamp is used for sorting notifications in the marketing notifications store.
			component: createNotificationComponent( id, triggered_at ),
		};
	} );

	initMarketingNotificationsSlot();
	registerNotificationsInMarketingSlot( notifications );
}

initNotifications();
