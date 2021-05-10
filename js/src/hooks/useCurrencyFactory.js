/**
 * External dependencies
 */
import { useMemo } from '@wordpress/element';
import CurrencyFactory from '@woocommerce/currency';

/**
 * Internal dependencies
 */
import useStoreCurrency from './useStoreCurrency';

/**
 * Gets the CurrencyFactory to format string based on the store's currency settings.
 */
const useCurrencyFactory = () => {
	const currencySetting = useStoreCurrency();
	return useMemo( () => CurrencyFactory( currencySetting ), [
		currencySetting,
	] );
};

export default useCurrencyFactory;
