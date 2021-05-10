/**
 * External dependencies
 */
import { SETTINGS_STORE_NAME } from '@woocommerce/data';
import { useSelect } from '@wordpress/data';

/**
 * Get the store's currency object.
 *
 * Usage:
 *
 * ```js
 * const { code } = useStoreCurrency()
 * console.log( code ) // Print the store's currency code, e.g. 'USD'.
 * ```
 *
 * @return {Object} The store's currency object.
 */
const useStoreCurrency = () => {
	return useSelect( ( select ) => {
		return select( SETTINGS_STORE_NAME ).getSetting(
			'wc_admin',
			'currency'
		);
	}, [] );
};

export default useStoreCurrency;
