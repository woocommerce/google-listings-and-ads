/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import AppButton from '.~/components/app-button';
import useDispatchCoreNotices from '.~/hooks/useDispatchCoreNotices';
import useApiFetchCallback from '.~/hooks/useApiFetchCallback';
import WPComAccountCard from './wpcom-account-card';

/**
 * Clicking on the button to connect WordPress.com account.
 *
 * @event gla_wordpress_account_connect_button_click
 */

/**
 * @fires gla_wordpress_account_connect_button_click
 */
const ConnectWPComAccountCard = () => {
	const { createNotice } = useDispatchCoreNotices();
	const [ fetchJetpackConnect, { loading, data } ] = useApiFetchCallback( {
		path: '/wc/gla/jetpack/connect',
	} );

	const handleConnectClick = async () => {
		try {
			const d = await fetchJetpackConnect();
			window.location.href = d.url;
		} catch ( error ) {
			createNotice(
				'error',
				__(
					'Unable to connect your WordPress.com account. Please try again later.',
					'google-listings-and-ads'
				)
			);
		}
	};

	return (
		<WPComAccountCard
			description={ __(
				'Required to connect with Google',
				'google-listings-and-ads'
			) }
			indicator={
				<AppButton
					isSecondary
					loading={ loading || data }
					eventName="gla_wordpress_account_connect_button_click"
					onClick={ handleConnectClick }
				>
					{ __( 'Connect', 'google-listings-and-ads' ) }
				</AppButton>
			}
		/>
	);
};

export default ConnectWPComAccountCard;
