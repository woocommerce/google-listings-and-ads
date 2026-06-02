/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import Notification from '../notification';

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
