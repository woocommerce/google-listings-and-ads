/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import Notification from '../notification';

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
