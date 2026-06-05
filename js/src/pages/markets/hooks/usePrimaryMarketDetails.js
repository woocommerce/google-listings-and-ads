/**
 * Internal dependencies
 */
import { PRIMARY_MARKET_ID } from '../constants';
import useMarkets from './useMarkets';

/**
 * @typedef {import('~/data/actions').Market} Market
 *
 * @typedef {Object} PrimaryMarketDetails
 * @property {Market|undefined} data The synthesized primary market, or `undefined`
 *                                   while resolution is in flight or no primary
 *                                   entry is present.
 * @property {boolean} hasFinishedResolution Whether the underlying markets selector
 *                                           has finished resolution.
 */

/**
 * Hook returning the primary market entry from the markets list.
 *
 * The primary market is synthesized server-side and is always the first entry in
 * the response (see `MarketService::get_markets()`), but consumers should not rely
 * on its position — this hook is the single place that knows how to find it.
 *
 * @return {PrimaryMarketDetails} The primary market and its resolution state.
 */
const usePrimaryMarketDetails = () => {
	const { data, hasFinishedResolution } = useMarkets();

	return {
		data: data?.find( ( market ) => market.id === PRIMARY_MARKET_ID ),
		hasFinishedResolution,
	};
};

export default usePrimaryMarketDetails;
