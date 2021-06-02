/**
 * Internal dependencies
 */
import AppInputControl from '.~/components/app-input-control';
import parseStringToNumber from './parseStringToNumber';
import useStoreNumberSettings from '.~/hooks/useStoreNumberSettings';
import useNumberFormat from '.~/hooks/useNumberFormat';

/**
 * Renders an AppInputControl that formats number value
 * into string displayed to the user,
 * and parses user input string into number.
 *
 * By default, formatting and parsing is based on integer number, with `{ precision: 0 }`.
 * It also respects the `decimalSeparator` and `thousandSeparator` in the store's settings.
 * You can pass in custom settings via the `settings` props.
 *
 * @param {Object} props Props that will be passed to AppInputControl.
 * @param {number} props.value Value of number type.
 * @param {(value: number) => void} props.onChange onChange callback with value of number type.
 * @param {(event: Object, value: number) => void} props.onBlur onBlur callback with event and value of number type.
 * @param {any} props.suffix AppInputControl's suffix.
 * @param {Object} [props.numberSettings] Settings for formatting the number to string and parsing the string to number.
 * @param {number} [props.numberSettings.precision] Number of decimal places after the decimal separator. Default to 0
 * @param {string} [props.numberSettings.decimalSeparator] Decimal separator. Default to the decimal separator based on the store's settings.
 * @param {string} [props.numberSettings.thousandSeparator] Thousand separator. Default to the thousand separator based on the store's settings.
 */
const AppInputNumberControl = ( props ) => {
	const {
		value,
		numberSettings: settings,
		onChange = () => {},
		onBlur = () => {},
		...rest
	} = props;
	const numberSettings = useStoreNumberSettings( settings );
	const numberFormat = useNumberFormat( settings );

	/**
	 * Value to be displayed to the user in the UI.
	 */
	const stringValue = numberFormat( value );

	/**
	 * Get number value from string value, by passing the string value
	 * through `numberFormat` (which respects the `numberSettings`)
	 * and `parseStringToNumber` functions.
	 *
	 * e.g. If the `numberSettings` is `{ precision: 2 }` and the string value is `"1.2345"`,
	 * the return number value would be `1.23`.
	 *
	 * @param {string} v String value.
	 * @return {number} Number value.
	 */
	const getNumberFromString = ( v ) => {
		const formattedString = numberFormat( v );
		const numberValue = parseStringToNumber(
			formattedString,
			numberSettings
		);

		return numberValue;
	};

	/**
	 * Parse the user input string value into number value and propagate up via onChange.
	 *
	 * @param {string} val User input string value.
	 */
	const handleChange = ( val ) => {
		const numberValue = getNumberFromString( val );
		onChange( numberValue );
	};

	const handleBlur = ( e ) => {
		const numberValue = getNumberFromString( e.target.value );
		onBlur( e, numberValue );
	};

	return (
		<AppInputControl
			value={ stringValue }
			onChange={ handleChange }
			onBlur={ handleBlur }
			{ ...rest }
		/>
	);
};

export default AppInputNumberControl;
