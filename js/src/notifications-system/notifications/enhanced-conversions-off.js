/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import Notification from '../notification';

const EnhancedConversionsOff = ( { triggeredAt, onDismiss } ) => {
	return (
		<Notification
			title={ __(
				'Enable Enhanced Conversions for accurate reporting',
				'google-listings-and-ads'
			) }
			description={ __(
				'To improve the accuracy of your sales data and attribution, you must enable Enhanced Conversions in your account settings.',
				'google-listings-and-ads'
			) }
			triggeredAt={ triggeredAt }
			onDismiss={ onDismiss }
			actions={ [
				{
					isPrimary: true,
					href: 'admin.php?page=wc-admin&path=/google/settings',
					children: __( 'Enable Feature', 'google-listings-and-ads' ),
				},
			] }
		/>
	);
};

export default EnhancedConversionsOff;
