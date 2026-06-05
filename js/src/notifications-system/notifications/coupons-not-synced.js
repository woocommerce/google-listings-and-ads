/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import useServiceBasedMerchant from '~/hooks/useServiceBasedMerchant';
import Notification from '../notification';

const CouponsNotSynced = ( { triggeredAt, onDismiss } ) => {
	const isServiceBased = useServiceBasedMerchant();

	const description = isServiceBased
		? __(
				'Your WooCommerce coupons are not currently synced to your Google feed. Sync them today to show these offers to customers searching for your products.',
				'google-listings-and-ads'
		  )
		: __(
				'Your WooCommerce coupons are not currently synced to your Google feed. Sync them today to show these offers to shoppers searching for your products.',
				'google-listings-and-ads'
		  );

	return (
		<Notification
			title={ __(
				'Promote your coupons on Google',
				'google-listings-and-ads'
			) }
			description={ description }
			triggeredAt={ triggeredAt }
			onDismiss={ onDismiss }
			actions={ [
				{
					isPrimary: true,
					href: 'admin.php?page=wc-admin&path=/google/settings',
					children: __(
						'Review coupon settings',
						'google-listings-and-ads'
					),
				},
			] }
		/>
	);
};

export default CouponsNotSynced;
