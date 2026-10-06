/**
 * External dependencies
 */
import apiFetch from '@wordpress/api-fetch';
import { addQueryArgs } from '@wordpress/url';
import { useCallback, useEffect, useRef, useState } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { API_NAMESPACE } from '~/data/constants';

/**
 * How often the summary is re-fetched while the page is open.
 * Matches the server-side cache lifetime.
 */
const POLL_INTERVAL_MS = 30 * 1000;

/**
 * Fetches an endpoint on mount, optionally re-fetching on an interval.
 *
 * @param {string} path Route under the plugin namespace.
 * @param {Object} [options]
 * @param {boolean} [options.enabled=true] Whether to fetch at all.
 * @param {number} [options.interval=0] Polling interval in ms, 0 to disable.
 * @return {{ data: Object|undefined, error: Object|undefined, loading: boolean, load: Function }} Fetch state and a `load` that accepts query args.
 */
const useHealthEndpoint = ( path, { enabled = true, interval = 0 } = {} ) => {
	const [ state, setState ] = useState( {
		data: undefined,
		error: undefined,
		loading: enabled,
	} );
	const mounted = useRef( true );

	const load = useCallback(
		( query = {} ) => {
			setState( ( prev ) => ( { ...prev, loading: true } ) );

			return apiFetch( {
				path: addQueryArgs( `${ API_NAMESPACE }/${ path }`, query ),
			} )
				.then( ( data ) => {
					if ( mounted.current ) {
						setState( { data, error: undefined, loading: false } );
					}
				} )
				.catch( ( error ) => {
					if ( mounted.current ) {
						setState( ( prev ) => ( {
							...prev,
							error,
							loading: false,
						} ) );
					}
				} );
		},
		[ path ]
	);

	useEffect( () => {
		mounted.current = true;

		return () => {
			mounted.current = false;
		};
	}, [] );

	useEffect( () => {
		if ( ! enabled ) {
			return;
		}

		load();

		if ( ! interval ) {
			return;
		}

		const id = setInterval( () => load(), interval );
		return () => clearInterval( id );
	}, [ enabled, interval, load ] );

	return { ...state, load };
};

/**
 * Polls the overall sync health summary.
 *
 * @return {{ data: Object|undefined, error: Object|undefined, loading: boolean, refresh: Function }} Summary state and a `refresh` that bypasses the server cache.
 */
export const useSyncHealth = () => {
	const { load, ...state } = useHealthEndpoint( 'sync-health', {
		interval: POLL_INTERVAL_MS,
	} );

	const refresh = useCallback( () => load( { refresh: true } ), [ load ] );

	return { ...state, refresh };
};

/**
 * Fetches per-job Action Scheduler counts. Only fetches once `enabled` is true,
 * so the technical section costs nothing until it is opened.
 *
 * @param {boolean} enabled
 * @return {{ data: Object|undefined, error: Object|undefined, loading: boolean, refresh: Function }} Jobs state.
 */
export const useSyncHealthJobs = ( enabled ) => {
	const { load, ...state } = useHealthEndpoint( 'sync-health/jobs', {
		enabled,
		interval: enabled ? POLL_INTERVAL_MS : 0,
	} );

	return { ...state, refresh: load };
};

/**
 * Fetches how many products sit in each sync journey segment.
 *
 * @param {boolean} [poll=false] Re-fetch on an interval, e.g. while syncing.
 * @return {{ data: Object|undefined, error: Object|undefined, loading: boolean, refresh: Function }} Journey state.
 */
export const useProductJourney = ( poll = false ) => {
	const { load, ...state } = useHealthEndpoint(
		'sync-health/product-journey',
		{ interval: poll ? POLL_INTERVAL_MS : 0 }
	);

	return { ...state, refresh: load };
};

/**
 * Ask the server to start fetching review results from Google. The server skips
 * it while a fetch is running or one finished in the last few minutes.
 *
 * Only call this from an explicit merchant action. Polling must never call it,
 * since each fetch pages through the whole catalog on the Merchant API.
 *
 * @return {Promise<{ started: boolean, reason: string, available_at: number|null }>} Outcome.
 */
export const requestReviewRefresh = () =>
	apiFetch( {
		path: `${ API_NAMESPACE }/sync-health/review-refresh`,
		method: 'POST',
	} );
