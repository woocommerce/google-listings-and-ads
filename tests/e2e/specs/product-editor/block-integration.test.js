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
		await expect( link ).toHaveAttribute(
			'href',
			/\/wp-admin\/admin\.php\?page=wc-admin&path=\/google\/start/
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

	test( 'Change channel visibility and check its notice, status, and issues', async () => {
		await editorUtils.gotoAddProductPage();
		await editorUtils.fillProductName();
		await editorUtils.clickPluginTab();

		const issueTexts = [ 'Invalid price', 'Invalid GTIN' ];
		const { selection, notice, status, issues } =
			editorUtils.getChannelVisibility();

		// Save another value and revert it to atomically re-fetch
		// the current product with newly mocked data.
		async function refetchProductData() {
			await selection.selectOption( 'dont-sync-and-show' );
			await editorUtils.save();
			await selection.selectOption( 'sync-and-show' );
			await editorUtils.save();
		}

		/*
		 * Assert:
		 * - The value is saved to 'dont-sync-and-show'
		 * - The notice won't be shown even if there are issues
		 */
		await editorUtils.mockChannelVisibility( 'has-errors', issueTexts );
		await selection.selectOption( 'dont-sync-and-show' );
		await editorUtils.save();

		await expect( selection ).toHaveValue( 'dont-sync-and-show' );
		await expect( notice ).toHaveCount( 0 );
		await expect( status ).toHaveCount( 0 );
		await expect( issues ).toHaveCount( 0 );

		/*
		 * Assert:
		 * - The value is saved to 'sync-and-show'
		 * - The warning notice is shown with "Issues detected" status and issue contents
		 */
		await selection.selectOption( 'sync-and-show' );
		await editorUtils.save();

		await expect( selection ).toHaveValue( 'sync-and-show' );
		await expect( notice ).toBeVisible();
		await expect( notice ).toHaveClass( /(^| )is-warning( |$)/ );
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
		await refetchProductData();

		await expect( notice ).toBeVisible();
		await expect( notice ).toHaveClass( /(^| )is-info( |$)/ );
		await expect( status ).toHaveText( /^Not synced$/i );
		await expect( issues ).toHaveCount( 0 );

		/*
		 * Assert:
		 * - The info notice is shown with "Pending" status
		 */
		await editorUtils.mockChannelVisibility( 'pending' );
		await refetchProductData();

		await expect( notice ).toBeVisible();
		await expect( notice ).toHaveClass( /(^| )is-info( |$)/ );
		await expect( status ).toHaveText( /^Pending$/i );
		await expect( issues ).toHaveCount( 0 );

		/*
		 * Assert:
		 * - The notice won't be shown when the status is 'synced'
		 */
		await editorUtils.mockChannelVisibility( 'synced' );
		await refetchProductData();

		await expect( notice ).toHaveCount( 0 );
		await expect( status ).toHaveCount( 0 );
		await expect( issues ).toHaveCount( 0 );
	} );

	test( 'Custom block: Product select with text field', async () => {
		await editorUtils.gotoAddProductPage();
		await editorUtils.fillProductName();
		await editorUtils.clickPluginTab();

		const { selection, input } = editorUtils.getSelectWithTextField();

		/*
		 * Assert:
		 * - The "Default" option is selected and the text field is not shown
		 */
		await expect( selection ).toHaveValue( '' );
		await expect( input ).toHaveCount( 0 );

		/*
		 * Assert:
		 * - When the "From WooCommerce Brands" option is selected, the text field is not shown
		 * - After saving, the field value and state remain the same
		 */
		await selection.selectOption( 'e2e_test_woocommerce_brands' );
		await expect( selection ).toHaveValue( 'e2e_test_woocommerce_brands' );
		await expect( input ).toHaveCount( 0 );

		await editorUtils.save();
		await expect( selection ).toHaveValue( 'e2e_test_woocommerce_brands' );
		await expect( input ).toHaveCount( 0 );

		/*
		 * Assert:
		 * - When the "Enter a custom value" option is selected, the text field is shown
		 * - After saving, the field value and state remain the same
		 */
		await selection.selectOption( '_gla_custom_value' );
		await expect( selection ).toHaveValue( '_gla_custom_value' );
		await expect( input ).toBeVisible();

		await input.fill( 'Cute Cat' );
		await editorUtils.save();

		await expect( selection ).toHaveValue( '_gla_custom_value' );
		await expect( input ).toHaveValue( 'Cute Cat' );

		/*
		 * Assert:
		 * - When switching to another value and back, the entered value in the text field is kept
		 */
		await selection.selectOption( '' );
		await expect( input ).toHaveCount( 0 );
		await selection.selectOption( '_gla_custom_value' );
		await expect( selection ).toHaveValue( '_gla_custom_value' );
		await expect( input ).toHaveValue( 'Cute Cat' );
	} );

	test( 'Custom block: Product date and time fields', async () => {
		await editorUtils.gotoAddProductPage();
		await editorUtils.fillProductName();
		await editorUtils.clickPluginTab();

		const { dateInput, timeInput, dateHelp, timeHelp } =
			editorUtils.getDateAndTimeFields();

		/*
		 * Assert:
		 * - The default values are empty strings
		 */
		await expect( dateInput ).toHaveValue( '' );
		await expect( timeInput ).toHaveValue( '' );
		await expect( dateHelp ).toHaveCount( 0 );
		await expect( timeHelp ).toHaveCount( 0 );

		/*
		 * Assert:
		 * - After entering an invalid date or time value, the help shows an error message
		 * - Saving an invalid date or time value shows a failure notice
		 *
		 * Please note that this part needs to be run before successfully saving data,
		 * otherwise it cannot simulate the invalid value status on the date or time input
		 * via Playwright. It's probably because the React "Controlled Components" runs
		 * a little differently in Playwright.
		 */
		await dateInput.pressSequentially( '9' );

		await editorUtils.assertUnableSave();
		await expect( dateHelp ).toBeVisible();
		await expect( dateHelp ).toHaveText(
			await editorUtils.evaluateValidationMessage( dateInput )
		);

		await dateInput.clear();

		await timeInput.pressSequentially( '9' );

		await editorUtils.assertUnableSave();
		await expect( timeHelp ).toBeVisible();
		await expect( timeHelp ).toHaveText(
			await editorUtils.evaluateValidationMessage( timeInput )
		);

		await timeInput.clear();

		/*
		 * Assert:
		 * - When valid values are entered, the help messages are not shown
		 * - After saving, the field values remain the same
		 */
		await dateInput.fill( '2024-02-29' );
		await timeInput.fill( '18:30' );

		await expect( dateInput ).toHaveValue( '2024-02-29' );
		await expect( timeInput ).toHaveValue( '18:30' );
		await expect( dateHelp ).toHaveCount( 0 );
		await expect( timeHelp ).toHaveCount( 0 );

		await editorUtils.save();

		await expect( dateInput ).toHaveValue( '2024-02-29' );
		await expect( timeInput ).toHaveValue( '18:30' );
		await expect( dateHelp ).toHaveCount( 0 );
		await expect( timeHelp ).toHaveCount( 0 );

		/*
		 * Assert:
		 * - It can enter only the date and leave the time empty
		 */
		await timeInput.clear();
		await editorUtils.save();

		await expect( dateInput ).toHaveValue( '2024-02-29' );
		await expect( timeInput ).toHaveValue( '' );
		await expect( timeHelp ).toHaveCount( 0 );

		/*
		 * Assert:
		 * - It allows to save empty values
		 */
		await dateInput.clear();
		await editorUtils.save();

		await expect( dateInput ).toHaveValue( '' );
		await expect( timeInput ).toHaveValue( '' );
		await expect( dateHelp ).toHaveCount( 0 );
		await expect( timeHelp ).toHaveCount( 0 );
	} );

	test( 'Generic block transformation: Non-negative integer input field', async () => {
		await editorUtils.gotoAddProductPage();
		await editorUtils.fillProductName();
		await editorUtils.clickPluginTab();

		const { input, help } = editorUtils.getMultipackField();

		await expect( input ).toHaveValue( '' );
		await expect( help ).toHaveCount( 0 );

		await input.fill( '-1' );

		await editorUtils.assertUnableSave(
			'The minimum value of the field is 0'
		);
		await expect( help ).toBeVisible();
		await expect( help ).toHaveText(
			await editorUtils.evaluateValidationMessage( input )
		);

		await input.fill( '9.5' );

		await editorUtils.assertUnableSave();
		await expect( help ).toBeVisible();
		await expect( help ).toHaveText(
			await editorUtils.evaluateValidationMessage( input )
		);

		await input.fill( '0' );
		await editorUtils.save();

		await expect( input ).toHaveValue( '0' );
		await expect( help ).toHaveCount( 0 );

		await input.fill( '100' );
		await editorUtils.save();

		await expect( input ).toHaveValue( '100' );
		await expect( help ).toHaveCount( 0 );

		await input.clear();
		await editorUtils.save();

		await expect( input ).toHaveValue( '' );
		await expect( help ).toHaveCount( 0 );
	} );

	test( 'Save all product attributes to simple product', async () => {
		await editorUtils.gotoAddProductPage();
		await editorUtils.fillProductName();
		await editorUtils.clickPluginTab();

		const pairs =
			await editorUtils.getAvailableProductAttributesWithTestValues();

		expect( pairs ).toHaveLength( 17 );

		/*
		 * Assert:
		 * - All attributes are empty or default
		 * - Save all attributes
		 * - After saving, attribute values remain the same
		 */
		for ( const [ attribute, value ] of pairs ) {
			await expect( attribute ).toHaveValue( '' );
			await editorUtils.setAttributeValue( attribute, value );
		}

		await editorUtils.save();

		for ( const [ attribute, value ] of pairs ) {
			await expect( attribute ).toHaveValue( value );
		}

		/*
		 * Assert:
		 * - It allows to save all attributes to empty or default
		 */
		for ( const [ attribute ] of pairs ) {
			await editorUtils.setAttributeValue( attribute, '' );
		}

		await editorUtils.save();

		for ( const [ attribute ] of pairs ) {
			await expect( attribute ).toHaveValue( '' );
		}
	} );

	test( 'Save all product attributes to variation product', async () => {
		await editorUtils.gotoEditVariableProductPage();
		await editorUtils.gotoEditVariationProductPage();
		await editorUtils.clickPluginTab();

		const pairs =
			await editorUtils.getAvailableProductAttributesWithTestValues();

		expect( pairs ).toHaveLength( 16 );

		/*
		 * Assert:
		 * - All attributes are empty or default
		 * - Save all attributes
		 * - After saving, attribute values remain the same
		 */
		for ( const [ attribute, value ] of pairs ) {
			await expect( attribute ).toHaveValue( '' );
			await editorUtils.setAttributeValue( attribute, value );
		}

		await editorUtils.save();

		for ( const [ attribute, value ] of pairs ) {
			await expect( attribute ).toHaveValue( value );
		}

		/*
		 * Assert:
		 * - It allows to save all attributes to empty or default
		 */
		for ( const [ attribute ] of pairs ) {
			await editorUtils.setAttributeValue( attribute, '' );
		}

		await editorUtils.save();

		for ( const [ attribute ] of pairs ) {
			await expect( attribute ).toHaveValue( '' );
		}
	} );

	test.afterEach( async () => {
		await page.unrouteAll();
	} );

	test.afterAll( async () => {
		await editorUtils.toggleBlockFeature( false );
		await api.clearOnboardedMerchant();
		await page.close();
	} );
} );
