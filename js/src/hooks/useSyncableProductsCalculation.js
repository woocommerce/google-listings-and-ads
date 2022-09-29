/**
 * External dependencies
 */
import { useEffect, useCallback } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { API_NAMESPACE } from '.~/data/constants';
import useApiFetchCallback from './useApiFetchCallback';
import useCountdown from './useCountdown';

/**
 * @typedef {Object} SyncableProductsCalculation
 * @property {number|null} count The number of syncable products. `null` if it's still in the calculation.
 * @property {()=>Promise} request The function requesting the start of a calculation.
 * @property {()=>Promise} retrieve The function retrieving the result of a requested calculation by polling with 5-second timer.
 */

const getOptions = {
	path: `${ API_NAMESPACE }/mc/syncable-products-count`,
};
const postOptions = {
	...getOptions,
	method: 'POST',
};

/**
 * A hook for communicating with the calculation of the syncable products and for returning the result.
 *
 * If a shop has a large number of products, requesting the result with a single API may encounter
 * a timeout or out-of-memory problem. Therefore, we use an API to schedule a batch for calculating,
 * and another one to poll the result.
 *
 * @return {SyncableProductsCalculation} Payload containing the result of calculation and the functions for requesting and retrieving a calculation.
 */
export default function useSyncableProductsCalculation() {
	const { second, callCount, startCountdown } = useCountdown();
	const [ fetch, { data } ] = useApiFetchCallback( getOptions );
	const [ request ] = useApiFetchCallback( postOptions );

	// The number 0 is a valid value.
	const count = data?.count ?? null;

	const retrieve = useCallback( () => {
		const promise = fetch();
		promise.finally( () => startCountdown( 5 ) );
		return promise;
	}, [ fetch, startCountdown ] );

	useEffect( () => {
		if ( second === 0 && callCount > 0 && count === null ) {
			retrieve();
		}
	}, [ second, callCount, count, retrieve ] );

	return {
		request,
		retrieve,
		count,
	};
}
