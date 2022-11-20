/**
 * External dependencies
 */
import { createHigherOrderComponent } from '@wordpress/compose';

/**
 * A higher-order component for wrapping the app shell on top of the GLA admin page.
 * Cross-page shared things could be handled here.
 *
 * @param {JSX.Element} Page Top-level admin page component to be wrapped by app shell.
 */
const withAdminPageShell = createHigherOrderComponent(
	( Page ) => ( props ) => {
		return (
			// gla-admin-page is for scoping particular styles to a GLA admin page.
			<div className="gla-admin-page">
				<Page { ...props } />
			</div>
		);
	},
	'withAdminPageShell'
);

export default withAdminPageShell;
