/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import Notification from '../notification';

/**
 * Notification congratulating the user on their first 10 sales and prompting
 * them to start a Google Ads campaign to drive further growth.
 *
 * @param {Object} props React props.
 * @param {number} props.triggeredAt Unix timestamp (ms) when the notification was triggered.
 * @param {Function} props.onDismiss Callback invoked when the notification is dismissed.
 * @return {JSX.Element} A {@link Notification} with a link to the Google Ads setup flow.
 */
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
