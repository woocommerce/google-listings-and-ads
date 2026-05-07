/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import {
	Card,
	CardDivider,
	CardHeader,
	Flex,
	FlexItem,
} from '@wordpress/components';
import { Badge } from '@woocommerce/components';

/**
 * Internal dependencies
 */
import Notification from './notification';
import './notification-system.scss';

const NotificationSystem = () => {
	return (
		<Card className="gla-notification-system">
			<CardHeader>
				<Flex
					className="gla-notification-system__title"
					justify="flex-start"
				>
					<FlexItem>
						{ __( 'Actions required', 'google-listings-and-ads' ) }
					</FlexItem>
					<FlexItem>
						<Badge count={ 2 } />
					</FlexItem>
				</Flex>
			</CardHeader>

			<Notification
				title={ __(
					'Increase your traffic and find your next customer with Google Ads ',
					'google-listings-and-ads'
				) }
				description={ __(
					'Get discovered and reach the right shoppers when they’re searching for products like yours across Google (including Search, Shopping, YouTube, and more) in just a few easy steps!',
					'google-listings-and-ads'
				) }
				date={ '2026-05-01' }
				ctas={ [
					{
						id: 'get-started',
						label: 'Get started',
						onClick: () => {},
					},
				] }
			/>
			<CardDivider />
			<Notification
				title={ __(
					'Missing size attributes ',
					'google-listings-and-ads'
				) }
				description={ __(
					'There are issues with missing size attributes for 40 of your products.',
					'google-listings-and-ads'
				) }
				date={ '2026-06-05' }
				ctas={ [
					{
						id: 'review-product-feed',
						label: 'Review product feed',
						onClick: () => {},
					},
					{
						id: 'dismiss',
						label: 'Dismiss',
						onClick: () => {},
					},
				] }
			/>
		</Card>
	);
};

export default NotificationSystem;
