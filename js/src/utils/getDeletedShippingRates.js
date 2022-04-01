/**
 * External dependencies
 */
import { differenceWith } from 'lodash';

/**
 * @typedef {import('.~/data/actions').ShippingRate} ShippingRate
 */

/**
 * Internal dependencies
 */
import isSameShippingRate from './isSameShippingRate';

/**
 * Get deleted shipping rates that exist in `oldShippingRates` but not in `newShippingRates`.
 *
 * @param {Array<ShippingRate>} newShippingRates New shipping rates.
 * @param {Array<ShippingRate>} oldShippingRates Old shipping rates.
 * @return {Array<ShippingRate>} Array containing shipping rates that exist in oldShippingRates but not in newShippingRates.
 */
const getDeletedShippingRates = ( newShippingRates, oldShippingRates ) => {
	return differenceWith(
		oldShippingRates,
		newShippingRates,
		isSameShippingRate
	);
};

export default getDeletedShippingRates;
