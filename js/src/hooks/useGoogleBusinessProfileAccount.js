/**
 * External dependencies
 */
import { useSelect } from '@wordpress/data';

/**
 * Internal dependencies
 */
import { STORE_KEY } from '~/data/constants';

/**
 * @typedef {import('~/data/types.js').GoogleBusinessProfileConnection} GoogleBusinessProfileConnection
 */

const selectorName = 'getGoogleBusinessProfileAccount';

/**
 * A hook to load the connection data of the Google Business Profile account.
 *
 * `hasFinishedResolution` is `false` until the connection has actually loaded, and `true` from
 * then on — gate rendering on it directly.
 *
 * @return {{ account: GoogleBusinessProfileConnection|null, hasFinishedResolution: boolean }} The data and its resolution state.
 */
const useGoogleBusinessProfileAccount = () => {
	return useSelect( ( select ) => {
		const selector = select( STORE_KEY );

		return {
			account: selector[ selectorName ](),
			hasFinishedResolution: selector.hasFinishedResolution(
				selectorName,
				[]
			),
		};
	}, [] );
};

export default useGoogleBusinessProfileAccount;
