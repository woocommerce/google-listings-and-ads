/**
 * External dependencies
 */
import { Page } from '@playwright/test';

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

		clickTab( tabName ) {
			return this.getTab( tabName ).click();
		},

		clickPluginTab() {
			return this.getPluginTab().click();
		},
	};

	return {
		...locators,
		...asyncActions,
	};
}
