/**
 * Internal dependencies
 */
import useAppSelectDispatch from './useAppSelectDispatch';

const useTargetAudience = () => {
	return useAppSelectDispatch( 'getTargetAudience' );
};

export default useTargetAudience;
