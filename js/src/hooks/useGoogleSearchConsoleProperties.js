/**
 * External dependencies
 */
import { useSelect } from '@wordpress/data';

/**
 * Internal dependencies
 */
import { STORE_KEY } from '~/data/constants';

/**
 * @typedef {import('~/data/types.js').GoogleSearchConsoleProperty} GoogleSearchConsoleProperty
 */

const selectorName = 'getGoogleSearchConsoleProperties';

/**
 * A hook to load the candidate Google Search Console properties the merchant can choose
 * between to complete the connection.
 *
 * @param {Object} [options] Options.
 * @param {boolean} [options.skip] When `true`, never calls the store selector — so a caller
 *   that only needs this data for one particular status (e.g. an indicator shared across every
 *   incomplete-flow sub-state) doesn't trigger the properties fetch for the others.
 * @return {{ properties: GoogleSearchConsoleProperty[]|null, hasFinishedResolution: boolean }} The data and its resolution state, or `{ properties: undefined, hasFinishedResolution: false }` while skipped.
 */
const useGoogleSearchConsoleProperties = ( { skip = false } = {} ) => {
	return useSelect(
		( select ) => {
			if ( skip ) {
				return { properties: undefined, hasFinishedResolution: false };
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
		[ skip ]
	);
};

export default useGoogleSearchConsoleProperties;
