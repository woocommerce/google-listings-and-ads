/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import Notification from '../notification';

/**
 * Notification informing the user that personalized recommendations are available
 * to improve their Google Ads campaigns.
 *
 * @param {Object} props React props.
 * @param {number} props.triggeredAt Unix timestamp (ms) when the notification was triggered.
 * @param {Function} props.onDismiss Callback invoked when the notification is dismissed.
 * @return {JSX.Element} A {@link Notification} with a link to the Google dashboard.
 */
const RecommendationsAvailable = ( { triggeredAt, onDismiss } ) => {
	return (
		<Notification
			title={ __(
				'Improve your Google Ads campaigns',
				'google-listings-and-ads'
			) }
			description={ __(
				'You have personalized recommendations to improve your Google Ads campaigns.',
				'google-listings-and-ads'
			) }
			triggeredAt={ triggeredAt }
			onDismiss={ onDismiss }
			actions={ [
				{
					isPrimary: true,
					href: 'admin.php?page=wc-admin&path=/google/dashboard',
					children: __(
						'See recommendations here',
						'google-listings-and-ads'
					),
				},
			] }
		/>
	);
};

export default RecommendationsAvailable;
