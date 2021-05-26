/**
 * Internal dependencies
 */
import useStoreCurrency from '.~/hooks/useStoreCurrency';
import AppInputNumberControl from '.~/components/app-input-number-control';

/**
 * Renders an AppInputNumberControl that formats number value
 * into string displayed to the user,
 * and parses user input string into number.
 *
 * Formatting and parsing is based on the store's currency setting.
 *
 * The AppInputNumberControl will have the store's currency code as its suffix by default.
 * You can pass in custom suffix.
 *
 * @param {Object} props Props that will be passed to AppInputNumberControl.
 * @param {number} props.value Value of number type.
 * @param {(value: number) => void} props.onChange onChange callback with value of number type.
 * @param {any} props.suffix AppInputNumberControl's suffix.
 */
const AppInputPriceControl = ( props ) => {
	const currencySetting = useStoreCurrency();

	return (
		<AppInputNumberControl
			suffix={ currencySetting.code }
			numberSettings={ currencySetting }
			{ ...props }
		/>
	);
};

export default AppInputPriceControl;
