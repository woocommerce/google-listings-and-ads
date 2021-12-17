/**
 * External dependencies
 */
import { useState, useEffect } from '@wordpress/element';

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
		setValue( ( state ) => ( { ...state, price: savedValue.price } ) );
	}, [ savedValue.price ] );

	const handleBlur = ( event, numberValue ) => {
		const { countries, currency, price } = value;

		if ( price === numberValue ) {
			return;
		}

		setValue( {
			countries,
			currency,
			price: numberValue,
		} );

		upsertShippingRates( {
			countryCodes: countries,
			currency,
			rate: numberValue,
		} );
	};

	return <CountriesPriceInput value={ value } onBlur={ handleBlur } />;
};

export default CountriesPriceInputForm;
