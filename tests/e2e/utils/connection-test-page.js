/**
 * Save test connection details on the Connection Test page.
 */
/* global page */
/**
 * External dependencies
 */
import {
	WP_ADMIN_DASHBOARD, // eslint-disable-line import/named
	isCurrentURL,
	loginUser,
	getPageError,
} from '@woocommerce/e2e-utils';

/**
 * Visits the connection test page; if user is not logged in, then it logs in first.
 */
export async function visitConnectionTestPage() {
	const connectionTestURL =
		WP_ADMIN_DASHBOARD + 'admin.php?page=connection-test-admin-page&e2e=1';

	await page.goto( connectionTestURL, {
		waitUntil: 'networkidle0',
	} );

	// Handle upgrade required screen
	if ( isCurrentURL( 'wp-admin/upgrade.php' ) ) {
		// Click update
		await page.click( '.button.button-large.button-primary' );
		// Click continue
		await page.click( '.button.button-large' );
	}

	if ( isCurrentURL( 'wp-login.php' ) ) {
		await loginUser();
		await visitConnectionTestPage();
	}

	const error = await getPageError();
	if ( error ) {
		throw new Error( 'Unexpected error in page content: ' + error );
	}
}

export async function saveConversionID() {
	await visitConnectionTestPage();
	await Promise.all( [
		expect( page ).toClick( '#e2e-test-conversion-id' ),
		page.waitForNavigation( { waitUntil: 'networkidle0' } ),
	] );
}

export async function clearConversionID() {
	await visitConnectionTestPage();
	await Promise.all( [
		expect( page ).toClick( '#e2e-clear-conversion-id' ),
		page.waitForNavigation( { waitUntil: 'networkidle0' } ),
	] );
}
