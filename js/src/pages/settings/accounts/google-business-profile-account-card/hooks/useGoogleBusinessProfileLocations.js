/**
 * External dependencies
 */
import { useSelect } from '@wordpress/data';

/**
 * Internal dependencies
 */
import { STORE_KEY } from '~/data/constants';

/**
 * @typedef {import('~/data/types.js').GoogleBusinessProfileLocation} GoogleBusinessProfileLocation
 */

const selectorName = 'getGoogleBusinessProfileLocations';

/**
 * A hook to load the Google Business Profile locations available to the connected Google user.
 *
 * @return {{ locations: GoogleBusinessProfileLocation[]|null, hasFinishedResolution: boolean }} The data and its resolution state.
 */
const useGoogleBusinessProfileLocations = () => {
	return useSelect( ( select ) => {
		const selector = select( STORE_KEY );

		return {
			locations: selector[ selectorName ](),
			hasFinishedResolution: selector.hasFinishedResolution(
				selectorName,
				[]
			),
		};
	}, [] );
};

export default useGoogleBusinessProfileLocations;
