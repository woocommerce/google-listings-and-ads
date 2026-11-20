/**
 * External dependencies
 */
import { getSetting } from '@woocommerce/settings';

/**
 * Get the base URL of WP admin. For example: 'https://example.com/wp-admin/'
 *
 * @return {string} The base URL of WP admin.
 */
const useAdminUrl = () => getSetting( 'adminUrl' );

export default useAdminUrl;
