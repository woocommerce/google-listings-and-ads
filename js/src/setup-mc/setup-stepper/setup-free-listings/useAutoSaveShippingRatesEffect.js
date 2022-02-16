/**
 * Internal dependencies
 */
import { useAppDispatch } from '.~/data';
import useIsEqualRefValue from '.~/hooks/useIsEqualRefValue';
import useDebouncedCallbackEffect from '.~/hooks/useDebouncedCallbackEffect';

const useAutoSaveShippingRatesEffect = ( shippingRates ) => {
	const { saveShippingRates } = useAppDispatch();
	const shippingRatesRefValue = useIsEqualRefValue( shippingRates );

	useDebouncedCallbackEffect( shippingRatesRefValue, saveShippingRates );
};

export default useAutoSaveShippingRatesEffect;
