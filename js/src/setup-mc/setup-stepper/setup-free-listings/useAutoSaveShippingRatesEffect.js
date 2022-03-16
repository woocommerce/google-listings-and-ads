/**
 * External dependencies
 */
import { useCallback } from '@wordpress/element';

/**
 * Internal dependencies
 */
import useIsEqualRefValue from '.~/hooks/useIsEqualRefValue';
import useDebouncedCallbackEffect from '.~/hooks/useDebouncedCallbackEffect';
import useSaveShippingRates from '.~/hooks/useSaveShippingRates';

const useAutoSaveShippingRatesEffect = ( shippingRates ) => {
	const { saveShippingRates } = useSaveShippingRates();
	const shippingRatesRefValue = useIsEqualRefValue( shippingRates );

	/**
	 * A `saveShippingRates` callback that catches error and do nothing.
	 * We don't want to show error messages for this auto save feature,
	 * and want it to fail silently in the background.
	 */
	const saveShippingRatesCallback = useCallback(
		async ( value ) => {
			try {
				await saveShippingRates( value );
			} catch ( error ) {}
		},
		[ saveShippingRates ]
	);

	useDebouncedCallbackEffect(
		shippingRatesRefValue,
		saveShippingRatesCallback
	);
};

export default useAutoSaveShippingRatesEffect;
