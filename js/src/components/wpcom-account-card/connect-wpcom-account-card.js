/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { addQueryArgs } from '@wordpress/url';

/**
 * Internal dependencies
 */
import { glaData } from '.~/constants';
import { API_NAMESPACE } from '.~/data/constants';
import AppButton from '.~/components/app-button';
import AccountCard, { APPEARANCE } from '.~/components/account-card';
import useDispatchCoreNotices from '.~/hooks/useDispatchCoreNotices';
import useApiFetchCallback from '.~/hooks/useApiFetchCallback';

const ConnectWPComAccountCard = () => {
	const { createNotice } = useDispatchCoreNotices();

	const next = glaData.mcSetupComplete ? 'reconnect' : 'setup-mc';
	const path = addQueryArgs( `${ API_NAMESPACE }/jetpack/connect`, { next } );
	const [ fetchJetpackConnect, { loading, data } ] = useApiFetchCallback( {
		path,
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
		<AccountCard
			appearance={ APPEARANCE.WPCOM }
			description={ __(
				'Required to connect with Google',
				'google-listings-and-ads'
			) }
			indicator={
				<AppButton
					isSecondary
					loading={ loading || data }
					eventName="gla_wordpress_account_connect_button_click"
					eventProps={ { context: next } }
					onClick={ handleConnectClick }
				>
					{ __( 'Connect', 'google-listings-and-ads' ) }
				</AppButton>
			}
		/>
	);
};

export default ConnectWPComAccountCard;
