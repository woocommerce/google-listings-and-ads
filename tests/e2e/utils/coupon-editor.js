/**
 * External dependencies
 */
import { Page } from '@playwright/test';

/**
 * Gets E2E test utils for facilitating tests for Classic Coupon Editor.
 *
 * @param {Page} page Playwright page object.
 */
export function getClassicCouponEditorUtils( page ) {
	const locators = {
		getChannelVisibilityMetaBox() {
			return page.locator( '#coupon_channel_visibility' );
		},

		getChannelVisibilityHeading() {
			return this.getChannelVisibilityMetaBox().getByRole( 'heading', {
				name: 'Channel visibility',
			} );
		},

		getChannelVisibility() {
			const metaBox = this.getChannelVisibilityMetaBox();

			return {
				selection: metaBox.getByRole( 'combobox' ),
				help: metaBox.locator( '.description' ),
				notice: metaBox.locator( '.sync-status' ),
			};
		},
	};

	const asyncActions = {
		async gotoAddCouponPage() {
			await page.goto( '/wp-admin/post-new.php?post_type=shop_coupon' );
			await this.waitForInteractionReady();
		},

		async gotoEditCouponPage( id ) {
			await page.goto( `/wp-admin/post.php?post=${ id }&action=edit` );
			await this.waitForInteractionReady();
		},

		async waitForInteractionReady() {
			await page.waitForLoadState( 'networkidle' );
			await page.waitForSelector( 'body.wp-admin' );
		},
	};

	return {
		...locators,
		...asyncActions,
	};
}
