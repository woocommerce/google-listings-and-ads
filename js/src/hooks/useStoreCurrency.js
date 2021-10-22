/**
 * External dependencies
 */
import { getSetting } from '@woocommerce/settings'; // eslint-disable-line import/no-unresolved
// The above is an unpublished package, delivered with WC, we use Dependency Extraction Webpack Plugin to import it.
// See https://github.com/woocommerce/woocommerce-admin/issues/7781

/**
 * Get the store's currency object.
 *
 * Usage:
 *
 * ```js
 * const { code } = useStoreCurrency()
 * console.log( code ) // Print the store's currency code, e.g. 'USD'.
 * ```
 *
 * @return {Object} The store's currency object.
 */
const useStoreCurrency = () => getSetting( 'currency' );

export default useStoreCurrency;
