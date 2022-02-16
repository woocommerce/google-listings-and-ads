/**
 * External dependencies
 */
import { useEffect } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { useAppDispatch } from '.~/data';
import useIsEqualRefValue from '.~/hooks/useIsEqualRefValue';

const useAutoSaveShippingRatesEffect = ( shippingRates ) => {
	const { saveShippingRates } = useAppDispatch();
	const shippingRatesRefValue = useIsEqualRefValue( shippingRates );

	useEffect( () => {
		saveShippingRates( shippingRatesRefValue );
	}, [ saveShippingRates, shippingRatesRefValue ] );
};

export default useAutoSaveShippingRatesEffect;
