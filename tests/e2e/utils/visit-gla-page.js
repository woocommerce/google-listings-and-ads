/**
 * A GLA specific version of `@wordpress/e2e-utils.visitAdminPage`,
 * as the original is not quite usefull for us.
 * See https://github.com/WordPress/gutenberg/issues/34044
 */
/* global page */
/**
 * External dependencies
 */
import {
	WP_ADMIN_WC_HOME, // eslint-disable-line import/named
	isCurrentURL,
	loginUser,
	getPageError,
} from '@woocommerce/e2e-utils';

/**
 * Internal dependencies
 */
import { createURL } from './create-url';

/**
 * Visits admin page; if user is not logged in then it logging in it first, then visits admin page.
 * If no specific page or path is given in the query, visits `WP_ADMIN_WC_HOME`.
 *
 * @param {string|Object|Array} [query]     	Search query params, in the format acceptable by `SearchParams`.
 * @param {string} 				[query.path]  	Path to the page.
 * @return {string} Full URL of the destination.
 */
export async function visitGLAPage( query ) {
	const fullURL = createURL( WP_ADMIN_WC_HOME, query );
	await page.goto( fullURL );

	// Handle upgrade required screen
	if ( isCurrentURL( 'wp-admin/upgrade.php' ) ) {
		// Click update
		await page.click( '.button.button-large.button-primary' );
		// Click continue
		await page.click( '.button.button-large' );
	}

	if ( isCurrentURL( 'wp-login.php' ) ) {
		await loginUser();
		await visitGLAPage( query );
	}

	const error = await getPageError();
	if ( error ) {
		throw new Error( 'Unexpected error in page content: ' + error );
	}
	return fullURL;
}
