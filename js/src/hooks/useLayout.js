/**
 * External dependencies
 */
import { useEffect } from '@wordpress/element';

// Styles of .gla-full-page and .gla-full-content are defined in .~/css/shared/_woocommerce-admin.scss
const classNameDict = {
	'admin-page': [ 'gla-admin-page' ],
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
 * @param {'admin-page'|'full-page'|'full-content'} layoutName Indicates which layout to be applied.
 *   - admin-page: Add a specific CSS class to the top of DOM tree for scoping particular styles to a GLA admin page.
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
		 * For WC Navigation, it already has classes applied, for example, "is-wp-toolbar-disabled".
		 * Here filter existing classes out to avoid them being removed in the cleanup function.
		 */
		const classNames = classNameDict[ layoutName ].filter(
			( name ) => ! bodyClassList.contains( name )
		);

		bodyClassList.add( ...classNames );
		return () => {
			bodyClassList.remove( ...classNames );
		};
	}, [ layoutName ] );
}
