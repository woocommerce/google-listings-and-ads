/**
 * External dependencies
 */
import { select, subscribe } from '@wordpress/data';
import { createElement } from '@wordpress/element';
import { doAction } from '@wordpress/hooks';
import { getPath, getHistory } from '@woocommerce/navigation';

/**
 * Internal dependencies
 */
import { STORE_KEY } from '~/data/constants';
import {
	registerNotificationsInMarketingSlot,
	removeNotificationFromMarketingSlot,
	useDismissNotificationFromMarketingSlot,
} from '~/notifications-system/woo-marketing-notifications-slot';
import Notification from './notification';
import useNotificationsSystemMap from './useNotificationsSystemMap';
import { GLA_NOTIFICATION_DISMISSED } from '~/constants';
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

/**
 * The GLA notifications last reconciled into the marketing slot, or `null`
 * before the first reconciliation.
 *
 * @type {Array<Object>|null}
 */
let syncedNotifications = null;

/**
 * Reconciles the marketing slot with the latest GLA notifications: registers
 * notifications that became active and removes those that are no longer active.
 *
 * Notifications present in both the previous and the latest list are left
 * untouched, so an existing card is never duplicated and a card already removed
 * from the slot locally (e.g. after its CTA saved a setting) isn't re-added.
 *
 * @param {Array<Object>} glaNotifications The latest notifications from the GLA store.
 */
function reconcileNotifications( glaNotifications ) {
	const previousIds = new Set(
		( syncedNotifications ?? [] ).map( ( { id } ) => id )
	);
	const latestIds = new Set( glaNotifications.map( ( { id } ) => id ) );

	previousIds.forEach( ( id ) => {
		if ( ! latestIds.has( id ) ) {
			removeNotificationFromMarketingSlot( id );
		}
	} );

	const addedNotifications = glaNotifications
		.filter( ( { id } ) => ! previousIds.has( id ) )
		.map( ( { id, triggered_at } ) => {
			return {
				id,
				triggered_at, // The triggered_at timestamp is used for sorting notifications in the marketing notifications store.
				component: createNotificationComponent( id, triggered_at ),
			};
		} );

	if ( addedNotifications.length ) {
		registerNotificationsInMarketingSlot( addedNotifications );
	}

	syncedNotifications = glaNotifications;
}

/**
 * Keeps the marketing slot in step with the GLA notifications.
 *
 * The notifications-system bundle is enqueued on every wc-admin page (it's a
 * single-page app, so any page can SPA-navigate to Marketing overview without
 * a full reload). Notifications are only fetched and reconciled while the
 * current SPA route is the Marketing overview page.
 *
 * Selecting `getNotifications` here also refetches the notifications once the
 * resolver is invalidated (e.g. after connecting or disconnecting the Google Ads
 * account), so the slot reflects the change without a page reload.
 */
function syncNotifications() {
	if ( getPath() !== MARKETING_OVERVIEW_PATH ) {
		return;
	}

	const { getNotifications, hasFinishedResolution } = select( STORE_KEY );
	const glaNotifications = getNotifications();

	if (
		! hasFinishedResolution( 'getNotifications' ) ||
		glaNotifications === syncedNotifications
	) {
		return;
	}

	reconcileNotifications( glaNotifications );
}

syncNotifications();

// Re-sync whenever the GLA store changes, e.g. a refetch after invalidation.
subscribe( syncNotifications, STORE_KEY );

// `getHistory().listen()` only fires on subsequent SPA navigations, not for
// the current location, so the initial sync above still runs separately.
getHistory().listen( syncNotifications );
