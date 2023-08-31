/**
 * External dependencies
 */
const { expect } = require( '@playwright/test' );

/**
 * Check the snackbar message.
 *
 * @param {import('@playwright/test').Page} page The current page.
 * @param {string} text The text to check.
 *
 * @return {Promise<void>}
 */
export async function checkSnackBarMessage( page, text ) {
	const snackbarMessage = page.locator( '.components-snackbar__content' );
	await snackbarMessage.waitFor();
	expect( snackbarMessage ).toHaveText( text );
}
