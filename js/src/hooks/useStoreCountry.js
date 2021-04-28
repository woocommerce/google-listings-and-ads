/**
 * External dependencies
 */
import { useSelect } from '@wordpress/data';
import { SETTINGS_STORE_NAME } from '@woocommerce/data';

/**
 * Gets the store's country.
 *
 * @return {Object} `{ code: CountryCode, name: string }`
 */
export default function useStoreCountry() {
	return useSelect( ( select ) => {
		const { getSetting } = select( SETTINGS_STORE_NAME );
		const countryNames = getSetting( 'wc_admin', 'countries' );
		const general = getSetting( 'general', 'general' );
		const [ code ] = general.woocommerce_default_country.split( ':' );

		return { code, name: countryNames[ code ] };
	} );
}
