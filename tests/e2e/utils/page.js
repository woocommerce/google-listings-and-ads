/**
 * External dependencies
 */
const { expect } = require( '@playwright/test' );

/**
 * Get FAQ panel.
 *
 * @param {import('@playwright/test').Page} page The current page.
 *
 * @return {import('@playwright/test').Locator} Get FAQ panel.
 */
export function getFAQPanel( page ) {
	return page.locator( '.gla-faqs-panel' );
}

/**
 * Get FAQ panel title.
 *
 * @param {import('@playwright/test').Page} page The current page.
 *
 * @return {import('@playwright/test').Locator} Get FAQ panel title.
 */
export function getFAQPanelTitle( page ) {
	return getFAQPanel( page ).locator( '.components-panel__body-title' );
}

/**
 * Get FAQ panel row.
 *
 * @param {import('@playwright/test').Page} page The current page.
 *
 * @return {import('@playwright/test').Locator} Get FAQ panel row.
 */
export function getFAQPanelRow( page ) {
	return getFAQPanel( page ).locator( '.components-panel__row' );
}

/**
 * Check if FAQs are expandable.
 *
 * @param {import('@playwright/test').Page} page The current page.
 *
 * @return {Promise<void>}
 */
export async function checkFAQExpandable( page ) {
	const faqTitles = getFAQPanelTitle( page );
	const count = await faqTitles.count();

	if ( count === 1 ) {
		await faqTitles.click();
		const faqRow = getFAQPanelRow( page );
		await expect( faqRow ).toBeVisible();
	} else if ( count > 1 ) {
		for ( const faqTitle of await faqTitles.all() ) {
			await faqTitle.click();
		}
		const faqRows = getFAQPanelRow( page );
		await expect( faqRows ).toHaveCount( count );
		for ( const faqRow of await faqRows.all() ) {
			await expect( faqRow ).toBeVisible();
		}
	}
}

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
