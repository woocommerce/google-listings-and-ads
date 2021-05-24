/**
 * Internal dependencies
 */
import useAppSelectDispatch from '.~/hooks/useAppSelectDispatch';

/**
 * Call `useAppSelectDispatch` with `"getMCProductStatistics"`.
 *
 * @return {useAppSelectDispatch} Result of useAppSelectDispatch.
 */
const useMCProductStatistics = () => {
	return useAppSelectDispatch( 'getMCProductStatistics' );
};

export default useMCProductStatistics;
