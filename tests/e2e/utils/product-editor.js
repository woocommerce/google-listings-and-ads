/**
 * External dependencies
 */
import { Page, expect } from '@playwright/test';

/**
 * Internal dependencies
 */
import * as api from './api';

const REGEX_URL_PRODUCTS = /\/wc\/v3\/products\/\d+(\/variations\/\d+)?\?/;

/**
 * Gets E2E test utils for facilitating writing tests for the classic product editor.
 *
 * @param {Page} page Playwright page object.
 */
export function getClassicProductEditorUtils( page ) {
	const locators = {
		getPluginTab() {
			return page.locator( '.gla_attributes_tab' );
		},

		getPluginPanel() {
			return page.locator( '#gla_attributes' );
		},

		getChannelVisibilityMetaBox() {
			return page.locator( '#channel_visibility' );
		},

		getChannelVisibilityHeading() {
			return this.getChannelVisibilityMetaBox().getByRole( 'heading', {
				name: 'Channel visibility',
			} );
		},

		getProductAttributesHeading() {
			return this.getPluginPanel().getByRole( 'heading', {
				name: 'Product attributes',
			} );
		},
	};

	const asyncActions = {
		gotoAddProductPage() {
			return page.goto( '/wp-admin/post-new.php?post_type=product' );
		},

		clickPluginTab() {
			return this.getPluginTab().click();
		},

		async changeProductType( type ) {
			await page.locator( '#product-type' ).selectOption( type );
		},

		changeToSimpleProduct() {
			return this.changeProductType( 'simple' );
		},

		changeToGroupedProduct() {
			return this.changeProductType( 'grouped' );
		},

		changeToExternalProduct() {
			return this.changeProductType( 'external' );
		},

		changeToVariableProduct() {
			return this.changeProductType( 'variable' );
		},
	};

	return {
		...locators,
		...asyncActions,
	};
}

/**
 * Gets E2E test utils for facilitating writing tests for Product Block Editor.
 *
 * @param {Page} page Playwright page object.
 */
