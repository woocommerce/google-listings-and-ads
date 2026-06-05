/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import useServiceBasedMerchant from '~/hooks/useServiceBasedMerchant';
import Notification from '../notification';

const SalesNotGrowing = ( { triggeredAt, onDismiss } ) => {
	const isServiceBased = useServiceBasedMerchant();

	const title = isServiceBased
		? __( 'Increase your site traffic', 'google-listings-and-ads' )
		: __( "You're not growing sales", 'google-listings-and-ads' );

	const description = isServiceBased
		? __(
				'Generate more customers with Google Ads. Get $500 USD or more in Google ad credit. Offer for new advertisers only. Terms apply.',
				'google-listings-and-ads'
		  )
		: __(
				'Generate more sales with Google Ads. Get $500 USD or more in Google ad credit. Offer for new advertisers only. Terms apply.',
				'google-listings-and-ads'
		  );

	return (
		<Notification
			title={ title }
			description={ description }
			triggeredAt={ triggeredAt }
			onDismiss={ onDismiss }
			actions={ [
				{
					isPrimary: true,
					href: 'admin.php?page=wc-admin&path=/google/setup-ads',
					children: __(
						'Launch a campaign today',
						'google-listings-and-ads'
					),
				},
			] }
		/>
	);
};

export default SalesNotGrowing;
