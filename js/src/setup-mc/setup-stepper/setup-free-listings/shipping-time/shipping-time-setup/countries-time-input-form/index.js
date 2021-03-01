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
import CountriesTimeInput from '../countries-time-input';

const CountriesTimeInputForm = ( props ) => {
	const { initialValue } = props;
	const [ value, setValue ] = useState( initialValue );
	const { upsertShippingTime } = useDispatch( STORE_KEY );
	const debouncedUpsertShippingTime = useDebouncedCallback( ( v ) => {
		const { countries, time } = v;
		countries.forEach( async ( el ) => {
			await upsertShippingTime( {
				countryCode: el,
				time,
			} );
		} );
	}, 500 );

	const handleChange = ( v ) => {
		setValue( v );
		debouncedUpsertShippingTime.callback( v );
	};

	return <CountriesTimeInput value={ value } onChange={ handleChange } />;
};

export default CountriesTimeInputForm;
