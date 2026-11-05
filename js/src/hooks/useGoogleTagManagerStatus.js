/**
 * Internal dependencies
 */
import { GOOGLE_TAG_MANAGER_ACCOUNT_STATUS } from '~/constants';
import useGoogleTagManagerAccount from './useGoogleTagManagerAccount';

/**
 * A hook to get the connection status of the Google Tag Manager account.
 *
 * @return {{ isConnected: boolean, hasFinishedResolution: boolean }} Whether a container is connected, and the resolution state.
 */
const useGoogleTagManagerStatus = () => {
	const { account, hasFinishedResolution } = useGoogleTagManagerAccount();

	return {
		isConnected:
			account?.status === GOOGLE_TAG_MANAGER_ACCOUNT_STATUS.CONNECTED,
		hasFinishedResolution,
	};
};

export default useGoogleTagManagerStatus;
