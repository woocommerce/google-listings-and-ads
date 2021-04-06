/**
 * Internal dependencies
 */
import useCurrencyFormat from '.~/hooks/useCurrencyFormat';
import useStoreCurrency from '.~/hooks/useStoreCurrency';
import AppInputControl from '.~/components/app-input-control';
import parseStringToNumber from './parseStringToNumber';

/**
 * Renders an AppInputControl that formats number value
 * into string displayed to the user,
 * and parses user input string into number.
 *
 * Formatting and parsing is based on the store's currency setting.
 *
 * The AppInputControl will have the store's currency code as its suffix by default.
 * You can pass in custom suffix.
 *
 * @param {Object} props Props that will be passed to AppInputControl.
 * @param {number} props.value Value of number type.
 * @param {(value: number) => void} props.onChange onChange callback with value of number type.
 * @param {any} props.suffix AppInputControl's suffix.
 */
const AppInputPriceControl = ( props ) => {
	const { value, onChange = () => {}, onBlur = () => {}, ...rest } = props;
	const currencySetting = useStoreCurrency();
	const priceFormat = useCurrencyFormat();

	/**
	 * Value to be displayed to the user in the UI.
	 */
	const stringValue = priceFormat( value );

	/**
	 * Parse the user input string value into number value and propagate up via onChange.
	 *
	 * @param {string} val User input string value.
	 */
	const handleChange = ( val ) => {
		const numberValue = parseStringToNumber( val, currencySetting );
		onChange( numberValue );
	};

	/**
	 * Blur event handler to parse stringValue into number,
	 * and propagate up to the form state via onChange.
	 *
	 * If users type in a value like "1.2345",
	 * the form state would be 1.2345 and the display would be formatted into "1.23",
	 * which can be problematic.
	 * So, on blur event, we parse the displayed string value into number
	 * and pass up to the form state via onChange
	 * to ensure that the number value in form state is consistent
	 * with the string value the user is seeing.
	 */
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
