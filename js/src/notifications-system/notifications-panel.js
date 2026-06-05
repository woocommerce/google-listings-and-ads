/**
 * External dependencies
 */
import { useEffect } from '@wordpress/element';

/**
 * Internal dependencies
 */
import useNotifications from '~/hooks/useNotifications';
import AbandonedOnboarding from './notifications/abandoned-onboarding';
import ActiveCampaignZeroSales from './notifications/active-campaign-zero-sales';
import CouponsNotSynced from './notifications/coupons-not-synced';
import EnhancedConversionsOff from './notifications/enhanced-conversions-off';
import NotOnboarded90Days from './notifications/not-onboarded-90-days';
import PaymentsShippingNoSales from './notifications/payments-shipping-no-sales';
import PausedCampaign from './notifications/paused-campaign';
import ProductIssues from './notifications/product-issues';
import RecommendationsAvailable from './notifications/recommendations-available';
import SalesNotGrowing from './notifications/sales-not-growing';
import SkippedCampaignCreation from './notifications/skipped-campaign-creation';
import Sold10Items from './notifications/sold-10-items';
import TrackingOff from './notifications/tracking-off';
import './notifications-panel.scss';
import { useAppDispatch } from '~/data';

const NOTIFICATION_MAP = {
	'skipped-campaign-creation': SkippedCampaignCreation,
	'not-onboarded-90-days': NotOnboarded90Days,
	'sold-10-items': Sold10Items,
	'payments-shipping-no-sales': PaymentsShippingNoSales,
	'abandoned-onboarding': AbandonedOnboarding,
	'product-issues': ProductIssues,
	'paused-campaign': PausedCampaign,
	'active-campaign-zero-sales': ActiveCampaignZeroSales,
	'enhanced-conversions-off': EnhancedConversionsOff,
	'recommendations-available': RecommendationsAvailable,
	'coupons-not-synced': CouponsNotSynced,
	'sales-not-growing': SalesNotGrowing,
	'tracking-off': TrackingOff,
};

/**
 * Renders the list of active notifications fetched from the notifications API.
 *
 * Maps each notification ID to its corresponding component via {@link NOTIFICATION_MAP},
 * updates the WooCommerce Marketing menu badge count, and re-fetches notifications
 * whenever the browser tab becomes visible.
 *
 * @return {JSX.Element|null} A panel of notification cards, or null if there are no active notifications.
 */
const NotificationsPanel = () => {
	const notifications = useNotifications();
	const { dismissNotification, invalidateResolutionForStoreSelector } =
		useAppDispatch();

	useEffect( () => {
		const handleVisibilityChange = () => {
			if ( ! document.hidden ) {
				invalidateResolutionForStoreSelector( 'getNotifications' );
			}
		};

		document.addEventListener( 'visibilitychange', handleVisibilityChange );

		return () => {
			document.removeEventListener(
				'visibilitychange',
				handleVisibilityChange
			);
		};
	}, [ invalidateResolutionForStoreSelector ] );

	if ( ! notifications.length ) {
		return null;
	}

	return (
		<div className="gla-notifications-panel">
			{ notifications.map( ( notification ) => {
				const Component = NOTIFICATION_MAP[ notification.id ];

				if ( ! Component ) {
					return null;
				}

				return (
					<Component
						key={ notification.id }
						triggeredAt={ notification.triggered_at }
						onDismiss={ () =>
							dismissNotification( notification.id )
						}
					/>
				);
			} ) }
		</div>
	);
};

export default NotificationsPanel;
