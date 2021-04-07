/**
 * External dependencies
 */
import { useSelect } from '@wordpress/data';
import { SETTINGS_STORE_NAME } from '@woocommerce/data';
import CurrencyFactory from '@woocommerce/currency';

/**
 * Gets the CurrencyFactory to format string based on the store's currency settings.
 */
const useCurrencyFactory = () => {
	const currencySetting = useSelect( ( select ) => {
		return select( SETTINGS_STORE_NAME ).getSetting(
			'wc_admin',
			'currency'
		);
	} );

	return CurrencyFactory( currencySetting );
};

export default useCurrencyFactory;
