/**
 * Internal dependencies
 */
import useAppSelectDispatch from './useAppSelectDispatch';

const useMCIssues = ( query ) => {
	return useAppSelectDispatch( 'getMCIssues', [ query ] );
};

export default useMCIssues;
