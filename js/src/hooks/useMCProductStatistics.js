/**
 * External dependencies
 */
import { useEffect } from '@wordpress/element';

/**
 * Internal dependencies
 */
import useAppSelectDispatch from './useAppSelectDispatch';
import useCountdown from './useCountdown';
import { useAppDispatch } from '.~/data';

/**
 * Call `useAppSelectDispatch` with `"getMCProductStatistics"`.
 * If the background process is loading, start a countdown and invalidate  the resolution for getMCProductStatistics and getMCProductFeed.
 *
 * @return {useAppSelectDispatch} Result of useAppSelectDispatch.
 */
const useMCProductStatistics = () => {
	const { second, callCount, startCountdown } = useCountdown();
	const { invalidateResolutionForStoreSelector } = useAppDispatch();
	const { data, hasFinishedResolution, invalidateResolution, ...rest } =
		useAppSelectDispatch( 'getMCProductStatistics' );

	// Weather the AS job is still processing the data.
	const isCalculatingStats =
		hasFinishedResolution && data?.loading ? true : false;
	const hasStats = hasFinishedResolution && data?.statistics ? true : false;

	useEffect( () => {
		// If the job is still processing the data, start the countdown.
		if ( isCalculatingStats && second === 0 ) {
			startCountdown( 15 );
			// If the job is still processing the data, invalidate the resolution/refetch the data to get the latest status.
			if ( callCount > 0 ) {
				invalidateResolution();
			}
		}

		// Stop the countdown when the data is loaded.
		if ( hasStats && callCount > 0 ) {
			startCountdown( 0 );
			// Refresh the product feed data so product statuses are updated.
			invalidateResolutionForStoreSelector( 'getMCProductFeed' );
		}
	}, [
		second,
		callCount,
		isCalculatingStats,
		hasStats,
		invalidateResolution,
		invalidateResolutionForStoreSelector,
		startCountdown,
	] );

	return {
		data,
		invalidateResolution,
		hasFinishedResolution,
		...rest,
	};
};

export default useMCProductStatistics;
