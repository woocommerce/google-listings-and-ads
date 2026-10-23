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

/**
 * @typedef {Object} GoogleBusinessProfileLocationsState
 * @property {GoogleBusinessProfileLocation[]|null} locations The candidate locations, or `null` before they've loaded or when they couldn't be loaded.
 * @property {boolean} hasFinishedResolution Whether the locations request has finished.
 */

const selectorName = 'getGoogleBusinessProfileLocations';

/**
 * A hook to load the Google Business Profile locations available to the connected Google user.
 *
 * @return {GoogleBusinessProfileLocationsState} The locations and their resolution state.
 */
const useGoogleBusinessProfileLocations = () => {
	return useSelect( ( select ) => {
		const selector = select( STORE_KEY );
		const locations = selector[ selectorName ]();
		const hasFinishedResolution = selector.hasFinishedResolution(
			selectorName,
			[]
		);

		return {
			locations,
			hasFinishedResolution,
		};
	}, [] );
};

export default useGoogleBusinessProfileLocations;
