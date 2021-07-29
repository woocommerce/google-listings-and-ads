/**
 * External dependencies
 */
import { SETTINGS_STORE_NAME } from '@woocommerce/data';
import { useSelect } from '@wordpress/data';

/*
 * Examples:
 * - Cura&ccedil;ao                             -> Curaçao
 * - Saint Barth&eacute;lemy                    -> Saint Barthélemy
 * - S&atilde;o Tom&eacute; and Pr&iacute;ncipe -> São Tomé and Príncipe
 */
function replaceHtmlEntities( countryName ) {
	const el = document.createElement( 'span' );
	el.innerHTML = countryName;
	return el.textContent;
}

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
			...getSetting( 'wc_admin', 'countries' ),
		};

		Object.keys( countries ).forEach( ( key ) => {
			const name = countries[ key ];
			if ( /&[^;]+;/.test( name ) ) {
				countries[ key ] = replaceHtmlEntities( name );
			}
		} );

		return countries;
	}, [] );
};

export default useCountryKeyNameMap;
