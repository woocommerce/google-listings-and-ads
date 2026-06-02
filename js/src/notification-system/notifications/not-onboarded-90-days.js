/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import useServiceBasedMerchant from '~/hooks/useServiceBasedMerchant';
import Notification from '../notification';

const NotOnboarded90Days = ( { triggeredAt, onDismiss } ) => {
	const isServiceBased = useServiceBasedMerchant();

	const description = isServiceBased
		? __(
				'The plugin is active but not yet connected to a Google account. Link your account and start your first Google Ads campaign.',
				'google-listings-and-ads'
		  )
		: __(
				'The plugin is active but not yet connected to a Google account. Link your account to sync your product data and start showing your inventory to shoppers.',
				'google-listings-and-ads'
		  );

	return (
		<Notification
			title={ __(
				'Finish your Google for WooCommerce connection',
				'google-listings-and-ads'
			) }
			description={ description }
			triggeredAt={ triggeredAt }
			onDismiss={ onDismiss }
			actions={ [
				{
					isPrimary: true,
					href: 'admin.php?page=wc-admin&path=/google/start',
					children: __( 'Setup here', 'google-listings-and-ads' ),
				},
			] }
		/>
	);
};

export default NotOnboarded90Days;
