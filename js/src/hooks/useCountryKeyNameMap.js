/**
 * External dependencies
 */
import { decodeEntities } from '@wordpress/html-entities';
import { getSetting } from '@woocommerce/settings'; // eslint-disable-line import/no-unresolved
// The above is an unpublished package, delivered with WC, we use Dependency Extraction Webpack Plugin to import it.
// See https://github.com/woocommerce/woocommerce-admin/issues/7781

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
	const countries = {
		...getSetting( 'countries' ),
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
};

export default useCountryKeyNameMap;
