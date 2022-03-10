/**
 * Internal dependencies
 */
import useIsEqualRefValue from '.~/hooks/useIsEqualRefValue';
import useDebouncedCallbackEffect from '.~/hooks/useDebouncedCallbackEffect';
import useSaveShippingRates from '.~/hooks/useSaveShippingRates';

const useAutoSaveShippingRatesEffect = ( shippingRates ) => {
	const { saveShippingRates } = useSaveShippingRates();
	const shippingRatesRefValue = useIsEqualRefValue( shippingRates );

	useDebouncedCallbackEffect( shippingRatesRefValue, saveShippingRates );
};

export default useAutoSaveShippingRatesEffect;
