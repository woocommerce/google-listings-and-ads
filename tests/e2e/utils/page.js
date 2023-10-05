/**
 * External dependencies
 */
const { expect } = require( '@playwright/test' );

/**
 * Internal dependencies
 */
import SetupBudgetPage from '../utils/pages/setup-ads/setup-budget';

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
 * Get country tags from input search box container.
 *
 * @param {import('@playwright/test').Page} page The current page.
 *
 * @return {import('@playwright/test').Locator} Get country tags from input search box container.
 */
export function getCountryTagsFromInputSearchBoxContainer( page ) {
	return getCountryInputSearchBoxContainer( page ).locator(
		'.woocommerce-tag'
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
 * Get tree select menu.
 *
 * @param {import('@playwright/test').Page} page The current page.
 *
 * @return {import('@playwright/test').Locator} Get tree select menu.
 */
export function getTreeSelectMenu( page ) {
	return page.locator( '.woocommerce-tree-select-control__main' );
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
 * Get remove country button by country name.
 *
 * @param {import('@playwright/test').Page} page The current page.
 * @param {string} name
 *
 * @return {import('@playwright/test').Locator} Get remove country button by country name.
 */
export function getRemoveCountryButtonByName(
	page,
	name = 'United States (US)'
) {
	return page.getByRole( 'button', { name: `Remove ${ name }` } );
}

/**
 * Remove a country from the search box.
 *
 * @param {import('@playwright/test').Page} page The current page.
 * @param {string} name
 *
 * @return {Promise<void>}
 */
export async function removeCountryFromSearchBox(
	page,
	name = 'United States (US)'
) {
	const removeButton = getRemoveCountryButtonByName( page, name );
	await removeButton.click();
}

/**
 * Fill a country in the search box.
 *
 * @param {import('@playwright/test').Page} page The current page.
 * @param {string} name
 *
 * @return {Promise<void>}
 */
export async function fillCountryInSearchBox(
	page,
	name = 'United States (US)'
) {
	const countrySearchBox = getCountryInputSearchBox( page );
	await countrySearchBox.fill( name );
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
	await fillCountryInSearchBox( page, name );
	const treeItem = getTreeItemByCountryName( page, name );
	await treeItem.click();
	const countrySearchBox = getCountryInputSearchBox( page );
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
 * Test if the Billing pop opens.
 *
 * @param {import('@playwright/test').Page} page The current page.
 */
export async function checkBillingAdsPopup( page ) {
	const popupPromise = page.waitForEvent( 'popup' );
	const setupBudgetPage = new SetupBudgetPage( page );
	await setupBudgetPage.clickSetUpBillingButton();
	const popup = await popupPromise;
	await popup.waitForLoadState();
	const popupTitle = await popup.title();
	const popupURL = popup.url();
	expect( popupTitle ).toBe(
		'Add a new payment method in Google Ads - Google Ads Help'
	);
	expect( popupURL ).toBe(
		'https://support.google.com/google-ads/answer/2375375'
	);
	await popup.close();
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
