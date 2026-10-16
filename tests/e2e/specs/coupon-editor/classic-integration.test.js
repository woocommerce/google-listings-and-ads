/**
 * External dependencies
 */
import { expect, test } from '@playwright/test';

/**
 * Internal dependencies
 */
import * as api from '../../utils/api';
import { getClassicCouponEditorUtils } from '../../utils/coupon-editor';

test.use( { storageState: process.env.ADMINSTATE } );
test.describe.configure( { mode: 'serial' } );

test.describe( 'Classic Coupon Editor integration', () => {
	let page = null;
	let editorUtils = null;

	test.beforeAll( async ( { browser } ) => {
		page = await browser.newPage();
		editorUtils = getClassicCouponEditorUtils( page );

		await api.setOnboardedMerchant();
	} );

	test( 'Hide Channel Visibility metabox when Merchant Center is not connected', async () => {
		await api.clearOnboardedMerchant();
		await editorUtils.gotoAddCouponPage();

		await expect( editorUtils.getChannelVisibilityMetaBox() ).toBeHidden();

		// Resume the plugin to onboarded status so that the next test can carry over.
		await api.setOnboardedMerchant();
	} );

	test( 'Show Channel Visibility metabox when Merchant Center is connected', async () => {
		await api.setOnboardedMerchant();
		await editorUtils.gotoAddCouponPage();

		await expect( editorUtils.getChannelVisibilityMetaBox() ).toBeVisible();
	} );
} );
