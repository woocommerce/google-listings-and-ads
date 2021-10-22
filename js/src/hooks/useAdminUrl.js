/**
 * External dependencies
 */
import { getSetting } from '@woocommerce/settings'; // eslint-disable-line import/no-unresolved
// The above is an unpublished package, delivered with WC, we use Dependency Extraction Webpack Plugin to import it.
// See https://github.com/woocommerce/woocommerce-admin/issues/7781

/**
 * Get the base URL of WP admin. For example: 'https://example.com/wp-admin/'
 *
 * @return {string} The base URL of WP admin.
 */
const useAdminUrl = () => getSetting( 'adminUrl' );

export default useAdminUrl;
