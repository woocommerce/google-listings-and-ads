/**
 * External dependencies
 */
import { expect, test, Page } from '@playwright/test';

/**
 * Internal dependencies
 */
import * as api from '../../utils/api';
import { getClassicProductEditorUtils } from '../../utils/product-editor';

test.use( { storageState: process.env.ADMINSTATE } );
test.describe.configure( { mode: 'serial' } );

test.describe( 'Backorder availability date notice', () => {
	/**
	 * @type {Page}
	 */
	let page = null;
	let editorUtils = null;

	test.beforeAll( async ( { browser } ) => {
		page = await browser.newPage();
		editorUtils = getClassicProductEditorUtils( page );

		await api.setOnboardedMerchant();
	} );

	test.beforeEach( async () => {
		await editorUtils.gotoAddProductPage();
		await editorUtils.fillProductName();
		await editorUtils.clickInventoryTab();
	} );

	test( 'Notice is hidden when stock status is "In stock"', async () => {
		await editorUtils.getStockStatusInput( 'instock' ).check();

		await expect(
			editorUtils.getBackorderAvailabilityDateNotice()
		).toBeHidden();
	} );

	test( 'Notice is hidden when stock status is "Out of stock"', async () => {
		await editorUtils.getStockStatusInput( 'outofstock' ).check();

		await expect(
			editorUtils.getBackorderAvailabilityDateNotice()
		).toBeHidden();
	} );

	test( 'Notice is visible when stock status is "On backorder" and no availability date is set', async () => {
		await editorUtils.getStockStatusInput( 'onbackorder' ).check();

		await expect(
			editorUtils.getBackorderAvailabilityDateNotice()
		).toBeVisible();
	} );

	test( 'Notice is hidden when stock status is "On backorder" and an availability date is set', async () => {
		await editorUtils.getStockStatusInput( 'onbackorder' ).check();

		await expect(
			editorUtils.getBackorderAvailabilityDateNotice()
		).toBeVisible();

		// Set the availability date in the GLA tab.
		await editorUtils.clickPluginTab();
		const { dateInput } = editorUtils.getDateAndTimeInputs();
		await dateInput.fill( '2026-06-01' );
		await dateInput.press( 'Tab' );

		// Return to the Inventory tab and confirm the notice is gone.
		await editorUtils.clickInventoryTab();

		await expect(
			editorUtils.getBackorderAvailabilityDateNotice()
		).toBeHidden();
	} );

	test( 'Clicking the notice link switches to the Google for WooCommerce tab', async () => {
		await editorUtils.getStockStatusInput( 'onbackorder' ).check();

		await expect(
			editorUtils.getBackorderAvailabilityDateNotice()
		).toBeVisible();

		await editorUtils.getNoticeTabLink().click();

		await expect( editorUtils.getPluginTab() ).toHaveClass( /active/ );
	} );

	test.afterAll( async () => {
		await api.clearOnboardedMerchant();
		await page.close();
	} );
} );
