/**
 * External dependencies
 */
import { differenceBy } from 'lodash';

/**
 * @typedef {import('.~/data/actions').ShippingTime	} ShippingTime
 */

/**
 * Get deleted shipping times that exist in `oldShippingTimes` but not in `newShippingTimes`.
 *
 * @param {Array<ShippingTime>} newShippingTimes New shipping times.
 * @param {Array<ShippingTime>} oldShippingTimes Old shipping times.
 * @return {Array<ShippingTime>} Array containing shipping times that exist in oldShippingtimes but not in newShippingTimes.
 */
const getDeletedShippingTimes = ( newShippingTimes, oldShippingTimes ) => {
	return differenceBy( oldShippingTimes, newShippingTimes, 'countryCode' );
};

export default getDeletedShippingTimes;
