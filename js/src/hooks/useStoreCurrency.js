/**
 * External dependencies
 */
import { SETTINGS_STORE_NAME } from '@woocommerce/data';
import { useSelect } from '@wordpress/data';

/**
 * Get a country key-name map object. Used for getting complete country name based on country code.
 *
 * Usage:
 *
 * ```js
 * const map = useCountryKeyNameMap()
 * console.log( map['AU'] ) // 'Australia'.
 * ```
 *
 * @return {Object} A map object where the key is the country code and the value is the country name.
 */
const useStoreCurrency = () => {
	return useSelect( ( select ) => {
		return select( SETTINGS_STORE_NAME ).getSetting(
			'wc_admin',
			'currency'
		);
	} );
};

export default useStoreCurrency;
