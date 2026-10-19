/**
 * External dependencies
 */
import { useEffect } from '@wordpress/element';

// gla-* styles are defined in ~/css/shared/_woocommerce-admin.scss
const classNameDict = {
	'full-page': [
		'woocommerce-admin-full-screen',
		'is-wp-toolbar-disabled',
		'gla-full-page',
	],

	'full-content': [ 'gla-full-content' ],
};

/**
 * A hook to attach specified layout styles onto topper DOM nodes when mounting,
 * and unattach when unmounting.
 *
 * @param {'full-page'|'full-content'} layoutName Indicates which layout to be applied.
 *   - full-page: Display full page layout by hiding top bar, left sidebar and header.
 *   - full-content: Display full content layout by hiding header and StoreAlerts.
 */
export default function useLayout( layoutName ) {
	useEffect( () => {
		if ( ! classNameDict.hasOwnProperty( layoutName ) ) {
			return;
		}

		const bodyClassList = document.body.classList;
		/**
		 * Here filter potentially already applied classes out
		 * to avoid them being removed in the cleanup function.
		 */
		const classNames = classNameDict[ layoutName ].filter(
			( name ) => ! bodyClassList.contains( name )
		);

		bodyClassList.add( ...classNames );
		return () => {
			bodyClassList.remove( ...classNames );

			/**
			 * WooCommerce Admin sets `#wpbody`'s inline `margin-top` from
			 * `.woocommerce-layout__header`'s `clientHeight`. Both layouts above
			 * hide that header, so while one is applied the measurement is `0`
			 * and `#wpbody` loses its margin.
			 *
			 * Detaching the layout makes the header visible again, but core does
			 * not re-measure on its own, so the stale `margin-top: 0px` remains
			 * and the header overlaps the page content. Core does recalculate on
			 * window resize — which is why resizing by a single pixel has been
			 * the manual workaround — so dispatch one here and let core measure
			 * the now-visible header itself.
			 *
			 * Ref: https://github.com/woocommerce/woocommerce/blob/trunk/plugins/woocommerce/client/admin/client/header/shared.tsx
			 */
			window.dispatchEvent( new Event( 'resize' ) );
		};
	}, [ layoutName ] );
}
