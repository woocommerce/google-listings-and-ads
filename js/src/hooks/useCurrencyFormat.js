/**
 * External dependencies
 */
import { useMemo } from '@wordpress/element';
import { numberFormat } from '@woocommerce/number';

/**
 * Internal dependencies
 */
import useStoreCurrency from './useStoreCurrency';

/**
 * Gets a formatter function to format a number based on the store's currency settings.
 *
 * The formatted string does not contain currency code or symbol.
 *
 * @param {Object} [overrideSetting] Currency setting to override store's.
 */
const useCurrencyFormat = ( overrideSetting ) => {
	const currencySetting = useStoreCurrency();
	return useMemo( () => {
		const mergedSetting = {
			...currencySetting,
			...overrideSetting,
		};
		return ( number ) => numberFormat( mergedSetting, number );
	}, [ currencySetting, overrideSetting ] );
};

export default useCurrencyFormat;
