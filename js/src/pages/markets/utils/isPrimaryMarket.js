/**
 * Internal dependencies
 */
import { PRIMARY_MARKET_ID } from '../constants';

/**
 * Checks if the given market is the primary market.
 *
 * @param {Object} market The market to check.
 * @return {boolean} True if the market is the primary market, false otherwise.
 */
export const isPrimaryMarket = ( market ) => market.id === PRIMARY_MARKET_ID;
