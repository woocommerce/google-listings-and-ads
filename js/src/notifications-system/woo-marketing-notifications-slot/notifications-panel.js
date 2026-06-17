/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Fragment } from '@wordpress/element';
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
import useNotifications from './useNotifications';
import './notifications-panel.scss';

/**
 * Renders the list of active notifications for the Woo Marketing Notifications Slot.
 *
 * @return {JSX.Element|null} A panel of notification cards, or null if there are no active notifications.
 */
const NotificationsPanel = () => {
	const { notifications } = useNotifications();

	if ( ! notifications.length ) {
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
