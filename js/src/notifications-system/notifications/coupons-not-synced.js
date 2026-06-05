/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import useServiceBasedMerchant from '~/hooks/useServiceBasedMerchant';
import Notification from '../notification';

/**
 * Notification alerting the user that their WooCommerce coupons are not synced
 * to their Google feed, and prompting them to review coupon settings.
 *
 * @param {Object} props React props.
 * @param {number} props.triggeredAt Unix timestamp (ms) when the notification was triggered.
 * @param {Function} props.onDismiss Callback invoked when the notification is dismissed.
 * @return {JSX.Element} A {@link Notification} with a link to the Google for WooCommerce settings page.
 */
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
