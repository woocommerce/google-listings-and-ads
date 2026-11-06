/**
 * External dependencies
 */
import { dispatch, resolveSelect, select, subscribe } from '@wordpress/data';
import { createElement } from '@wordpress/element';
import { doAction } from '@wordpress/hooks';
import { getPath, getHistory } from '@woocommerce/navigation';

/**
 * Internal dependencies
 */
import { STORE_KEY } from '~/data/constants';
import { STORE_KEY as MARKETING_NOTIFICATIONS_STORE_KEY } from '~/notifications-system/woo-marketing-notifications-slot/data/constants';
import {
	registerNotificationsInMarketingSlot,
	useDismissNotificationFromMarketingSlot,
} from '~/notifications-system/woo-marketing-notifications-slot';
import Notification from './notification';
import useNotificationsSystemMap from './useNotificationsSystemMap';
import {
	GLA_NOTIFICATION_DISMISSED,
	GLA_NOTIFICATIONS_COUNT_CHANGED,
} from '~/constants';
import { recordGlaEvent, CONTEXT_MARKETING_OVERVIEW } from '~/utils/tracks';

/**
 * A merchant dismisses a notification.
 *
 * @event gla_notifications_system_notification_dismissed
 * @property {string} context Where the notification is shown, e.g. `'marketing-overview'`.
 * @property {string} id The notification ID.
 */

/**
 * Creates a notification component.
 *
 * @param {string} id Notification ID, used to call dismissNotification on dismiss.
 * @param {number} triggeredAt Unix timestamp (seconds) when the notification was triggered.
 * @return {Function} A function that returns a React component.
 * @fires gla_notifications_system_notification_dismissed with `{ context: 'marketing-overview', id }`.
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
			doAction( GLA_NOTIFICATION_DISMISSED );
			recordGlaEvent( 'gla_notifications_system_notification_dismissed', {
				context: CONTEXT_MARKETING_OVERVIEW,
				id,
			} );
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

const MARKETING_OVERVIEW_PATH = '/marketing';

let syncedNotifications = null;

/**
 * Initializes the notifications system by fetching notifications from the GLA store
 * and registering them in the marketing notifications store.
 *
 * Runs again whenever the GLA notifications are refetched (e.g. after the Ads account
 * is connected or disconnected), so it reconciles the marketing notifications store
 * with the latest list: notifications that are no longer active are removed, and only
 * the new ones are registered, since `registerNotificationsInMarketingSlot` appends to
 * the store with no id-based dedup.
 *
 * After the first sync, it also fires `GLA_NOTIFICATIONS_COUNT_CHANGED` with the
 * difference in the number of cards, so the admin menu badge stays in step with the inbox.
 */
async function initNotifications() {
	const glaNotifications =
		await resolveSelect( STORE_KEY ).getNotifications();

	if ( glaNotifications === syncedNotifications ) {
		return;
	}

	const previousIds = new Set(
		( syncedNotifications ?? [] ).map( ( { id } ) => id )
	);
	const latestIds = new Set( glaNotifications.map( ( { id } ) => id ) );
	const isFirstSync = syncedNotifications === null;
	syncedNotifications = glaNotifications;

	// Cards the merchant dismissed are already gone from the marketing notifications
	// store and the badge, so only the ones still shown are removed and counted here.
	const shownIds = new Set(
		select( MARKETING_NOTIFICATIONS_STORE_KEY )
			.getNotifications()
			.map( ( { id } ) => id )
	);
	let removedCount = 0;

	previousIds.forEach( ( id ) => {
		if ( ! latestIds.has( id ) && shownIds.has( id ) ) {
			// Only removes the card from the marketing notifications store. Unlike a
			// merchant's dismissal, it doesn't call the API or fire `GLA_NOTIFICATION_DISMISSED`.
			dispatch( MARKETING_NOTIFICATIONS_STORE_KEY ).dismissNotification(
				id
			);
			removedCount++;
		}
	} );

	const newNotifications = glaNotifications.filter(
		( { id } ) => ! previousIds.has( id )
	);

	// The server already counted the notifications of the first sync in the badge it rendered.
	const countDelta = newNotifications.length - removedCount;

	if ( ! isFirstSync && countDelta !== 0 ) {
		doAction( GLA_NOTIFICATIONS_COUNT_CHANGED, countDelta );
	}

	if ( ! newNotifications.length ) {
		return;
	}

	const notifications = newNotifications.map( ( { id, triggered_at } ) => {
		return {
			id,
			triggered_at, // The triggered_at timestamp is used for sorting notifications in the marketing notifications store.
			component: createNotificationComponent( id, triggered_at ),
		};
	} );

	registerNotificationsInMarketingSlot( notifications );
}

/**
 * The notifications-system bundle is enqueued on every wc-admin page (it's a
 * single-page app, so any page can SPA-navigate to Marketing overview without
 * a full reload). Only fetch/register notifications once the current SPA
 * route is actually the Marketing overview page.
 */
function initNotificationsIfOnMarketingOverview() {
	if ( getPath() === MARKETING_OVERVIEW_PATH ) {
		initNotifications();
	}
}

initNotificationsIfOnMarketingOverview();

// `getHistory().listen()` only fires on subsequent SPA navigations, not for
// the current location, so the initial check above still runs separately.
getHistory().listen( initNotificationsIfOnMarketingOverview );

// Re-sync when the GLA notifications are refetched, e.g. after the Ads account
// is connected or disconnected, so the inbox updates without a page reload.
subscribe( initNotificationsIfOnMarketingOverview, STORE_KEY );
