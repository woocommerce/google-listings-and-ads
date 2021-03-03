/**
 * External dependencies
 */
import { useEffect, useRef } from '@wordpress/element';
import { useDebouncedCallback } from 'use-debounce';

/**
 * Internal dependencies
 */
import { useAppDispatch } from '.~/data';
import useShippingTimes from '.~/hooks/useShippingTimes';
import useShippingRates from '.~/hooks/useShippingRates';

const wait = 500;

// TODO: review the following effect (specifically the useShippingRates and useShippingTimes)
// after reducer deepClone issue (https://github.com/woocommerce/google-listings-and-ads/issues/270)
// has been addressed.
const useAutoClearShippingEffect = ( value ) => {
	const { data: shippingRates } = useShippingRates();
	const { data: shippingTimes } = useShippingTimes();
	const { deleteShippingRate, deleteShippingTime } = useAppDispatch();

	const debouncedDelete = useDebouncedCallback( async ( rates, times ) => {
		rates.forEach( ( el ) => {
			deleteShippingRate( el.countryCode );
		} );

		times.forEach( ( el ) => {
			deleteShippingTime( el.countryCode );
		} );
	}, wait );

	const ref = useRef( null );

	useEffect( () => {
		// do not call on first render.
		if ( ref.current === null ) {
			ref.current = value;
			return;
		}

		debouncedDelete.callback( shippingRates, shippingTimes );
	}, [ debouncedDelete, shippingRates, shippingTimes, value ] );
};

export default useAutoClearShippingEffect;
