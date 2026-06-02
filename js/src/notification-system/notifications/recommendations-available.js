/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import Notification from '../notification';

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
