/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import Notification from '../notification';

/**
 * Notification prompting the user to enable Enhanced Conversions to improve
 * the accuracy of their sales data and attribution reporting.
 *
 * @param {Object} props React props.
 * @param {number} props.triggeredAt Unix timestamp (ms) when the notification was triggered.
 * @param {Function} props.onDismiss Callback invoked when the notification is dismissed.
 * @return {JSX.Element} A {@link Notification} with a link to the Google for WooCommerce settings page.
 */
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
