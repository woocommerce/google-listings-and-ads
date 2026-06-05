/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import Notification from '../notification';

/**
 * Notification encouraging the user to enable tracking in order to receive
 * personalized recommendations for growing their business.
 *
 * @param {Object} props React props.
 * @param {number} props.triggeredAt Unix timestamp (ms) when the notification was triggered.
 * @param {Function} props.onDismiss Callback invoked when the notification is dismissed.
 * @return {JSX.Element} A {@link Notification} with a link to the WooCommerce advanced settings page.
 */
const TrackingOff = ( { triggeredAt, onDismiss } ) => {
	return (
		<Notification
			title={ __(
				'You are missing out on personalized recommendations',
				'google-listings-and-ads'
			) }
			description={ __(
				'Turn on tracking to receive relevant recommendations on how you can grow your business.',
				'google-listings-and-ads'
			) }
			triggeredAt={ triggeredAt }
			onDismiss={ onDismiss }
			actions={ [
				{
					isPrimary: true,
					href: 'admin.php?page=wc-settings&tab=advanced',
					children: __(
						'Turn on tracking',
						'google-listings-and-ads'
					),
				},
			] }
		/>
	);
};

export default TrackingOff;
