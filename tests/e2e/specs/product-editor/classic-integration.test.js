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

	test( 'Check existence of fields for variable and variation products', async () => {
		await editorUtils.gotoEditVariableProductPage();
		await editorUtils.clickPluginTab();

		const panel = editorUtils.getPluginPanel();

		/*
		 * 2 Sections for variable product
		 */
		await expect( editorUtils.getChannelVisibilityHeading() ).toBeVisible();
		await expect( editorUtils.getProductAttributesHeading() ).toBeVisible();

		/*
		 * Description of where to edit attributes for variations
		 */
		await expect(
			panel.getByText(
				'As this is a variable product, you can add additional product attributes by going to Variations > Select one variation > Google Listings & Ads.'
			)
		).toBeVisible();

		/*
		 * 1 + 2 <select> for variable product:
		 * - Channel visibility
		 * - Brand (dynamically changed to `SelectWithTextInput`)
		 * - Adult content
		 */
		await expect(
			editorUtils.getChannelVisibilityMetaBox().getByRole( 'combobox' )
		).toHaveCount( 1 );
		await expect( panel.getByRole( 'combobox' ) ).toHaveCount( 2 );

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
		await editorUtils.gotoEditVariation();

		const variation = editorUtils.getPluginVariationMetaBox();

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
		await expect( variation.getByRole( 'combobox' ) ).toHaveCount( 7 );

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
		await expect( variation.getByRole( 'textbox' ) ).toHaveCount( 8 );

		/*
		 * 1 <input type="number"> for variation product:
		 * - Multipack
		 */
		await expect( variation.getByRole( 'spinbutton' ) ).toHaveCount( 1 );
	} );

	test( 'Channel visibility is disabled when hiding in product catalog', async () => {
		await editorUtils.gotoAddProductPage();
		await editorUtils.fillProductName();

		const { selection, help } = editorUtils.getChannelVisibility();
		const catalogVisibility = page.locator( '#catalog-visibility' );

		await expect( selection ).toBeEnabled();
		await expect( selection ).toHaveValue( 'sync-and-show' );
		await expect( help ).toBeHidden();

		await catalogVisibility.getByRole( 'link', { name: 'Edit' } ).click();
		await catalogVisibility.getByLabel( 'Search results only' ).click();
		await editorUtils.save();

		await expect( selection ).toBeDisabled();
		await expect( selection ).toHaveValue( 'dont-sync-and-show' );
		await expect( help ).toBeVisible();
		await expect( help ).toContainText(
			'This product cannot be shown on any channel because it is hidden from your store catalog.'
		);

		await catalogVisibility.getByRole( 'link', { name: 'Edit' } ).click();
		await catalogVisibility.getByLabel( 'Shop and search results' ).click();
		await editorUtils.save();

		await expect( selection ).toBeEnabled();
		await expect( help ).toBeHidden();
	} );

	test( 'Change channel visibility and check its notice, status, and issues', async () => {
		await editorUtils.gotoAddProductPage();
		await editorUtils.fillProductName();
		await editorUtils.save();

		const issueTexts = [ 'Invalid price', 'Invalid GTIN' ];
		const { selection, notice, status, issues } =
			editorUtils.getChannelVisibility();

		/*
		 * Assert:
		 * - The value is saved to 'dont-sync-and-show'
		 * - The notice won't be shown even if there are issues
		 */
		await editorUtils.mockChannelVisibility( 'has-errors', issueTexts );
		await selection.selectOption( 'dont-sync-and-show' );
		await editorUtils.save();

		await expect( selection ).toHaveValue( 'dont-sync-and-show' );
		await expect( notice ).toBeHidden();
		await expect( status ).toBeHidden();
		await expect( issues ).toBeHidden();

		/*
		 * Assert:
		 * - The value is saved to 'sync-and-show'
		 * - The warning notice is shown with "Issues detected" status and issue contents
		 */
		await selection.selectOption( 'sync-and-show' );
		await editorUtils.save();

		await expect( selection ).toHaveValue( 'sync-and-show' );
		await expect( notice ).toBeVisible();
		await expect( notice ).toHaveClass( /(^| )notice-warning( |$)/ );
		await expect( status ).toHaveText( /^Issues detected$/i );
		await expect( issues ).toHaveCount( issueTexts.length );

		for ( const [ index, issueText ] of issueTexts.entries() ) {
			await expect( issues.nth( index ) ).toHaveText( issueText );
		}

		/*
		 * Assert:
		 * - The info notice is shown with "Not synced" status
		 */
		await editorUtils.mockChannelVisibility( 'not-synced' );
		await page.reload();

		await expect( notice ).toBeVisible();
		await expect( notice ).not.toHaveClass( /(^| )notice-warning( |$)/ );
		await expect( status ).toHaveText( /^Not synced$/i );
		await expect( issues ).toBeHidden();

		/*
		 * Assert:
		 * - The info notice is shown with "Pending" status
		 */
		await editorUtils.mockChannelVisibility( 'pending' );
		await page.reload();

		await expect( notice ).toBeVisible();
		await expect( notice ).not.toHaveClass( /(^| )notice-warning( |$)/ );
		await expect( status ).toHaveText( /^Pending$/i );
		await expect( issues ).toBeHidden();

		/*
		 * Assert:
		 * - The notice won't be shown when the status is 'synced'
		 */
		await editorUtils.mockChannelVisibility( 'synced' );
		await page.reload();

		await expect( notice ).toBeHidden();
		await expect( status ).toBeHidden();
		await expect( issues ).toBeHidden();
	} );

	test( 'Custom input: Select with text input', async () => {
		await editorUtils.gotoAddProductPage();
		await editorUtils.fillProductName();
		await editorUtils.clickPluginTab();

		const { selection, input } = editorUtils.getSelectWithTextInput();

		/*
		 * Assert:
		 * - The "Default" option is selected and the text field is not shown
		 */
		await expect( selection ).toHaveValue( '' );
		await expect( input ).toBeHidden();

		/*
		 * Assert:
		 * - When the "From WooCommerce Brands" option is selected, the text field is not shown
		 * - After saving, the field value and state remain the same
		 */
		await selection.selectOption( 'e2e_test_woocommerce_brands' );
		await expect( input ).toBeHidden();

		await editorUtils.save();
		await editorUtils.clickPluginTab();

		await expect( selection ).toHaveValue( 'e2e_test_woocommerce_brands' );
		await expect( input ).toBeHidden();

		/*
		 * Assert:
		 * - When the "Enter a custom value" option is selected, the text field is shown
		 * - After saving, the field value and state remain the same
		 */
		await selection.selectOption( '_gla_custom_value' );
		await expect( input ).toBeVisible();

		await input.fill( 'Cute Cat' );
		await editorUtils.save();
		await editorUtils.clickPluginTab();

		await expect( selection ).toHaveValue( '_gla_custom_value' );
		await expect( input ).toHaveValue( 'Cute Cat' );

		/*
		 * Assert:
		 * - When switching to another value and back, the entered value in the text field is kept
		 */
		await selection.selectOption( '' );
		await expect( input ).toBeHidden();
		await selection.selectOption( '_gla_custom_value' );
		await expect( input ).toHaveValue( 'Cute Cat' );
	} );

	test( 'Custom input: Date and time inputs', async () => {
		await editorUtils.gotoAddProductPage();
		await editorUtils.fillProductName();
		await editorUtils.clickPluginTab();

		const { dateInput, timeInput } = editorUtils.getDateAndTimeInputs();

		/*
		 * Assert:
		 * - The default values are empty strings
		 */
		await expect( dateInput ).toHaveValue( '' );
		await expect( timeInput ).toHaveValue( '' );

		/*
		 * Assert:
		 * - After entering an invalid date or time value, its validity.valid is false
		 */
		await dateInput.pressSequentially( '9' );
		await editorUtils.clickSave();

		expect( await editorUtils.evaluateValidity( dateInput ) ).toBe( false );

		await dateInput.clear();

		await timeInput.pressSequentially( '9' );
		await editorUtils.clickSave();

		expect( await editorUtils.evaluateValidity( timeInput ) ).toBe( false );

		await timeInput.clear();

		/*
		 * Assert:
		 * - When valid values are entered, it allows to save
		 * - After saving, the field values remain the same
		 */
		await dateInput.fill( '2024-02-29' );
		await timeInput.fill( '18:30' );
		await editorUtils.save();
		await editorUtils.clickPluginTab();

		await expect( dateInput ).toHaveValue( '2024-02-29' );
		await expect( timeInput ).toHaveValue( '18:30' );

		/*
		 * Assert:
		 * - It can enter only the date and leave the time empty
		 * - After saving, the empty time value is considered as 00:00
		 */
		await timeInput.clear();
		await editorUtils.save();
		await editorUtils.clickPluginTab();

		await expect( dateInput ).toHaveValue( '2024-02-29' );
		await expect( timeInput ).toHaveValue( '00:00' );

		/*
		 * Assert:
		 * - It allows to save empty values
		 */
		await dateInput.clear();
		await editorUtils.save();
		await editorUtils.clickPluginTab();

		await expect( dateInput ).toHaveValue( '' );
		await expect( timeInput ).toHaveValue( '' );
	} );

	test.afterAll( async () => {
		await api.clearOnboardedMerchant();
		await page.close();
	} );
} );
