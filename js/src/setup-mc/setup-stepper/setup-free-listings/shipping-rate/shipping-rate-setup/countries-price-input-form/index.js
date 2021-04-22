/**
 * External dependencies
 */
import { useState, useEffect } from '@wordpress/element';
import { useDebouncedCallback } from 'use-debounce';

/**
 * Internal dependencies
 */
import { useAppDispatch } from '.~/data';
import CountriesPriceInput from '../countries-price-input';

const CountriesPriceInputForm = ( props ) => {
	const { savedValue } = props;
	const [ value, setValue ] = useState( savedValue );
	const { upsertShippingRates } = useAppDispatch();

	useEffect( () => {
		setValue( savedValue );
	}, [ savedValue ] );

	const debouncedUpsertShippingRate = useDebouncedCallback( ( v ) => {
		const { countries, currency, price } = v;

		upsertShippingRates( {
			countryCodes: countries,
			currency,
			rate: price,
		} );
	}, 500 );

	const handleChange = ( v ) => {
		setValue( v );
		debouncedUpsertShippingRate.callback( v );
	};

	return <CountriesPriceInput value={ value } onChange={ handleChange } />;
};

export default CountriesPriceInputForm;
