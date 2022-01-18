/**
 * External dependencies
 */
import { useEffect } from '@wordpress/element';

// gla-* styles are defined in .~/css/shared/_woocommerce-admin.scss
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
		const classNames = classNameDict[ layoutName ].filter(
			( name ) => ! bodyClassList.contains( name )
		);

		bodyClassList.add( ...classNames );
		return () => {
			bodyClassList.remove( ...classNames );
		};
	}, [ layoutName ] );
}
