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
 * Notification prompting the user to launch a Google Ads campaign when their
 * sales or site traffic have stagnated.
 *
 * @param {Object} props React props.
 * @param {number} props.triggeredAt Unix timestamp (ms) when the notification was triggered.
 * @param {Function} props.onDismiss Callback invoked when the notification is dismissed.
 * @return {JSX.Element} A {@link Notification} with a link to the Google Ads setup flow.
 */
const SalesNotGrowing = ( { triggeredAt, onDismiss } ) => {
	const isServiceBased = useServiceBasedMerchant();

	const title = isServiceBased
		? __( 'Increase your site traffic', 'google-listings-and-ads' )
		: __( "You're not growing sales", 'google-listings-and-ads' );

	const description = isServiceBased
		? __(
				'Generate more customers with Google Ads. Get $500 USD or more in Google ad credit. Offer for new advertisers only. Terms apply.',
				'google-listings-and-ads'
		  )
		: __(
				'Generate more sales with Google Ads. Get $500 USD or more in Google ad credit. Offer for new advertisers only. Terms apply.',
				'google-listings-and-ads'
		  );

	return (
		<Notification
			title={ title }
			description={ description }
			triggeredAt={ triggeredAt }
			onDismiss={ onDismiss }
			actions={ [
				{
					isPrimary: true,
					href: 'admin.php?page=wc-admin&path=/google/setup-ads',
					children: __(
						'Launch a campaign today',
						'google-listings-and-ads'
					),
				},
			] }
		/>
	);
};

export default SalesNotGrowing;
