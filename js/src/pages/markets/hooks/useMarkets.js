/**
 * External dependencies
 */
import { useSelect } from '@wordpress/data';

/**
 * Internal dependencies
 */
import { STORE_KEY } from '~/data/constants';

const selectorName = 'getMarkets';

/**
 * @typedef {import('~/data/actions').Market} Market
 */

/**
 * @typedef {Object} MarketsData
 * @property {Array<Market>} data The list of markets.
 * @property {boolean} hasFinishedResolution Whether the selector has finished resolution.
 */

/**
 * Hook to retrieve the list of markets.
 *
 * @return {MarketsData} An object containing the markets data and resolution status.
 */
const useMarkets = () => {
	return useSelect( ( select ) => {
		const selector = select( STORE_KEY );
		const data = selector[ selectorName ]();

		return {
			data,
			hasFinishedResolution:
				selector.hasFinishedResolution( selectorName ),
		};
	}, [] );
};

export default useMarkets;
