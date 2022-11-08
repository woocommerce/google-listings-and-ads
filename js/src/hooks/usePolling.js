/**
 * External dependencies
 */
import { useEffect, useCallback } from '@wordpress/element';

/**
 * Internal dependencies
 */
import useApiFetchCallback from './useApiFetchCallback';
import useCountdown from '.~/hooks/useCountdown';

/**
 * A hook for polling to an API Endpoint on intervals
 *
 * @param {import('@wordpress/api-fetch').APIFetchOptions} options Options to be forwarded to `apiFetch`.
 * @param {number} [delay=20] Time in seconds to wait for each polling request
 * @param {boolean} [stopOnResolved=false] If true. It stops the polling when the data is received
 * @return {Object} Payload containing the result of the API call as well as a trigger function for starting the polling.
 */
export default function usePolling(
	options,
	delay = 10,
	stopOnResolved = false
) {
	const { second, callCount, startCountdown } = useCountdown();
	const [ fetch, { data } ] = useApiFetchCallback( options );
	const shouldRun = ! data || ! stopOnResolved;

	const start = useCallback( () => {
		const promise = fetch();
		promise.finally( () => startCountdown( delay ) );
		return promise;
	}, [ fetch, startCountdown, delay ] );

	useEffect( () => {
		if ( second === 0 && callCount > 0 && shouldRun ) {
			start();
		}
	}, [ second, callCount, start, shouldRun ] );

	return {
		start,
		data,
	};
}
