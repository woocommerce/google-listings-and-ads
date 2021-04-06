/**
 * External dependencies
 */
import { numberFormat } from '@woocommerce/number';

/**
 * Internal dependencies
 */
import useStoreCurrency from './useStoreCurrency';

/**
 * Gets a formatter function to format a number based on the store's currency settings.
 *
 * The formatted string does not contain currency code or symbol.
 */
const useCurrencyFormat = () => {
	const currencySetting = useStoreCurrency();

	return ( number ) => {
		return numberFormat( currencySetting, number );
	};
};

export default useCurrencyFormat;
