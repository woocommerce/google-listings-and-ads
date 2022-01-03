/**
 * Internal dependencies
 */
import useAppSelectDispatch from './useAppSelectDispatch';

const useShippingTimes = () => {
	return useAppSelectDispatch( 'getShippingTimes' );
};

export default useShippingTimes;
