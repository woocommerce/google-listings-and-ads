/**
 * External dependencies
 */
import { subscribe, select, resolveSelect } from '@wordpress/data';
import { createElement } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { STORE_KEY } from '~/data/constants';
import {
	createMarketingNotificationsSlot,
	setNotificationsInMarketingSlot,
	useDismissNotificationFromMarketingSlot,
	SYNC_MARKETING_NOTIFICATIONS_EVENT,
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
 * @param {Array} glaNotifications
 * @return {string} Stable key for comparing notification lists.
 */
function getNotificationsKey( glaNotifications ) {
	return glaNotifications
		.map( ( { id, triggered_at } ) => `${ id }:${ triggered_at }` )
		.join( ',' );
}

/**
 * @param {Array} glaNotifications
 */
function syncMarketingSlot( glaNotifications ) {
	createMarketingNotificationsSlot();

	const notifications = glaNotifications.map( ( { id, triggered_at } ) => {
		return {
			id,
			triggered_at,
			component: createNotificationComponent( id, triggered_at ),
		};
	} );

	setNotificationsInMarketingSlot( notifications );
}

let lastSyncedKey = '';

/**
 * Sync the marketing slot from the current GLA store state.
 */
function syncFromCurrentGlaStore() {
	const glaNotifications = select( STORE_KEY ).getNotifications();
	const key = getNotificationsKey( glaNotifications );

	if ( key === lastSyncedKey ) {
		return;
	}

	lastSyncedKey = key;
	syncMarketingSlot( glaNotifications );
}

/**
 * Initializes the notifications system by fetching notifications from the GLA store
 * and syncing them in the marketing notifications store.
 */
async function initNotifications() {
	const glaNotifications =
		await resolveSelect( STORE_KEY ).getNotifications();

	lastSyncedKey = getNotificationsKey( glaNotifications );
	syncMarketingSlot( glaNotifications );

	subscribe( () => {
		syncFromCurrentGlaStore();
	}, STORE_KEY );

	window.addEventListener( SYNC_MARKETING_NOTIFICATIONS_EVENT, () => {
		syncFromCurrentGlaStore();
	} );
}

initNotifications();
