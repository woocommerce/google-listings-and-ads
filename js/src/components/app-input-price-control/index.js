/**
 * Internal dependencies
 */
import usePriceFormat from '.~/hooks/usePriceFormat';
import useStoreCurrency from '.~/hooks/useStoreCurrency';
import AppInputControl from '.~/components/app-input-control';
import parseStringToNumber from './parseStringToNumber';

const AppInputPriceControl = ( props ) => {
	const { value, onChange = () => {}, onBlur = () => {}, ...rest } = props;
	const currencySetting = useStoreCurrency();
	const priceFormat = usePriceFormat();

	const stringValue = priceFormat( value );
	const handleChange = ( v ) => {
		const numberValue = parseStringToNumber( v, currencySetting );
		onChange( numberValue );
	};

	// if users type in a value like "1.2345",
	// the form state would be 1.2345 and the display would be formatted into "1.23",
	// which can be problematic.
	// so, on blur, we parse the displayed string value into number
	// and pass up to the form state via onChange
	// to ensure that the number value in form state is consistent
	// with the string value the user is seeing.
	const handleBlur = () => {
		const numberValue = parseStringToNumber( stringValue, currencySetting );

		if ( numberValue !== value ) {
			onChange( numberValue );
		}

		onBlur();
	};

	return (
		<AppInputControl
			suffix={ currencySetting.code }
			value={ stringValue }
			onChange={ handleChange }
			onBlur={ handleBlur }
			{ ...rest }
		/>
	);
};

export default AppInputPriceControl;
