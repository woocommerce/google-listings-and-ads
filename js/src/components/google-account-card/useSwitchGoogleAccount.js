/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { useState } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { useAppDispatch } from '~/data';
import useGoogleAuthorization from '~/hooks/useGoogleAuthorization';
import useDispatchCoreNotices from '~/hooks/useDispatchCoreNotices';

/**
 * Hook to initiates Google account disconnect and get Google OAuth URL to reconnect.
 * It will also disconnect other connected accounts and WPCOM app to ensure a clean switch.
 *
 * The `handleSwitch` handler is meant to be used in button click handler. Upon button click, the handler will:
 *
 * 1. Display an info notice that the process is running and request the users to wait.
 * 2. Call `DELETE /connections` API to disconnect all the connected accounts and WPCOM app.
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
	const { disconnectAllAccounts } = useAppDispatch();
	const [ loading, setLoading ] = useState( false );

	const [ fetchGoogleConnect ] = useGoogleAuthorization( 'setup-mc' );

	const handleSwitch = async () => {
		setLoading( true );

		const { notice } = await createNotice(
			'info',
			__(
				'Connecting to a different Google account, please wait…',
				'google-listings-and-ads'
			)
		);

		try {
			await disconnectAllAccounts();
		} catch ( error ) {
			setLoading( false );
			removeNotice( notice.id );
			return;
		}

		try {
			const { url } = await fetchGoogleConnect();
			window.location.href = url;
		} catch ( error ) {
			removeNotice( notice.id );

			createNotice(
				'error',
				__(
					'Unable to get the URL for Google authorization. Please refresh the page to restart the onboarding.',
					'google-listings-and-ads'
				)
			);
		}
	};

	return [ handleSwitch, { loading } ];
};

export default useSwitchGoogleAccount;
