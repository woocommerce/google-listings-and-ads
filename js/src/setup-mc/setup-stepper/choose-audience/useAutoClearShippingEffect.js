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
const useAutoClearShippingEffect = ( location, countries ) => {
	const { data: shippingRates } = useShippingRates();
	const { data: shippingTimes } = useShippingTimes();
	const { deleteShippingRate, deleteShippingTime } = useAppDispatch();

	const debouncedDelete = useDebouncedCallback( async () => {
		shippingRates.forEach( ( el ) => {
			deleteShippingRate( el.countryCode );
		} );

		shippingTimes.forEach( ( el ) => {
			deleteShippingTime( el.countryCode );
		} );
	}, wait );

	const locationRef = useRef( null );
	const countriesRef = useRef( null );

	useEffect( () => {
		if ( locationRef.current === null && countriesRef.current === null ) {
			locationRef.current = location;
			countriesRef.current = countries;
			return;
		}

		if (
			( locationRef.current === 'all' && location === 'all' ) ||
			( locationRef.current === 'selected' &&
				location === 'selected' &&
				countriesRef.current.length === countries.length )
		) {
			return;
		}

		locationRef.current = location;
		countriesRef.current = countries;

		debouncedDelete.callback();
	}, [ debouncedDelete, location, countries ] );
};

export default useAutoClearShippingEffect;
