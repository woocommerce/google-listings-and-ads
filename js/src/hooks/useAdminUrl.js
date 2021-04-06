/**
 * External dependencies
 */
import { SETTINGS_STORE_NAME } from '@woocommerce/data';
import { useSelect } from '@wordpress/data';

/**
 * Get the base URL of WP admin. For example: 'https://example.com/wp-admin/'
 *
 * @return {string} The base URL of WP admin.
 */
const useAdminUrl = () => {
	return useSelect( ( select ) => {
		return select( SETTINGS_STORE_NAME ).getSetting(
			'wc_admin',
			'adminUrl'
		);
	} );
};

export default useAdminUrl;
