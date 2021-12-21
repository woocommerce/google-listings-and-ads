/**
 * External dependencies
 */
import { useState, useEffect } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { useAppDispatch } from '.~/data';
import CountriesTimeInput from '../countries-time-input';
import useIsEqualRefValue from '.~/hooks/useIsEqualRefValue';

const CountriesTimeInputForm = ( props ) => {
	const savedValue = useIsEqualRefValue( props.savedValue );
	const [ value, setValue ] = useState( savedValue );
	const { upsertShippingTimes } = useAppDispatch();

	useEffect( () => {
		setValue( savedValue );
	}, [ savedValue ] );

	const handleBlur = ( event, numberValue ) => {
		const { countries, time } = value;

		if ( time === numberValue ) {
			return;
		}

		setValue( {
			countries,
			time: numberValue,
		} );

		upsertShippingTimes( { countryCodes: countries, time: numberValue } );
	};

	return <CountriesTimeInput value={ value } onBlur={ handleBlur } />;
};

export default CountriesTimeInputForm;
