/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { addQueryArgs } from '@wordpress/url';

/**
 * Internal dependencies
 */
import { API_NAMESPACE } from '~/data/constants';
import AppButton from '~/components/app-button';
import useDispatchCoreNotices from '~/hooks/useDispatchCoreNotices';
import useApiFetchCallback from '~/hooks/useApiFetchCallback';

/**
 * Clicking on the button to connect YouTube account.
 *
 * @event gla_youtube_account_connect_button_click
 * @property {string} context Indicates from which page the button was clicked. Possible value: 'settings-youtube'.
 */

/**
 * Renders the "Connect" button that starts the YouTube account flow.
 *
 * @fires gla_youtube_account_connect_button_click
 *
 * @return {JSX.Element} The connect button.
 */
const ConnectButton = () => {
	const { createNotice } = useDispatchCoreNotices();

	const path = addQueryArgs( `${ API_NAMESPACE }/youtube/connect`, {
		next_page_name: 'setup-youtube',
	} );
	const [ fetchYouTubeConnect, { loading, data } ] = useApiFetchCallback( {
		path,
	} );

	const handleConnectClick = async () => {
		try {
			const response = await fetchYouTubeConnect();
			window.location.href = response.url;
		} catch ( error ) {
			createNotice(
				'error',
				__(
					'Unable to connect your YouTube account. Please try again later.',
					'google-listings-and-ads'
				)
			);
		}
	};

	return (
		<AppButton
			loading={ loading || data }
			eventName="gla_youtube_account_connect_button_click"
			eventProps={ { context: 'settings-youtube' } }
			onClick={ handleConnectClick }
			isSecondary
		>
			{ __( 'Connect', 'google-listings-and-ads' ) }
		</AppButton>
	);
};

export default ConnectButton;
