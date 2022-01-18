/**
 * External dependencies
 */
import { useEffect } from '@wordpress/element';

const cssClassNames = {
	isWPToolbarDisabled: 'is-wp-toolbar-disabled',
	woocommerceAdminFullScreen: 'woocommerce-admin-full-screen',
	appFullScreen: 'app-full-screen',
};

const FullScreen = ( props ) => {
	const { children } = props;

	useEffect( () => {
		/**
		 * For WC Navigation, it already has "is-wp-toolbar-disabled" applied,
		 * we check this so that we do not remove it in the cleanup function.
		 */
		const isWPToolbarDisabled = document.body.classList.contains(
			cssClassNames.isWPToolbarDisabled
		);

		if ( ! isWPToolbarDisabled ) {
			document.body.classList.add( cssClassNames.isWPToolbarDisabled );
		}

		document.body.classList.add(
			cssClassNames.woocommerceAdminFullScreen,
			cssClassNames.appFullScreen
		);

		return () => {
			if ( ! isWPToolbarDisabled ) {
				document.body.classList.remove(
					cssClassNames.isWPToolbarDisabled
				);
			}

			document.body.classList.remove(
				cssClassNames.woocommerceAdminFullScreen,
				cssClassNames.appFullScreen
			);
		};
	}, [] );

	return children;
};

export default FullScreen;
