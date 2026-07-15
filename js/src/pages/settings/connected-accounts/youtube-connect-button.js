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
 * Renders the "Connect" button for the YouTube account row, reusing the same
 * connect flow as the standalone YouTube account card.
 *
 * @return {JSX.Element} The connect button.
 */
export default function YouTubeConnectButton() {
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
			isSecondary
			loading={ loading || data }
			eventName="gla_youtube_account_connect_button_click"
			eventProps={ { context: 'settings-youtube' } }
			onClick={ handleConnectClick }
		>
			{ __( 'Connect', 'google-listings-and-ads' ) }
		</AppButton>
	);
}
