/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { glaData } from '.~/constants';
import { API_NAMESPACE } from '.~/data/constants';
import useGoogleAuthorization from '.~/hooks/useGoogleAuthorization';
import useDispatchCoreNotices from '.~/hooks/useDispatchCoreNotices';
import useApiFetchCallback from '.~/hooks/useApiFetchCallback';

/**
 * A hook that returns a handler that initiates Google account disconnect and connect, to support switching to a different Google account.
 *
 * The `handleSwitch` handler is meant to be used in button click handler. Upon button click, the handler will:
 *
 * 1. Display an info notice that the process is running and request the users to wait.
 * 2. Call `DELETE /google/connect` API to disconnect the existing connected Google account.
 * 3. Call `GET /google/connect` API to get the Google OAuth URL.
 * 4. Redirect the browser to the URL.
 * 5. If there is an error in the above process, it will display an error notice.
 *
 * @return {Array} `[ handleSwitch, loading ]`
 * 		- `handleSwitch` is meant to be used as button click handler.
 * 		- `loading` is a state to indicate that the process is running.
 */
const useSwitchGoogleAccount = () => {
	const pageName = glaData.mcSetupComplete ? 'reconnect' : 'setup-mc';
	const { createNotice, removeNotice } = useDispatchCoreNotices();

	/**
	 * Note: we are manually calling `DELETE /google/connect` instead of using
	 * `disconnectGoogleAccount` action from wp-data store
	 * because `disconnectGoogleAccount` will cause a store update,
	 * and the UI will display the Connect card for a brief moment,
	 * before the browser redirects to the Google Auth page.
	 */
	const [
		fetchGoogleDisconnect,
		{ loading: loadingGoogleDisconnect },
	] = useApiFetchCallback( {
		path: `${ API_NAMESPACE }/google/connect`,
		method: 'DELETE',
	} );

	const [
		fetchGoogleConnect,
		{ loading: loadingGoogleConnect, data: dataGoogleConnect },
	] = useGoogleAuthorization( pageName );

	const handleSwitch = async () => {
		const { notice } = await createNotice(
			'info',
			__(
				'Connecting to a different Google account, please wait…',
				'google-listings-and-ads'
			)
		);

		try {
			await fetchGoogleDisconnect();
			const { url } = await fetchGoogleConnect();
			window.location.href = url;
		} catch ( error ) {
			removeNotice( notice.id );
			createNotice(
				'error',
				__(
					'Unable to connect to a different Google account. Please try again later.',
					'google-listings-and-ads'
				)
			);
		}
	};

	const loading =
		loadingGoogleDisconnect || loadingGoogleConnect || dataGoogleConnect;

	return [ handleSwitch, loading ];
};

export default useSwitchGoogleAccount;
