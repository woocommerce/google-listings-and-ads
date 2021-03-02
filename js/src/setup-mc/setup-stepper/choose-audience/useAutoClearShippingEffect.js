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
