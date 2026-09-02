/**
 * External dependencies
 */
import { useSelect } from '@wordpress/data';

/**
 * Internal dependencies
 */
import { STORE_KEY } from '~/data/constants';

/**
 * @typedef {import('~/data/types.js').GoogleSearchConsoleMatch} GoogleSearchConsoleMatch
 */

const selectorName = 'getGoogleSearchConsoleProperties';

/**
 * A hook to load the candidate Google Search Console properties the merchant can choose
 * between to complete the connection. A standalone listing, separate from the connection's
 * own status — see `~/hooks/useGoogleSearchConsoleAccount`.
 *
 * @return {{ properties: GoogleSearchConsoleMatch[]|null, hasFinishedResolution: boolean }} The data and its resolution state.
 */
const useGoogleSearchConsoleProperties = () => {
	return useSelect( ( select ) => {
		const selector = select( STORE_KEY );

		return {
			properties: selector[ selectorName ](),
			hasFinishedResolution: selector.hasFinishedResolution(
				selectorName,
				[]
			),
		};
	}, [] );
};

export default useGoogleSearchConsoleProperties;
