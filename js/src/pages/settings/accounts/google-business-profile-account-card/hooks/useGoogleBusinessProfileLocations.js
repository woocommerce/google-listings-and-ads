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
 * @property {boolean} hasLocationsError Whether the locations request finished without a list, i.e. it failed.
 * @property {boolean} hasNoLocations Whether the locations loaded and none were found.
 * @property {GoogleBusinessProfileLocation|null} singleLocation The only location when exactly one was found, otherwise `null`.
 */

const selectorName = 'getGoogleBusinessProfileLocations';

/**
 * A hook to load the Google Business Profile locations available to the connected Google user,
 * along with which of the card's states they put it in: loading failed, no location, exactly
 * one location, or (when none of those apply once loaded) several locations.
 *
 * @return {GoogleBusinessProfileLocationsState} The data, its resolution state, and the derived location states.
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
			hasLocationsError: hasFinishedResolution && ! locations,
			hasNoLocations: locations?.length === 0,
			singleLocation: locations?.length === 1 ? locations[ 0 ] : null,
		};
	}, [] );
};

export default useGoogleBusinessProfileLocations;
