/**
 * External dependencies
 */
import { __, sprintf } from '@wordpress/i18n';
import { addQueryArgs } from '@wordpress/url';
import { useCallback, useEffect, useRef, useState } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { useAppDispatch } from '~/data';
import { API_NAMESPACE } from '~/data/constants';
import useApiFetchCallback from '~/hooks/useApiFetchCallback';
import useApiFetchEffect from '~/hooks/useApiFetchEffect';
import { timeAgo } from './utils';

const SUMMARY_PATH = `${ API_NAMESPACE }/sync-health`;
const JOURNEY_PATH = `${ API_NAMESPACE }/sync-health/product-journey`;
const REVIEW_REFRESH_PATH = `${ API_NAMESPACE }/sync-health/review-refresh`;
const JOBS_PATH = `${ API_NAMESPACE }/sync-health/jobs`;

/**
 * How often sync status is re-checked while review results are being fetched
 * from Google. The re-check reads the server's cached summary (30 seconds), so
 * it never calls Google and never rebuilds the summary more often than that.
 */
export const REVIEW_WATCH_INTERVAL_MS = 30 * 1000;

/**
 * Most re-checks per refresh (20 minutes at the interval above), so a fetch
 * that never finishes can't keep a tab polling forever.
 */
export const MAX_REVIEW_CHECKS = 40;

/**
 * Consecutive failed re-checks after which watching stops and the error shows.
 */
export const MAX_REVIEW_CHECK_FAILURES = 3;

/**
 * Review job states (`review.job_state`) in which the fetch won't finish on its
 * own, e.g. when WP-Cron isn't running, so there's nothing to wait for.
 */
const STOPPED_REVIEW_JOB_STATES = [ 'stalled', 'stuck', 'paused' ];

/**
 * Fetches per-job Action Scheduler counts once, on mount. Mount it only while
 * Advanced troubleshooting is open, so it costs nothing until someone looks.
 *
 * @return {Object} Fetch result with `data`, `error` and `loading`.
 */
export const useSyncHealthJobs = () => useApiFetchEffect( { path: JOBS_PATH } );

/**
 * Explains why a review refresh request didn't start a fetch from Google.
 *
 * @param {Object} [result] Response of the review-refresh request.
 * @return {string} A note for the merchant, or an empty string.
 */
const describeReviewRefresh = ( result ) => {
	if ( result?.reason === 'running' ) {
		return __(
			'Review results are already being fetched from Google.',
			'google-listings-and-ads'
		);
	}

	if ( result?.reason === 'recent' ) {
		return sprintf(
			// translators: %s: relative time, e.g. "in 3 minutes".
			__(
				'Review results were fetched recently. New results can be fetched %s.',
				'google-listings-and-ads'
			),
			timeAgo( result.available_at, '' )
		);
	}

	return '';
};

/**
 * Sync status summary and product status counts for the Product Feed page.
 *
 * Refreshes once automatically on mount (page visit), and again when `refresh`
 * is called. While Google review results are being fetched in the background
 * (`review.loading`), it re-checks sync status every 30 seconds until they are
 * in, then reloads the counts and the product table once. It stops early when
 * the review job is stalled, stuck or paused, after `MAX_REVIEW_CHECKS` checks,
 * or after `MAX_REVIEW_CHECK_FAILURES` failures in a row (showing the error).
 * Nothing re-fetches on a timer otherwise.
 *
 * A refresh asks Google for new review results (the server skips this when a
 * fetch is running or ran recently; it's only requested on page visit or an
 * explicit click, since each fetch pages through the whole catalog), then
 * re-checks sync status bypassing the server cache and reloads the counts.
 *
 * When refreshes overlap, only the latest one applies its results and ends
 * the refreshing state, so a slow earlier response can't overwrite newer data.
 * A re-check that is in flight when a refresh starts is dropped the same way.
 *
 * Notes about review results not being fetched ("already being fetched",
 * "fetched recently") only follow a manual refresh; the automatic one on page
 * visit only reports errors.
 *
 * @return {Object} `{ summary, counts, error, isRefreshing, refreshNote, refresh, reloadCounts }`. `refresh` returns a promise; pass it `{ manual: false }` for an automatic refresh. `reloadCounts` reloads only the product status counts, e.g. after products changed, without asking Google for anything.
 */
