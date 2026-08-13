/**
 * Internal dependencies
 */
import { SEARCH_CONSOLE_ACCOUNT_STATUS } from '~/constants';
import useSearchConsoleAccount from './useSearchConsoleAccount';

/**
 * A hook that derives the Google Search Console account's connected/incomplete status.
 *
 * @return {{ status: (string|undefined), isConnected: boolean, isIncomplete: boolean, hasFinishedResolution: boolean }} The derived status.
 */
const useGoogleSearchConsoleAccountStatus = () => {
	const { searchConsoleAccount, hasFinishedResolution } =
		useSearchConsoleAccount();
	const status = searchConsoleAccount?.status;

	return {
		status,
		isConnected: status === SEARCH_CONSOLE_ACCOUNT_STATUS.CONNECTED,
		isIncomplete: status === SEARCH_CONSOLE_ACCOUNT_STATUS.INCOMPLETE,
		hasFinishedResolution,
	};
};

export default useGoogleSearchConsoleAccountStatus;
