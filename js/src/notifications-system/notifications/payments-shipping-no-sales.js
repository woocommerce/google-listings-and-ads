/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import Notification from '../notification';

/**
 * Notification encouraging a user who has set up payments and shipping but has
 * not yet made any sales to start a Google Ads campaign.
 *
 * @param {Object} props React props.
 * @param {number} props.triggeredAt Unix timestamp (ms) when the notification was triggered.
 * @param {Function} props.onDismiss Callback invoked when the notification is dismissed.
 * @return {JSX.Element} A {@link Notification} with a link to the Google Ads setup flow.
 */
const PaymentsShippingNoSales = ( { triggeredAt, onDismiss } ) => {
	return (
		<Notification
			title={ __(
				'Get more sales with Google Ads',
				'google-listings-and-ads'
			) }
			description={ __(
				"Reach the right shoppers when they're searching for products like yours across Google (including Search, Shopping, YouTube, and more) in just a few easy steps! Get $500 USD or more in Google ad credit. Offer for new advertisers only. Terms apply.",
				'google-listings-and-ads'
			) }
			triggeredAt={ triggeredAt }
			onDismiss={ onDismiss }
			actions={ [
				{
					isPrimary: true,
					href: 'admin.php?page=wc-admin&path=/google/setup-ads',
					children: __( 'Get started', 'google-listings-and-ads' ),
				},
			] }
		/>
	);
};

export default PaymentsShippingNoSales;
