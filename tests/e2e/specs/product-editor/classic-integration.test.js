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

	test( 'Check existence and availability of fields for simple product', async () => {
		await editorUtils.gotoAddProductPage();
		await editorUtils.clickPluginTab();

		const panel = editorUtils.getPluginPanel();

		/*
		 * 2 Headings
		 */
		await expect( editorUtils.getChannelVisibilityHeading() ).toBeVisible();
		await expect( editorUtils.getProductAttributesHeading() ).toBeVisible();

		/*
		 * 1 + 8 <select>:
		 * - Channel visibility
		 * - Brand
		 *   - This is dynamically changed from `Select` to `SelectWithTextInput`.
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
		await expect(
			editorUtils.getChannelVisibilityMetaBox().getByRole( 'combobox' )
		).toHaveCount( 1 );

		await expect( panel.getByRole( 'combobox' ) ).toHaveCount( 8 );

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
		 * - Availability date and time
		 * - Adult content
		 */
		await expect( panel.locator( 'label:visible' ) ).toHaveCount( 16 );
		await expect( panel.locator( '.woocommerce-help-tip' ) ).toHaveCount(
			16
		);

		/*
		 * Hover the help icon to view the tooltip and its content for each type of field
		 * with the tuple of field CSS name and containing text.
		 */
		const fields = [
			// Text
			[
				'.gla_attributes_gtin_field',
				/global trade item number \(gtin\) for your item/i,
			],

			// Select with text
			[
				'.gla_attributes_brand__gla_select_field',
				/brand of the product/i,
			],

			// Select
			[
				'.gla_attributes_condition_field',
				/condition or state of the item/i,
			],

			// Integer
			[
				'.gla_attributes_multipack_field',
				/number of identical products in a multipack/i,
			],

			// Boolean select
			[
				'.gla_attributes_isBundle_field',
				/whether the item is a bundle of products/i,
			],

			// Date and time
			[
				'.gla_attributes_availabilityDate_field',
				/date a preordered or backordered product becomes available for delivery/i,
			],
		];

		for ( const [ cssName, expectedText ] of fields ) {
			// Since the testing hovers to the next help icon very quickly
			// after the Tooltip disappears, which could randomly cause
			// the next hover not to be triggered, here use `toPass` to
			// reset the hovering attempt.
			await expect( async () => {
				const tooltip = page.locator( '#tiptip_holder' );

				await page.mouse.move( 0, 0 );
				await expect( tooltip ).toBeHidden();

				await panel
					.locator( `${ cssName } .woocommerce-help-tip` )
					.hover();

				await expect( tooltip ).toBeVisible( { timeout: 1000 } );
				await expect( tooltip ).toContainText( expectedText );
			} ).toPass();
		}
	} );

	test.afterAll( async () => {
		await api.clearOnboardedMerchant();
		await page.close();
	} );
} );
