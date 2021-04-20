/**
 * External dependencies
 */
import { useState, useEffect } from '@wordpress/element';
import { useDebouncedCallback } from 'use-debounce';

/**
 * Internal dependencies
 */
import { useAppDispatch } from '.~/data';
import CountriesTimeInput from '../countries-time-input';

const CountriesTimeInputForm = ( props ) => {
	const { savedValue } = props;
	const [ value, setValue ] = useState( savedValue );
	const { upsertShippingTimes, deleteShippingTimes } = useAppDispatch();

	useEffect( () => {
		setValue( savedValue );
	}, [ savedValue ] );

	const debouncedUpsertShippingTime = useDebouncedCallback( ( v ) => {
		const { countries: countryCodes, time } = v;
		if ( time ) {
			upsertShippingTimes( { countryCodes, time } );
		}
	}, 500 );

	const handleChange = ( v ) => {
		setValue( v );
		debouncedUpsertShippingTime.callback( v );
	};

	const handleBlur = () => {
		if ( value.time === '' ) {
			deleteShippingTimes( value.countries );
		}
	};

	return (
		<CountriesTimeInput
			value={ value }
			onChange={ handleChange }
			onBlur={ handleBlur }
		/>
	);
};

export default CountriesTimeInputForm;
