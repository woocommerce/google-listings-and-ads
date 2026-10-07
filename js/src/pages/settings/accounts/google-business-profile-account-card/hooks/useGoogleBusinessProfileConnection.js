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

const selectorName = 'getGoogleBusinessProfileConnection';

/**
 * A hook to load the Google Business Profile connection.
 *
 * @return {{ connection: GoogleBusinessProfileConnection|null, hasFinishedResolution: boolean }} The connection and its resolution state.
 */
const useGoogleBusinessProfileConnection = () => {
	return useSelect( ( select ) => {
		const selector = select( STORE_KEY );

		return {
			connection: selector[ selectorName ](),
			hasFinishedResolution: selector.hasFinishedResolution(
				selectorName,
				[]
			),
		};
	}, [] );
};

export default useGoogleBusinessProfileConnection;
