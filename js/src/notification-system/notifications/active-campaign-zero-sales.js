/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import Notification from '../notification';

const ActiveCampaignZeroSales = ( { triggeredAt, onDismiss } ) => {
	return (
		<Notification
			title={ __(
				'Drive traffic from Google Ads',
				'google-listings-and-ads'
			) }
			description={ __(
				"Your campaign is active, but hasn't generated sales yet. Review your account recommendations in Google Ads to find specific ways to improve your performance.",
				'google-listings-and-ads'
			) }
			triggeredAt={ triggeredAt }
			onDismiss={ onDismiss }
			actions={ [
				{
					isPrimary: true,
					href: 'https://ads.google.com/aw/recommendations',
					target: '_blank',
					rel: 'noopener noreferrer',
					children: __(
						'View recommendations',
						'google-listings-and-ads'
					),
				},
			] }
		/>
	);
};

export default ActiveCampaignZeroSales;
