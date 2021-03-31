/**
 * External dependencies
 */
import { useSelect } from '@wordpress/data';
import { SETTINGS_STORE_NAME } from '@woocommerce/data';
import { numberFormat } from '@woocommerce/number';

/**
 * Gets a formatter function to format a number based on the store's currency settings.
 *
 * The formatted string does not contain currency code or symbol.
 */
const usePriceFormat = () => {
	const currencySetting = useSelect( ( select ) => {
		return select( SETTINGS_STORE_NAME ).getSetting(
			'wc_admin',
			'currency'
		);
	} );

	return ( number ) => {
		return numberFormat( currencySetting, number );
	};
};

export default usePriceFormat;
