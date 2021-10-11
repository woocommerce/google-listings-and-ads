/**
 * External dependencies
 */
import { SETTINGS_STORE_NAME } from '@woocommerce/data';
import { useSelect } from '@wordpress/data';
import { decodeEntities } from '@wordpress/html-entities';

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
const useCountryKeyNameMap = () => {
	return useSelect( ( select ) => {
		const { getSetting } = select( SETTINGS_STORE_NAME );
		const countries = {
			...getSetting(
				'wc_admin',
				'countries',
				// Use global settings as a fallback value,
				// to hotfix lack of countries in Settings Store in WC 5.8
				// See https://github.com/woocommerce/woocommerce-admin/issues/7781
				window.wcSettings.countries
			),
		};

		/*
		 * There are HTML entities in some country names.
		 * Examples:
		 * - Cura&ccedil;ao                             -> Curaçao
		 * - Saint Barth&eacute;lemy                    -> Saint Barthélemy
		 * - S&atilde;o Tom&eacute; and Pr&iacute;ncipe -> São Tomé and Príncipe
		 */
		Object.keys( countries ).forEach( ( key ) => {
			countries[ key ] = decodeEntities( countries[ key ] );
		} );

		return countries;
	}, [] );
};

export default useCountryKeyNameMap;
