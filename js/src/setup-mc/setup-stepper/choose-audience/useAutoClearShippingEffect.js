/**
 * External dependencies
 */
import { useEffect, useRef } from '@wordpress/element';
import { useDebouncedCallback } from 'use-debounce';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { useAppDispatch } from '.~/data';
import useShippingTimes from '.~/hooks/useShippingTimes';
import useSaveShippingRates from '.~/hooks/useSaveShippingRates';
import useDispatchCoreNotices from '.~/hooks/useDispatchCoreNotices';

const wait = 500;

const useAutoClearShippingEffect = ( location, countries ) => {
	const { data: shippingTimes } = useShippingTimes();
	const { saveShippingRates } = useSaveShippingRates();
	const { deleteShippingTimes } = useAppDispatch();
	const { createNotice } = useDispatchCoreNotices();

	const debouncedDelete = useDebouncedCallback( async () => {
		try {
			saveShippingRates( [] );

			if ( shippingTimes.length ) {
				const countryCodes = shippingTimes.map(
					( el ) => el.countryCode
				);
				deleteShippingTimes( countryCodes );
			}
		} catch ( error ) {
			createNotice(
				'error',
				__(
					'Something went wrong while trying to clear your shipping data. Please try again later.',
					'google-listings-and-ads'
				)
			);
		}
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
