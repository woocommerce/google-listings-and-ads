/**
 * External dependencies
 */
import { useSelect } from '@wordpress/data';
import { OPTIONS_STORE_NAME } from '@woocommerce/data';
import { getSetting } from '@woocommerce/settings'; // eslint-disable-line import/no-unresolved
// The above is an unpublished package, delivered with WC, we use Dependency Extraction Webpack Plugin to import it.
// See https://github.com/woocommerce/woocommerce-admin/issues/7781

/**
 * Gets the store's country.
 *
 * @return {Object} `{ code: CountryCode, name: string }`. Returns `{ code: null, name: null }` if the data are not yet resolved.
 */
export default function useStoreCountry() {
	return useSelect( ( select ) => {
		const optionsSelectors = select( OPTIONS_STORE_NAME );
		const selector = 'getOption';
		const args = [ 'woocommerce_default_country' ];
		const defaultCountry = optionsSelectors[ selector ]( ...args );
		const loaded = optionsSelectors.hasFinishedResolution( selector, args );

		let code = null;
		let name = null;

		if ( loaded ) {
			const countryNames = getSetting( 'countries' );
			[ code ] = defaultCountry.split( ':' );
			name = countryNames[ code ];
		}

		return { code, name };
	}, [] );
}
