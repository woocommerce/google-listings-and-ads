/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import useServiceBasedMerchant from '~/hooks/useServiceBasedMerchant';
import Notification from '../notification';

const PausedCampaign = ( { triggeredAt, onDismiss } ) => {
	const isServiceBased = useServiceBasedMerchant();

	const description = isServiceBased
		? __( 'Your ads are not currently running.', 'google-listings-and-ads' )
		: __(
				'Your products are not currently appearing to shoppers.',
				'google-listings-and-ads'
		  );

	return (
		<Notification
			title={ __(
				'Your Google Ads campaign is paused',
				'google-listings-and-ads'
			) }
			description={ description }
			triggeredAt={ triggeredAt }
			onDismiss={ onDismiss }
			actions={ [
				{
					isPrimary: true,
					href: 'admin.php?page=wc-admin&path=/google/dashboard',
					children: __(
						'Resume your campaign',
						'google-listings-and-ads'
					),
				},
			] }
		/>
	);
};

export default PausedCampaign;
