/**
 * External dependencies
 */
import { useSelect } from '@wordpress/data';
import { OPTIONS_STORE_NAME } from '@woocommerce/data';
import { getSetting } from '@woocommerce/settings';

/**
 * Gets the store's country.
 *
 * @return {Object} `{ code: CountryCode, name: string }`. Returns `{ code: null, name: null }` if the data are not yet resolved or could not be loaded.
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

		// A failed `wc-admin/options` request also finishes the resolution,
		// but leaves the option value undefined.
		if ( loaded && typeof defaultCountry === 'string' ) {
			const countryNames = getSetting( 'countries' );
			[ code ] = defaultCountry.split( ':' );
			name = countryNames[ code ];
		}

		return { code, name };
	}, [] );
}
