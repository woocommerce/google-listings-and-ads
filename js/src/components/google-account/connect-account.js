/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import AppButton from '.~/components/app-button';
import TitleButtonLayout from '.~/components/title-button-layout';
import useDispatchCoreNotices from '.~/hooks/useDispatchCoreNotices';
import useApiFetchCallback from '.~/hooks/useApiFetchCallback';

const ConnectAccount = ( props ) => {
	const { disabled = false } = props;
	const { createNotice } = useDispatchCoreNotices();
	const [ fetchGoogleConnect, { loading, data } ] = useApiFetchCallback( {
		path: '/wc/gla/google/connect',
	} );

	const handleConnectClick = async () => {
		try {
			const d = await fetchGoogleConnect();

			window.location.href = d.url;
		} catch ( error ) {
			createNotice(
				'error',
				__(
					'Unable to connect your Google account. Please try again later.',
					'google-listings-and-ads'
				)
			);
		}
	};

	return (
		<TitleButtonLayout
			title={ __(
				'Connect your Google account',
				'google-listings-and-ads'
			) }
			button={
				<AppButton
					isSecondary
					disabled={ disabled }
					loading={ loading || data }
					eventName="gla_google_account_connect_button_click"
					onClick={ handleConnectClick }
				>
					{ __( 'Connect', 'google-listings-and-ads' ) }
				</AppButton>
			}
		/>
	);
};

export default ConnectAccount;
