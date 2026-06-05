/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import Notification from '../notification';

/**
 * Notification prompting the user to complete an interrupted Google Ads setup.
 *
 * @param {Object} props React props.
 * @param {number} props.triggeredAt Unix timestamp (ms) when the notification was triggered.
 * @param {Function} props.onDismiss Callback invoked when the notification is dismissed.
 * @return {JSX.Element} A {@link Notification} with a link to resume the onboarding flow.
 */
const AbandonedOnboarding = ( { triggeredAt, onDismiss } ) => {
	return (
		<Notification
			title={ __(
				'Finish your Google Ads setup',
				'google-listings-and-ads'
			) }
			description={ __(
				'Your Google Ads integration setup was interrupted. Complete the remaining configuration steps to ensure your store data is properly synced. Get $500 USD or more in Google ad credit. Offer for new advertisers only. Terms apply.',
				'google-listings-and-ads'
			) }
			triggeredAt={ triggeredAt }
			onDismiss={ onDismiss }
			actions={ [
				{
					isPrimary: true,
					href: 'admin.php?page=wc-admin&path=/google/start',
					children: __( 'Continue Setup', 'google-listings-and-ads' ),
				},
			] }
		/>
	);
};

export default AbandonedOnboarding;
