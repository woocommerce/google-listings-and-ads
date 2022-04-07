/**
 * External dependencies
 */
import { useMemo } from '@wordpress/element';
import { numberFormat } from '@woocommerce/number';
import { getSetting } from '@woocommerce/settings'; // eslint-disable-line import/no-unresolved
// The above is an unpublished package, delivered with WC, we use Dependency Extraction Webpack Plugin to import it.
// See https://github.com/woocommerce/woocommerce-admin/issues/7781

/**
 * Get the store's currency object and a number formatter function.
 *
 * Usage:
 *
 * ```js
 * const { code, formatNumber } = useStoreCurrency()
 * console.log( code ) // Print the store's currency code, e.g. 'USD'.
 * console.log( formatNumber( 1234.567 ) ) // Format number according to currency config, e.g. '1,234.57'.
 * console.log( formatNumber( 1234.567, 1 ) ) // 1,234.6
 * console.log( formatNumber( 1234.567, 0 ) ) // 1,235
 * ```
 *
 * @return {Object} The store's currency object.
 */
const useStoreCurrency = () => {
	const currency = getSetting( 'currency' );

	return useMemo( () => {
		const formatNumber = ( value, precision = currency.precision ) => {
			const numberConfig = {
				...currency,
				precision,
			};
			return numberFormat( numberConfig, value );
		};

		return {
			...currency,
			formatNumber,
		};
	}, [ currency ] );
};

export default useStoreCurrency;
