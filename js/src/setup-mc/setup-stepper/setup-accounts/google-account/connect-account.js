/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import AppButton from '.~/components/app-button';
import TitleButtonLayout from '../title-button-layout';
import useDispatchCoreNotices from '.~/hooks/useDispatchCoreNotices';
import useApiFetch from '.~/hooks/useApiFetch';

const ConnectAccount = ( props ) => {
	const { disabled = false } = props;
	const { createNotice } = useDispatchCoreNotices();
	const [ fetchGoogleConnect, { loading, data } ] = useApiFetch( {
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
					onClick={ handleConnectClick }
				>
					{ __( 'Connect', 'google-listings-and-ads' ) }
				</AppButton>
			}
		/>
	);
};

export default ConnectAccount;
