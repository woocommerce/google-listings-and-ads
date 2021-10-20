/**
 * External dependencies
 */
import { useEffect } from '@wordpress/element';
/**
 * Internal dependencies
 */
import isWCNavigationEnabled from '.~/utils/isWCNavigationEnabled';

/**
 * Mocked result of parsing a page entry from {@link /js/src/index.js} by WC-admin's <Route>.
 *
 * @see https://github.com/woocommerce/woocommerce-admin/blob/release/2.7.1/client/layout/controller.js#L240-L244
 */
const dashboardPage = {
	match: { url: '/google/dashboard' },
	wpOpenMenu: 'toplevel_page_woocommerce-marketing',
};

/**
 * Effect that highlights the GLA Dashboard menu entry in the legacy WC menu.
 *
 * Should be called for every "root page" (`.~/pages/*`) that wants to open the GLA menu.
 *
 * The hook could be removed once WC Navigation will be always enabled,
 * or if we make the plugin fully use the routing feature of WC,
 * and let this be done by proper matching of URL matchers from {@link /js/src/index.js}
 *
 * @see window.wpNavMenuClassChange
 */
export default function useLegacyMenuEffect() {
	const navigationEnabled = isWCNavigationEnabled();
	return useEffect( () => {
		// Highlight the wp-admin dashboard menu
		if ( ! navigationEnabled ) {
			window.wpNavMenuClassChange(
				dashboardPage,
				dashboardPage.match.url
			);
		}
	}, [ navigationEnabled ] );
}
