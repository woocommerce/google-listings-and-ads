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

	test( 'Hide plugin tab for unsupported product types', async () => {
		await editorUtils.gotoAddProductPage();
		await editorUtils.fillProductName();

		const pluginTab = editorUtils.getPluginTab();

		await expect( pluginTab ).toHaveCount( 1 );

		await editorUtils.changeToGroupedProduct();
		await expect( pluginTab ).toHaveCount( 0 );

		await editorUtils.changeToStandardProduct();
		await expect( pluginTab ).toHaveCount( 1 );

		await editorUtils.changeToAffiliateProduct();
		await expect( pluginTab ).toHaveCount( 0 );
	} );

	test( 'Check existence and availability of fields for simple product', async () => {
		await editorUtils.gotoAddProductPage();
		await editorUtils.clickPluginTab();

		const panel = page.getByRole( 'tabpanel' );

		/*
		 * 2 Sections
		 */
		await expect( editorUtils.getChannelVisibilityHeading() ).toBeVisible();
		await expect( editorUtils.getProductAttributesHeading() ).toBeVisible();

		/*
		 * 9 <select>:
		 * - Channel visibility
		 * - Brand
		 *   - This is dynamically changed from woocommerce/product-text-field
		 *     to google-listings-and-ads/product-select-with-text-field.
		 *   - Code ref: `AttributesForm::init_input`
		 *   - E2E setup: 'woocommerce_gla_product_attribute_value_options_brand' filter in test-snippets.php
		 * - Condition
		 * - Gender
		 * - Size system
		 * - Size type
		 * - Age group
		 * - Is bundle
		 * - Adult content
		 */
		await expect( panel.getByRole( 'combobox' ) ).toHaveCount( 9 );

		/*
		 * 8 <input type="text|date|time">:
		 * - GTIN
		 * - MPN
		 * - Size
		 * - Color
		 * - Material
		 * - Pattern
		 * - Availability date
		 * - Availability time
		 */
		await expect( panel.getByRole( 'textbox' ) ).toHaveCount( 8 );

		/*
		 * 1 <input type="number">:
		 * - Multipack
		 */
		await expect( panel.getByRole( 'spinbutton' ) ).toHaveCount( 1 );

		/*
		 * 16 pairs of <label> and help icon buttons:
		 * - GTIN
		 * - MPN
		 * - Brand
		 * - Condition
		 * - Gender
		 * - Size
		 * - Size system
		 * - Size type
		 * - Color
		 * - Material
		 * - Pattern
		 * - Age group
		 * - Multipack
		 * - Is bundle
		 * - Availability date
		 *   Availability time has an invisible pair for layout alignment purpose
		 * - Adult content
		 */
		await expect(
			panel.locator( 'label' ).filter( { hasText: /\w+/ } )
		).toHaveCount( 16 );

		await expect(
			panel.getByRole( 'button', { name: 'help' } )
		).toHaveCount( 16 );

		/*
		 * Click the help icon to view the popover and its content for each type of field block
		 * with the tuple of block name and containing text.
		 */
		const blocks = [
			[
				'woocommerce/product-text-field',
				/global trade item number \(gtin\) for your item/i,
			],
			[
				'google-listings-and-ads/product-select-with-text-field',
				/brand of the product/i,
			],
			[
				'google-listings-and-ads/product-select-field',
				/condition or state of the item/i,
			],
			[
				'google-listings-and-ads/product-date-time-field',
				/date a preordered or backordered product becomes available for delivery/i,
			],
		];

		for ( const [ name, expectedText ] of blocks ) {
			await panel
				.locator( `[data-type="${ name }"]` )
				.first()
				.getByRole( 'button', { name: 'help' } )
				.click();

			const popover = page.locator( '.components-popover:visible' );
			await expect( popover ).toBeVisible();
			await expect( popover ).toContainText( expectedText );

			// To close popover
			await editorUtils.clickPluginTab();
			await expect( popover ).toHaveCount( 0 );
		}
	} );

	test( 'Check existence of fields for variable and variation products', async () => {
		await editorUtils.gotoEditVariableProductPage();
		await editorUtils.clickPluginTab();

		const panel = page.getByRole( 'tabpanel' );

		/*
		 * 2 Sections for variable product
		 */
		await expect( editorUtils.getChannelVisibilityHeading() ).toBeVisible();
		await expect( editorUtils.getProductAttributesHeading() ).toBeVisible();

		/*
		 * 3 <select> for variable product:
		 * - Channel visibility
		 * - Brand (dynamically changed to google-listings-and-ads/product-select-with-text-field)
		 * - Adult content
		 */
		await expect( panel.getByRole( 'combobox' ) ).toHaveCount( 3 );

		/*
		 * 0 <input type="text|date|time"> for variable product.
		 */
		await expect( panel.getByRole( 'textbox' ) ).toHaveCount( 0 );

		/*
		 * 0 <input type="number"> for variable product.
		 */
		await expect( panel.getByRole( 'spinbutton' ) ).toHaveCount( 0 );

		// ===============================
		// Go to edit the first variation.
		// ===============================
		await editorUtils.gotoEditVariationProductPage();
		await editorUtils.clickPluginTab();

		/*
		 * 1 Section for variation product
		 */
		await expect( editorUtils.getProductAttributesHeading() ).toBeVisible();
		await expect( editorUtils.getChannelVisibilityHeading() ).toHaveCount(
			0
		);

		/*
		 * 7 <select> for variation product:
		 * - Condition
		 * - Gender
		 * - Size system
		 * - Size type
		 * - Age group
		 * - Is bundle
		 * - Adult content
		 */
		await expect( panel.getByRole( 'combobox' ) ).toHaveCount( 7 );

		/*
		 * 8 <input type="text|date|time"> for variation product:
		 * - GTIN
		 * - MPN
		 * - Size
		 * - Color
		 * - Material
		 * - Pattern
		 * - Availability date
		 * - Availability time
		 */
		await expect( panel.getByRole( 'textbox' ) ).toHaveCount( 8 );

		/*
		 * 1 <input type="number"> for variation product:
		 * - Multipack
		 */
		await expect( panel.getByRole( 'spinbutton' ) ).toHaveCount( 1 );
	} );

	test( 'Channel visibility is disabled when hiding in product catalog', async () => {
		await editorUtils.gotoAddProductPage();
		await editorUtils.fillProductName();

		const { selection, help } = editorUtils.getChannelVisibility();

		await editorUtils.clickPluginTab();
		await expect( selection ).toBeEnabled();
		await expect( selection ).toHaveValue( 'sync-and-show' );
		await expect( help ).toHaveCount( 0 );

		await editorUtils.clickTab( 'Organization' );
		await page.getByLabel( 'Hide in product catalog' ).setChecked( true );
		await editorUtils.save();

		await editorUtils.clickPluginTab();
		await expect( selection ).toBeDisabled();
		await expect( selection ).toHaveValue( 'dont-sync-and-show' );
		await expect( help ).toBeVisible();
		await expect( help ).toContainText(
			'This product cannot be shown on any channel because it is hidden from your store catalog.'
		);

		await editorUtils.clickTab( 'Organization' );
		await page.getByLabel( 'Hide in product catalog' ).setChecked( false );
		await editorUtils.save();

		await editorUtils.clickPluginTab();
		await expect( selection ).toBeEnabled();
		await expect( help ).toHaveCount( 0 );
	} );

	test.afterAll( async () => {
		await editorUtils.toggleBlockFeature( page, false );
		await api.clearOnboardedMerchant();
		await page.close();
	} );
} );
