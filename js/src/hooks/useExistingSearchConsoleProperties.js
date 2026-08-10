/**
 * Internal dependencies
 */
import { useAppDispatch } from '~/data';
import useSearchConsoleAccount from './useSearchConsoleAccount';

/**
 * @typedef {import('./useSearchConsoleAccount.js').SearchConsoleProperty} SearchConsoleProperty
 */

/**
 * A hook to load the list of candidate Search Console properties for the multi-property selector.
 *
 * Unlike `useExistingGoogleMCAccounts`/`useExistingGoogleAdsAccounts`, this doesn't hit a dedicated
 * REST resolver of its own — Search Console mirrors YouTube's single-endpoint store shape, so the candidate `properties` list is already part of the single Search
 * Console connection payload. This hook only reshapes that same data into the existing-accounts-list
 * hook shape (`data`/`isResolving`/`hasFinishedResolution`/`invalidateResolution`) expected by the
 * property selector.
 *
 * @return {{ data: SearchConsoleProperty[], isResolving: boolean, hasFinishedResolution: boolean, invalidateResolution: Function }} The data and its state.
 */
const useExistingSearchConsoleProperties = () => {
	const { invalidateResolution } = useAppDispatch();
	const { searchConsoleAccount, hasFinishedResolution } =
		useSearchConsoleAccount();

	return {
		data: searchConsoleAccount?.properties ?? [],
		isResolving: ! hasFinishedResolution,
		hasFinishedResolution,
		invalidateResolution: () =>
			invalidateResolution( 'getSearchConsoleAccount', [] ),
	};
};

export default useExistingSearchConsoleProperties;
