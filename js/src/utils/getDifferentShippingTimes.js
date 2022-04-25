/**
 * External dependencies
 */
import { differenceWith, isEqual } from 'lodash';

/**
 * @typedef {import('.~/data/actions').ShippingTime	} ShippingTime
 */

/**
 * Get shipping times from `shippingTimes1` that are different from shipping times in `shippingTimes2`.
 *
 * A shipping time in `shippingTimes1` is considered different when:
 *
 * - it is a newly added shipping time and does not exist in `shippingTimes2`, or
 * - it has been edited and it is different from the one in `shippingTimes2`.
 *
 * Note that the term "difference" here relates to the term "set difference" in set theory.
 * See https://en.wikipedia.org/wiki/Complement_(set_theory)#Relative_complement.
 *
 * @param {Array<ShippingTime>} shippingTimes1 Array of shipping times. This will be used to compare against shippingTimes2.
 * @param {Array<ShippingTime>} shippingTimes2 Array of shipping times.
 * @return {Array<ShippingTime>} Array containing shipping times from shippingTimes1 that are different from shipping times in shippingTimes2.
 */
const getDifferentShippingTimes = ( shippingTimes1, shippingTimes2 ) => {
	return differenceWith( shippingTimes1, shippingTimes2, isEqual );
};

export default getDifferentShippingTimes;
