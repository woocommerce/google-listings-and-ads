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
		...rest
	} = props;
	const numberSettings = useStoreNumberSettings( settings );
	const numberFormat = useNumberFormat( settings );

	/**
	 * Value to be displayed to the user in the UI.
	 */
	const stringValue = numberFormat( value );

	/**
	 * Parse the user input string value into number value and propagate up via onChange.
	 *
	 * @param {string} val User input string value.
	 */
	const handleChange = ( val ) => {
		/**
		 * Formatted string value based on the passed in `numberSettings` props.
		 * e.g. If the `numberSettings` is `{ precision: 2 }` and users' input value string is `"1.2345"`,
		 * the input value `"1.2345"` would be formatted to `"1.23"`.
		 */
		const formattedString = numberFormat( val );

		/**
		 * Parse the formatted string into number.
		 * e.g. formatted string `"1.23"` would be parsed into number `1.23`.
		 */
		const numberValue = parseStringToNumber(
			formattedString,
			numberSettings
		);

		onChange( numberValue );
	};

	return (
		<AppInputControl
			value={ stringValue }
			onChange={ handleChange }
			{ ...rest }
		/>
	);
};

export default AppInputNumberControl;