export function getProductBlockEditorUtils( page ) {
	const locators = {
		getTab( tabName ) {
			return page
				.getByRole( 'tablist' )
				.getByRole( 'button', { name: tabName } );
		},

		getPluginTab() {
			return this.getTab( 'Google Listings & Ads' );
		},

		getChannelVisibilityHeading() {
			return page.getByRole( 'heading', { name: 'Channel visibility' } );
		},

		getProductAttributesHeading() {
			return page.getByRole( 'heading', { name: 'Product attributes' } );
		},

		getChannelVisibility() {
			const block = page.locator(
				'[data-type="google-listings-and-ads/product-channel-visibility"]'
			);
			return {
				selection: block.getByRole( 'combobox' ),
				help: block.locator( '.components-base-control__help' ),
				notice: block.locator( '.components-notice' ),
				status: block.locator( 'section p' ).first(),
				issues: block.getByRole( 'listitem' ),
			};
		},

		getSelectWithTextField() {
			const block = page
				.locator(
					'[data-type="google-listings-and-ads/product-select-with-text-field"]'
				)
				.first();
			return {
				selection: block.getByRole( 'combobox' ),
				input: block.getByRole( 'textbox' ),
			};
		},

		getDateAndTimeFields() {
			const block = page
				.locator(
					'[data-type="google-listings-and-ads/product-date-time-field"]'
				)
				.first();

			return {
				dateInput: block.locator( 'input[type=date]' ),
				timeInput: block.locator( 'input[type=time]' ),
				dateHelp: block
					.locator( '.components-input-control' )
					.nth( 0 )
					.locator( '.components-base-control__help' ),
				timeHelp: block
					.locator( '.components-input-control' )
					.nth( 1 )
					.locator( '.components-base-control__help' ),
			};
		},

		getMultipackField() {
			const block = page.locator(
				'[data-template-block-id="google-listings-and-ads-product-attributes-multipack"]'
			);
			return {
				input: block.locator( 'input' ),
				help: block.locator( '.components-base-control__help' ),
			};
		},

		getAllProductAttributes() {
			const { dateInput: availabilityDate, timeInput: availabilityTime } =
				this.getDateAndTimeFields();

			return {
				gtin: page.getByLabel( 'GTIN' ),
				mpn: page.getByLabel( 'MPN' ),
				brand: page.getByLabel( 'Brand' ),
				condition: page.getByLabel( 'Condition', { exact: true } ),
				gender: page.getByLabel( 'Gender' ),
				size: page.getByLabel( 'Size', { exact: true } ),
				sizeSystem: page.getByLabel( 'Size system' ),
				sizeType: page.getByLabel( 'Size type' ),
				color: page.getByLabel( 'Color' ),
				material: page.getByLabel( 'Material' ),
				pattern: page.getByLabel( 'Pattern' ),
				ageGroup: page.getByLabel( 'Age Group' ),
				multipack: page.getByLabel( 'Multipack' ),
				isBundle: page.getByLabel( 'Is Bundle' ),
				availabilityDate,
				availabilityTime,
				adultContent: page.getByLabel( 'Adult content' ),
			};
		},
	};

	const asyncActions = {
		async toggleBlockFeature( enable ) {
			await page.goto(
				'/wp-admin/admin.php?page=wc-settings&tab=advanced&section=features'
			);

			const checkbox = page.locator(
				'#woocommerce_feature_product_block_editor_enabled'
			);

			if ( enable ) {
				await checkbox.check();
			} else {
				await checkbox.uncheck();
			}

			await page.getByRole( 'button', { name: 'Save changes' } ).click();
			await page
				.getByText( 'Your settings have been saved.' )
				.isVisible();
		},

		gotoAddProductPage() {
			return page.goto(
				'/wp-admin/admin.php?page=wc-admin&path=%2Fadd-product'
			);
		},

		async gotoEditVariableProductPage() {
			const variableId = await api.createVariableProduct();
			await api.createVariationProducts( variableId );

			return page.goto(
				`/wp-admin/admin.php?page=wc-admin&path=%2Fproduct%2F${ variableId }`
			);
		},

		async gotoEditVariationProductPage( index = 0 ) {
			await this.clickTab( 'Variations' );

			return page
				.getByRole( 'tabpanel' )
				.getByRole( 'link', { name: 'Edit' } )
				.nth( index )
				.click();
		},

		clickSave() {
			return page
				.getByRole( 'region', { name: 'Header' } )
				.getByRole( 'button', { name: /^(Save draft|Update)$/ } )
				.click();
		},

		async save() {
			const observer = this.waitForSaved();
			await this.clickSave();
			await observer;
		},

		waitForSaved() {
			return page.waitForResponse( ( response ) => {
				return (
					REGEX_URL_PRODUCTS.test( response.url() ) &&
					response.ok() &&
					response.request().method() === 'POST'
				);
			} );
		},

		clickTab( tabName ) {
			return this.getTab( tabName ).click();
		},

		clickPluginTab() {
			return this.getPluginTab().click();
		},

		async changeProductType( type ) {
			await page
				.getByRole( 'button', { name: 'Change product type' } )
				.click();

			const observer = this.waitForSaved();
			await page.getByRole( 'menuitemradio', { name: type } ).click();

			// Avoid some random race conditions. Without this wait, subsequent UI operations
			// or assertions may fail due to re-rendering after data saving is completed or
			// continuously triggering data saving API requests.
			await observer;
		},

		changeToStandardProduct() {
			return this.changeProductType( /Standard product/ );
		},

		changeToGroupedProduct() {
			return this.changeProductType( /Grouped product/ );
		},

		changeToAffiliateProduct() {
			return this.changeProductType( /Affiliate product/ );
		},

		fillProductName( name = 'Cat Teaser' ) {
			// Timestamp for avoid duplicated SKU errors
			name += ` - ${ Date.now() }`;
			return page.getByRole( 'textbox', { name: 'name' } ).fill( name );
		},

		evaluateValidationMessage( input ) {
			return input.evaluate( ( element ) => element.validationMessage );
		},

		async setAttributeValue( locator, value ) {
			const tagName = await locator.evaluate(
				( element ) => element.tagName
			);

			if ( tagName === 'SELECT' ) {
				await locator.selectOption( value );
			} else {
				await locator.fill( value );
			}

			await expect( locator ).toHaveValue( value );
		},
	};

	const mocks = {
		async mockChannelVisibility( syncStatus, issues = [] ) {
			const key = 'google_listings_and_ads__channel_visibility';

			await page.route( REGEX_URL_PRODUCTS, async ( route ) => {
				// If the test case has reached the end but there is still any route awaiting fulfillment, it will cause "Request context disposed" errors and further lead to the test case being considered a failure. Therefore, here it catches and ignores errors.
				try {
					const response = await route.fetch();
					const product = await response.json();

					product[ key ].sync_status = syncStatus;
					product[ key ].issues = issues;

					await route.fulfill( { response, json: product } );
				} catch ( e ) {}
			} );
		},
	};

	const assertions = {
		async assertUnableSave() {
			await this.clickSave();

			const failureNotice = page
				.getByRole( 'button' )
				.filter( { hasText: 'Failed to save product' } );

			await expect( failureNotice ).toBeVisible();

			// Dismiss the notice.
			await failureNotice.click();
			await expect( failureNotice ).toHaveCount( 0 );
		},
	};

	return {
		...locators,
		...asyncActions,
		...mocks,
		...assertions,
	};
}
