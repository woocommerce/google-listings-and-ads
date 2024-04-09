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

test.describe( 'Classic Product Editor integration', () => {
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

	test( 'Prompt to Get Started when not yet finished onboarding', async () => {
		await api.clearOnboardedMerchant();
		await editorUtils.gotoAddProductPage();

		await expect( editorUtils.getPluginTab() ).toBeHidden();

		const link = editorUtils
			.getChannelVisibilityMetaBox()
			.getByRole( 'link', { name: 'Complete setup' } );

		await expect( link ).toBeVisible();
		await expect( link ).toHaveAttribute(
			'href',
			/\/wp-admin\/admin\.php\?page=wc-admin&path=\/google\/start/
		);

		// Resume the plugin to onboarded status so that the next test can carry over.
		await api.setOnboardedMerchant();
	} );

	test( 'Hide plugin tab and meta box for unsupported product types', async () => {
		await editorUtils.gotoAddProductPage();

		const channelVisibilityMetaBox =
			editorUtils.getChannelVisibilityMetaBox();

		const pluginTab = editorUtils.getPluginTab();

		await expect( channelVisibilityMetaBox ).toBeVisible();
		await expect( pluginTab ).toBeVisible();

		await editorUtils.changeToGroupedProduct();
		await expect( channelVisibilityMetaBox ).toBeHidden();
		await expect( pluginTab ).toBeHidden();

		await editorUtils.changeToSimpleProduct();
		await expect( channelVisibilityMetaBox ).toBeVisible();
		await expect( pluginTab ).toBeVisible();

		await editorUtils.changeToExternalProduct();
		await expect( channelVisibilityMetaBox ).toBeHidden();
		await expect( pluginTab ).toBeHidden();

		await editorUtils.changeToVariableProduct();
		await expect( channelVisibilityMetaBox ).toBeVisible();
		await expect( pluginTab ).toBeVisible();
	} );

	test.afterAll( async () => {
		await api.clearOnboardedMerchant();
		await page.close();
	} );
} );
