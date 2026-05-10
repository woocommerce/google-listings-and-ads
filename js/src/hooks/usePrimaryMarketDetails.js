/**
 * Internal dependencies
 */
import useMarkets from './useMarkets';

/**
 * @typedef {import('~/data/actions').Market} Market
 */

/**
 * @typedef {Object} PrimaryMarketData
 * @property {Market|undefined} data The primary market details, or undefined if not yet loaded.
 * @property {boolean} hasFinishedResolution Whether the underlying selector has finished resolution.
 */

/**
 * Hook to retrieve the primary market details.
 *
 * @return {PrimaryMarketData} An object containing the primary market data and resolution status.
 */
const usePrimaryMarketDetails = () => {
	const { data: markets, hasFinishedResolution } = useMarkets();

	return {
		data: markets?.find( ( market ) => market.id === 'primary' ),
		hasFinishedResolution,
	};
};

export default usePrimaryMarketDetails;