export const useProductFeedHealth = () => {
	const [ fetchSummary ] = useApiFetchCallback( { path: SUMMARY_PATH } );
	const [ fetchJourney ] = useApiFetchCallback( { path: JOURNEY_PATH } );
	const [ fetchReviewRefresh ] = useApiFetchCallback( {
		path: REVIEW_REFRESH_PATH,
		method: 'POST',
	} );

	const [ summary, setSummary ] = useState( {} );
	const [ journey, setJourney ] = useState( {} );
	const [ isRefreshing, setIsRefreshing ] = useState( true );
	const [ refreshNote, setRefreshNote ] = useState( '' );
	const [ watchTick, setWatchTick ] = useState( 0 );
	const watch = useRef( { checks: 0, failures: 0 } );
	const latestRequest = useRef( 0 );
	const { invalidateResolutionForStoreSelector } = useAppDispatch();

	const refresh = useCallback(
		( { manual = true } = {} ) => {
			const request = ++latestRequest.current;
			const isLatest = () => request === latestRequest.current;

			// Applies a response only if no newer refresh has started since.
			const applyTo = ( setter ) => [
				( data ) => isLatest() && setter( { data } ),
				( error ) =>
					isLatest() && setter( ( prev ) => ( { ...prev, error } ) ),
			];

			setIsRefreshing( true );
			watch.current = { checks: 0, failures: 0 };
			setRefreshNote( '' );

			return fetchReviewRefresh()
				.then(
					( result ) =>
						manual ? describeReviewRefresh( result ) : '',
					() =>
						__(
							'Could not fetch review results from Google.',
							'google-listings-and-ads'
						)
				)
				.then( ( note ) => {
					if ( isLatest() ) {
						setRefreshNote( note );
					}

					return Promise.all( [
						fetchSummary( {
							path: addQueryArgs( SUMMARY_PATH, { refresh: 1 } ),
						} ).then( ...applyTo( setSummary ) ),
						fetchJourney().then( ...applyTo( setJourney ) ),
					] );
				} )
				.finally( () => {
					if ( isLatest() ) {
						setIsRefreshing( false );
					}
				} );
		},
		[ fetchReviewRefresh, fetchSummary, fetchJourney ]
	);

	const reloadCounts = useCallback( () => {
		const request = latestRequest.current;
		const isLatest = () => request === latestRequest.current;

		return fetchJourney().then(
			( data ) => isLatest() && setJourney( { data } ),
			( error ) =>
				isLatest() && setJourney( ( prev ) => ( { ...prev, error } ) )
		);
	}, [ fetchJourney ] );

	const hasAutoRefreshed = useRef( false );
	useEffect( () => {
		if ( hasAutoRefreshed.current ) {
			return;
		}

		hasAutoRefreshed.current = true;
		refresh( { manual: false } );
	}, [ refresh ] );

	// Watch review results until Google's are in. Only while a fetch is running
	// and can still finish, within the check and failure limits, and not while a
	// refresh is in flight, since that re-checks status anyway.
	const review = summary.data?.pillars?.review;
	const isReviewWatchable =
		!! review?.loading &&
		! STOPPED_REVIEW_JOB_STATES.includes( review.job_state );
	useEffect( () => {
		if (
			! isReviewWatchable ||
			isRefreshing ||
			watch.current.checks >= MAX_REVIEW_CHECKS ||
			watch.current.failures >= MAX_REVIEW_CHECK_FAILURES
		) {
			return;
		}

		const timer = setTimeout( () => {
			// Skip checks while the tab is in the background.
			if ( document.hidden ) {
				setWatchTick( ( tick ) => tick + 1 );
				return;
			}

			const request = latestRequest.current;
			const isLatest = () => request === latestRequest.current;
			watch.current.checks++;

			fetchSummary()
				.then(
					( data ) => {
						if ( ! isLatest() ) {
							return;
						}

						watch.current.failures = 0;
						setSummary( { data } );

						// Review results are in: update the counts and the table.
						if ( ! data?.pillars?.review?.loading ) {
							invalidateResolutionForStoreSelector(
								'getMCProductFeed'
							);
							return fetchJourney().then(
								( counts ) =>
									isLatest() &&
									setJourney( { data: counts } ),
								() => {}
							);
						}
					},
					( error ) => {
						if ( ! isLatest() ) {
							return;
						}

						watch.current.failures++;
						if (
							watch.current.failures >= MAX_REVIEW_CHECK_FAILURES
						) {
							setSummary( ( prev ) => ( { ...prev, error } ) );
						}
					}
				)
				.finally( () => {
					// Schedules the next check, if review results are still loading.
					if ( isLatest() ) {
						setWatchTick( ( tick ) => tick + 1 );
					}
				} );
		}, REVIEW_WATCH_INTERVAL_MS );

		return () => clearTimeout( timer );
	}, [
		isReviewWatchable,
		isRefreshing,
		watchTick,
		fetchSummary,
		fetchJourney,
		invalidateResolutionForStoreSelector,
	] );

	return {
		summary: summary.data,
		counts: journey.data,
		error: summary.error || journey.error,
		isRefreshing,
		refreshNote,
		refresh,
		reloadCounts,
	};
};
