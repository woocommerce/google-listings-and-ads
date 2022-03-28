/**
 * Internal dependencies
 */
import useIsEqualRefValue from '.~/hooks/useIsEqualRefValue';
import useDebouncedCallbackEffect from '.~/hooks/useDebouncedCallbackEffect';
import useSaveShippingRates from '.~/hooks/useSaveShippingRates';

/**
 * @typedef { import(".~/data/actions").ShippingRate } ShippingRate
 */

const useAutoSaveShippingRatesEffect = ( shippingRates ) => {
	const { saveShippingRates } = useSaveShippingRates();
	const shippingRatesRefValue = useIsEqualRefValue( shippingRates );

	/**
	 * A `saveShippingRates` callback that catches error and do nothing.
	 * We don't want to show error messages for this auto save feature,
	 * and want it to fail silently in the background.
	 *
	 * @param {Array<ShippingRate>} value Shipping rates.
	 */
	const saveShippingRatesCallback = async ( value ) => {
		try {
			await saveShippingRates( value );
		} catch ( error ) {}
	};

	useDebouncedCallbackEffect(
		shippingRatesRefValue,
		saveShippingRatesCallback
	);
};

export default useAutoSaveShippingRatesEffect;
