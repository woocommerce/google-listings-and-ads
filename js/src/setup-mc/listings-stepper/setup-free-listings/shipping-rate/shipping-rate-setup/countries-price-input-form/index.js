/**
 * External dependencies
 */
import { useState } from '@wordpress/element';
import { useDispatch } from '@wordpress/data';
import { useDebouncedCallback } from 'use-debounce';

/**
 * Internal dependencies
 */
import { STORE_KEY } from '.~/data';
import CountriesPriceInput from '../countries-price-input';

const CountriesPriceInputForm = ( props ) => {
	const { initialValue } = props;
	const [ value, setValue ] = useState( initialValue );
	const { upsertShippingRate } = useDispatch( STORE_KEY );
	const debouncedUpsertShippingRate = useDebouncedCallback( ( v ) => {
		const { countries, currency, price } = v;
		countries.forEach( async ( el ) => {
			await upsertShippingRate( {
				countryCode: el,
				currency,
				rate: price,
			} );
		} );
	}, 500 );

	const handleChange = ( v ) => {
		setValue( v );
		debouncedUpsertShippingRate.callback( v );
	};

	return <CountriesPriceInput value={ value } onChange={ handleChange } />;
};

export default CountriesPriceInputForm;
