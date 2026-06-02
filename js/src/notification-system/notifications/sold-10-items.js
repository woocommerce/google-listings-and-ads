/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import Notification from '../notification';

const Sold10Items = ( { triggeredAt, onDismiss } ) => {
	return (
		<Notification
			title={ __(
				'Drive more sales with Google Ads',
				'google-listings-and-ads'
			) }
			description={ __(
				"Congrats on your first 10 sales – now let's find your next customer. Reach high-intent shoppers across Google. Get $500 USD or more in Google ad credit. Offer for new advertisers only. Terms apply.",
				'google-listings-and-ads'
			) }
			triggeredAt={ triggeredAt }
			onDismiss={ onDismiss }
			actions={ [
				{
					isPrimary: true,
					href: 'admin.php?page=wc-admin&path=/google/setup-ads',
					children: __(
						'Set up Google Ads campaign',
						'google-listings-and-ads'
					),
				},
			] }
		/>
	);
};

export default Sold10Items;
