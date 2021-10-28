/**
 * External dependencies
 */
import { useSelect } from '@wordpress/data';
import { SETTINGS_STORE_NAME } from '@woocommerce/data';
import { getSetting as getWCSetting } from '@woocommerce/settings'; // eslint-disable-line import/no-unresolved
// The above is an unpublished package, delivered with WC, we use Dependency Extraction Webpack Plugin to import it.
// See https://github.com/woocommerce/woocommerce-admin/issues/7781

/**
 * Gets the store's country.
 *
 * @return {Object} `{ code: CountryCode, name: string }`
 */
export default function useStoreCountry() {
	return useSelect( ( select ) => {
		const { getSetting } = select( SETTINGS_STORE_NAME );
		const countryNames = getWCSetting( 'countries' );
		const general = getSetting( 'general', 'general' );
		const [ code ] = general.woocommerce_default_country.split( ':' );

		return { code, name: countryNames[ code ] };
	}, [] );
}
