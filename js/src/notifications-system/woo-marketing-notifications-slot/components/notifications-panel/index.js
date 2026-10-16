/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Fragment, useEffect } from '@wordpress/element';
import {
	Card,
	CardHeader,
	CardDivider,
	Flex,
	FlexItem,
} from '@wordpress/components';
import { Badge } from '@woocommerce/components';

/**
 * Internal dependencies
 */
import useNotifications from '../../hooks/useNotifications';
import { recordGlaEvent, CONTEXT_MARKETING_OVERVIEW } from '~/utils/tracks';
import './index.scss';

/**
 * The `NotificationsPanel` component is rendered.
 *
 * @event gla_notifications_system_notifications_panel_shown
 * @property {string} context Where the panel is shown, e.g. `'marketing-overview'`.
 */

/**
 * Renders the list of active notifications for the Woo Marketing Notifications Slot.
 *
 * @return {JSX.Element|null} A panel of notification cards, or null if there are no active notifications.
 * @fires gla_notifications_system_notifications_panel_shown with `{ context: 'marketing-overview' }`.
 */
const NotificationsPanel = () => {
	const { notifications } = useNotifications();
	const hasNotifications = notifications.length > 0;

	useEffect( () => {
		if ( ! hasNotifications ) {
			return;
		}

		recordGlaEvent( 'gla_notifications_system_notifications_panel_shown', {
			context: CONTEXT_MARKETING_OVERVIEW,
		} );
	}, [ hasNotifications ] );

	if ( ! hasNotifications ) {
		return null;
	}

	return (
		<Card className="gla-woo-marketing-notifications-slot-panel">
			<CardHeader>
				<Flex
					className="woocommerce-marketing-card-header-title"
					align="center"
					justify="start"
				>
					<FlexItem>
						{ __( 'Action required', 'google-listings-and-ads' ) }
					</FlexItem>
					<FlexItem>
						<Badge
							count={ notifications.length }
							className="gla-woo-marketing-notifications-slot-panel__badge"
						/>
					</FlexItem>
				</Flex>
			</CardHeader>

			{ notifications.map( ( { component: Notification, id }, index ) => {
				if ( ! Notification ) {
					return null;
				}

				return (
					<Fragment key={ id }>
						<Notification />

						{ index !== notifications.length - 1 && (
							<CardDivider />
						) }
					</Fragment>
				);
			} ) }
		</Card>
	);
};

export default NotificationsPanel;
