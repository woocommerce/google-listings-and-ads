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
 * Get country input search box container.
 *
 * @param {import('@playwright/test').Page} page The current page.
 *
 * @return {import('@playwright/test').Locator} Get country input search box container.
 */
export function getCountryInputSearchBoxContainer( page ) {
	return page.locator(
		'.woocommerce-tree-select-control > .components-base-control'
	);
}

/**
 * Get country input search box.
 *
 * @param {import('@playwright/test').Page} page The current page.
 *
 * @return {import('@playwright/test').Locator} Get country input search box.
 */
export function getCountryInputSearchBox( page ) {
	return getCountryInputSearchBoxContainer( page ).locator(
		'input[id*="woocommerce-tree-select-control"]'
	);
}

/**
 * Get tree item by country name.
 *
 * @param {import('@playwright/test').Page} page The current page.
 * @param {string} name
 *
 * @return {import('@playwright/test').Locator} Get tree item by country name.
 */
export function getTreeItemByCountryName( page, name = 'United States (US)' ) {
	return page.getByRole( 'treeitem', { name } );
}

/**
 * Select a country from the search box.
 *
 * @param {import('@playwright/test').Page} page The current page.
 * @param {string} name
 *
 * @return {Promise<void>}
 */
export async function selectCountryFromSearchBox(
	page,
	name = 'United States (US)'
) {
	const countrySearchBox = getCountryInputSearchBox( page );
	await countrySearchBox.fill( name );
	await getTreeItemByCountryName( page, name ).click();
	await countrySearchBox.press( 'Escape' );
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
