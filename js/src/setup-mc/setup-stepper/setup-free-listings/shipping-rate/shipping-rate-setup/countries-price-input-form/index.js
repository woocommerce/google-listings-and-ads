/**
 * External dependencies
 */
import { useState } from '@wordpress/element';

/**
 * Internal dependencies
 */
import CountriesPriceInput from '../countries-price-input';

const CountriesPriceInputForm = ( props ) => {
	const { initialValue } = props;
	const [ value, setValue ] = useState( initialValue );

	const handleChange = ( v ) => {
		setValue( v );
	};

	return <CountriesPriceInput value={ value } onChange={ handleChange } />;
};

export default CountriesPriceInputForm;
