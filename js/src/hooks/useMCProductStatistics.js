/**
 * Internal dependencies
 */
import useAppSelectDispatch from './useAppSelectDispatch';

const useMCProductStatistics = () => {
	return useAppSelectDispatch( 'getMCProductStatistics' );
};

export default useMCProductStatistics;
