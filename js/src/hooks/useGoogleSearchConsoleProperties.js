/**
 * External dependencies
 */
import { useSelect } from '@wordpress/data';

/**
 * Internal dependencies
 */
import { STORE_KEY } from '~/data/constants';
import { GOOGLE_SEARCH_CONSOLE_ACCOUNT_STATUS } from '~/constants';
import useGoogleSearchConsoleAccount from './useGoogleSearchConsoleAccount';

/**
 * @typedef {import('~/data/types.js').GoogleSearchConsoleProperty} GoogleSearchConsoleProperty
 */

const selectorName = 'getGoogleSearchConsoleProperties';

/**
 * A hook to load the candidate Google Search Console properties the merchant can choose
 * between to complete the connection. Only the `incomplete` status ever has a genuine
 * multi-match choice pending, so the store selector is only called then — every other status
 * (including not-yet-resolved) skips the fetch entirely.
 *
 * @return {{ properties: GoogleSearchConsoleProperty[]|null, hasFinishedResolution: boolean }} The data and its resolution state, or `{ properties: undefined, hasFinishedResolution }` (taken from the account's own resolution state) while there's no `incomplete` account to list candidates for.
 */
const useGoogleSearchConsoleProperties = () => {
	const { account, hasFinishedResolution: hasResolvedAccount } =
		useGoogleSearchConsoleAccount();
	const isIncomplete =
		account?.status === GOOGLE_SEARCH_CONSOLE_ACCOUNT_STATUS.INCOMPLETE;

	return useSelect(
		( select ) => {
			if ( ! isIncomplete ) {
				return {
					properties: undefined,
					hasFinishedResolution: hasResolvedAccount,
				};
			}

			const selector = select( STORE_KEY );

			return {
				properties: selector[ selectorName ](),
				hasFinishedResolution: selector.hasFinishedResolution(
					selectorName,
					[]
				),
			};
		},
		[ isIncomplete, hasResolvedAccount ]
	);
};

export default useGoogleSearchConsoleProperties;
