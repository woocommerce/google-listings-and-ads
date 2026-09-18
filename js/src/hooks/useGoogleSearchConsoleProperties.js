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
 * A hook to load the candidate Google Search Console properties the merchant can choose between
 * to complete the connection, or to recover one that's since become unusable. Only `incomplete`
 * (a genuine multi-match choice pending) and `action-needed` (the previously selected property
 * needs replacing) ever have a reason to list candidates, so the store selector is only called
 * for those two statuses — every other status (including not-yet-resolved) skips the fetch
 * entirely.
 *
 * @return {{ properties: GoogleSearchConsoleProperty[]|null, hasFinishedResolution: boolean }} The data and its resolution state, or `{ properties: undefined, hasFinishedResolution }` (taken from the account's own resolution state) while there's no candidate list to fetch.
 */
const useGoogleSearchConsoleProperties = () => {
	const { account, hasFinishedResolution: hasResolvedAccount } =
		useGoogleSearchConsoleAccount();
	const shouldFetch = [
		GOOGLE_SEARCH_CONSOLE_ACCOUNT_STATUS.INCOMPLETE,
		GOOGLE_SEARCH_CONSOLE_ACCOUNT_STATUS.ACTION_NEEDED,
	].includes( account?.status );

	return useSelect(
		( select ) => {
			if ( ! shouldFetch ) {
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
		[ shouldFetch, hasResolvedAccount ]
	);
};

export default useGoogleSearchConsoleProperties;
