/**
 * External dependencies
 */
import { Page } from '@playwright/test';

/**
 * Internal dependencies
 */
import * as api from './api';

const REGEX_URL_PRODUCTS = /\/wc\/v3\/products\/\d+(\/variations\/\d+)?\?/;

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
	};

	return {
		...locators,
		...asyncActions,
	};
}
