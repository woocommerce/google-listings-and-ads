/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import useServiceBasedMerchant from '~/hooks/useServiceBasedMerchant';
import Notification from '../notification';

const SkippedCampaignCreation = ( { triggeredAt, onDismiss } ) => {
	const isServiceBased = useServiceBasedMerchant();

	const description = isServiceBased
		? __(
				'Your campaign is not live. Finish setup now to begin showing your business services across Google (Including Search, Shopping, YouTube, and more). Get $500 USD or more in Google ad credit. Offer for new advertisers only. Terms apply.',
				'google-listings-and-ads'
		  )
		: __(
				'Your campaign is not live. Finish setup now to begin showing your products across Google (Including Search, Shopping, YouTube, and more). Get $500 USD or more in Google ad credit. Offer for new advertisers only. Terms apply.',
				'google-listings-and-ads'
		  );

	return (
		<Notification
			title={ __(
				'Finish setting up Google Ads',
				'google-listings-and-ads'
			) }
			description={ description }
			triggeredAt={ triggeredAt }
			onDismiss={ onDismiss }
			actions={ [
				{
					isPrimary: true,
					href: 'admin.php?page=wc-admin&path=/google/setup-ads',
					children: __(
						'Complete Campaign Setup',
						'google-listings-and-ads'
					),
				},
			] }
		/>
	);
};

export default SkippedCampaignCreation;
