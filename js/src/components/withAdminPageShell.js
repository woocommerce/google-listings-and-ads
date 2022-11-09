/**
 * External dependencies
 */
import { createHigherOrderComponent } from '@wordpress/compose';

/**
 * Internal dependencies
 */
import useLayout from '.~/hooks/useLayout';

/**
 * A higher-order component for wrapping the app shell on top of the GLA admin page.
 * Cross-page shared things could be handled here.
 *
 * @param {JSX.Element} Page Top-level admin page component to be wrapped by app shell.
 */
const withAdminPageShell = createHigherOrderComponent(
	( Page ) => ( props ) => {
		useLayout( 'admin-page' );
		return <Page { ...props } />;
	},
	'withAdminPageShell'
);

export default withAdminPageShell;
