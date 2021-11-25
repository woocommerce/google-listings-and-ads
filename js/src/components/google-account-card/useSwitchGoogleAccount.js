/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { API_NAMESPACE } from '.~/data/constants';
import useGoogleAuthorization from '.~/hooks/useGoogleAuthorization';
import useDispatchCoreNotices from '.~/hooks/useDispatchCoreNotices';
import useApiFetchCallback from '.~/hooks/useApiFetchCallback';

/**
 * A hook that returns a handler that initiates Google account disconnect and connect, to support switching to a different Google account.
 * This will also disconnect Google Merchant Center account, since Google Merchant Center account depends on Google account.
 *
 * The `handleSwitch` handler is meant to be used in button click handler. Upon button click, the handler will:
 *
 * 1. Display an info notice that the process is running and request the users to wait.
 * 2. Call `DELETE /mc/connection` API to disconnect the existing connected Google Merchant Center account.
 * 3. Call `DELETE /google/connect` API to disconnect the existing connected Google account.
 * 4. Call `GET /google/connect` API to get the Google OAuth URL.
 * 5. Redirect the browser to the URL.
 * 6. If there is an error in the above process, it will display an error notice.
 *
 * @return {Array} `[ handleSwitch, { loading } ]`
 * 		- `handleSwitch` is meant to be used as button click handler.
 * 		- `loading` is a state to indicate that the process is running.
 */
const useSwitchGoogleAccount = () => {
	const { createNotice, removeNotice } = useDispatchCoreNotices();

	const [
		fetchGoogleMCDisconnect,
		{ loading: loadingGoogleMCDisconnect },
	] = useApiFetchCallback( {
		path: `${ API_NAMESPACE }/mc/connection`,
		method: 'DELETE',
	} );

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
	] = useGoogleAuthorization( 'setup-mc' );

	const handleSwitch = async () => {
		const { notice } = await createNotice(
			'info',
			__(
				'Connecting to a different Google account, please wait…',
				'google-listings-and-ads'
			)
		);

		try {
			await fetchGoogleMCDisconnect();
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
		loadingGoogleMCDisconnect ||
		loadingGoogleDisconnect ||
		loadingGoogleConnect ||
		dataGoogleConnect;

	return [ handleSwitch, { loading } ];
};

export default useSwitchGoogleAccount;
