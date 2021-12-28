/**
 * Internal dependencies
 */
import useAppSelectDispatch from './useAppSelectDispatch';

const useShippingRates = () => {
	return useAppSelectDispatch( 'getShippingRates' );
};

export default useShippingRates;
