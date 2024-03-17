/**
 * External dependencies
 */
import { useEffect } from '@wordpress/element';

/**
 * Internal dependencies
 */
import useAppSelectDispatch from './useAppSelectDispatch';
import useCountdown from './useCountdown';
import useApiFetchCallback from '.~/hooks/useApiFetchCallback';

/**
 * Call `useAppSelectDispatch` with `"getMCProductStatistics"`.
 * If the background process is loading, start a countdown and invalidate the resolution to refresh the data.
 *
 * @return {useAppSelectDispatch} Result of useAppSelectDispatch.
 */
const useMCProductStatistics = () => {
	const { second, callCount, startCountdown } = useCountdown();
	const { data, hasFinishedResolution, invalidateResolution, ...rest } =
		useAppSelectDispatch( 'getMCProductStatistics' );

	const isLoading = hasFinishedResolution && data?.loading ? true : false;

	const [ refreshProductStats ] = useApiFetchCallback( {
		path: `/wc/gla/mc/product-statistics/refresh`,
		method: 'GET',
	} );

	const refreshStats = async () => {
		await refreshProductStats();
		invalidateResolution( 'getMCProductStatistics', [] );
	};

	useEffect( () => {
		if ( isLoading && second === 0 ) {
			startCountdown( 30 );
		}

		if ( isLoading && second === 0 && callCount > 0 ) {
			invalidateResolution( 'getMCProductStatistics', [] );
		}
		// Stop the countdown when the data is loaded.
		if ( data?.loading === false ) {
			startCountdown( 0 );
		}
	}, [
		second,
		callCount,
		isLoading,
		invalidateResolution,
		startCountdown,
		data,
	] );

	return {
		data,
		invalidateResolution,
		hasFinishedResolution,
		refreshStats,
		...rest,
	};
};

export default useMCProductStatistics;
