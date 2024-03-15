/**
 * External dependencies
 */
import { expect, test, Page } from '@playwright/test';

/**
 * Internal dependencies
 */
import * as api from '../../utils/api';
import { getProductBlockEditorUtils } from '../../utils/product-editor';

test.use( { storageState: process.env.ADMINSTATE } );
test.describe.configure( { mode: 'serial' } );

test.describe( 'Product Block Editor integration', () => {
	/**
	 * @type {Page}
	 */
	let page = null;
	let editorUtils = null;

	test.beforeAll( async ( { browser } ) => {
		page = await browser.newPage();
		editorUtils = getProductBlockEditorUtils( page );

		await editorUtils.toggleBlockFeature( true );
		await api.setOnboardedMerchant();
	} );

	test( 'Prompt to Get Started when not yet finished onboarding', async () => {
		await api.clearOnboardedMerchant();
		await editorUtils.gotoAddProductPage();
		await editorUtils.clickPluginTab();

		const link = page.getByRole( 'link', { name: 'Get Started' } );

		await expect( link ).toBeVisible();
		await link.click();

		await expect( page.getByRole( 'main' ) ).toContainText(
			/Reach millions of shoppers with product listings on Google/i
		);

		// Resume the plugin to onboarded status so that the next test can carry over.
		await api.setOnboardedMerchant();
	} );

	test.afterAll( async () => {
		await editorUtils.toggleBlockFeature( page, false );
		await api.clearOnboardedMerchant();
		await page.close();
	} );
} );
