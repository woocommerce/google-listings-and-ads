/**
 * External dependencies
 */
import { expect, test } from '@playwright/test';

/**
 * Internal dependencies
 */
import { getClassicProductEditorUtils } from '../../utils/product-editor';
import MockRequests from '../../utils/mock-requests';
import {
	setNotificationsReady,
	clearOnboardedMerchant,
	setOnboardedMerchant,
} from '../../utils/api';

test.use( { storageState: process.env.ADMINSTATE } );

test.describe.configure( { mode: 'serial' } );

/**
 * @type {import('../../utils/mock-requests.js').default } mockRequests
 */
let mockRequests = null;

/**
 * @type {import('../../utils/product-editor.js').default } productEditor
 */
let productEditor = null;

/**
 * @type {import('@playwright/test').Page} page
 */
let page = null;

const actionSchedulerLink =
	'wp-admin/admin.php?page=wc-status&tab=action-scheduler&orderby=schedule&order=desc';

test.describe( 'Notifications Schedule', () => {
	test.beforeAll( async ( { browser } ) => {
		page = await browser.newPage();
		productEditor = getClassicProductEditorUtils( page );
		mockRequests = new MockRequests( page );
		await mockRequests.mockMCConnected( 1234, true, 'approved' );
		await setOnboardedMerchant();
		await Promise.all( [
			// Mock Jetpack as connected
			mockRequests.mockJetpackConnected(),

			// Mock google as connected.
			mockRequests.mockGoogleConnected(),
		] );
	} );

	test.afterAll( async () => {
		await clearOnboardedMerchant();
		await page.close();
	} );

	test( 'When access is granted and Product is created - Notifications are scheduled', async () => {
		await setNotificationsReady();
		// Create a new fresh product
		await productEditor.gotoAddProductPage();
		await productEditor.fillProductName();
		await productEditor.publish();
		const id = productEditor.getPostID();

		// Check the product.create job is scheduled.
		await page.goto( actionSchedulerLink );
		let row = page.getByRole( 'row', {
			name: `gla/jobs/notifications/products/process_item Run | Cancel Pending 0 => array ( 'item_id' => ${ id }, 'topic' => 'product.create'`,
		} );
		await expect( row ).toBeVisible();

		// Hover the row, so the Run button gets visible
		await row.hover( { force: true } );
		await row.getByRole( 'link' ).first().click();

		// Wait for the page to refresh and see that pending job is not there anymore.
		await page.waitForURL( actionSchedulerLink );
		await expect( row ).not.toBeVisible();

		// edit the product and set it as notified
		await productEditor.gotoEditProductPage( id );
		await productEditor.mockNotificationStatus( 'created' );
		await productEditor.fillProductName( 'updated product' );
		await productEditor.save();

		// Check if the product.update job is there.
		await page.goto( actionSchedulerLink );
		row = page.getByRole( 'row', {
			name: `gla/jobs/notifications/products/process_item Run | Cancel Pending 0 => array ( 'item_id' => ${ id }, 'topic' => 'product.update'`,
		} );
		await expect( row ).toBeVisible();
		await row.hover( { force: true } );
		await row.getByRole( 'link' ).first().click();
		await page.waitForURL( actionSchedulerLink );
		await expect( row ).not.toBeVisible();

		// change to external type. It will trigger the product.delete
		await productEditor.gotoEditProductPage( id );
		await productEditor.changeToExternalProduct();
		await productEditor.save();
		// Check if the product.delete job is there.
		await page.goto( actionSchedulerLink );
		row = page.getByRole( 'row', {
			name: `gla/jobs/notifications/products/process_item Run | Cancel Pending 0 => array ( 'item_id' => ${ id }, 'topic' => 'product.delete'`,
		} );
		await expect( row ).toBeVisible();
		await row.hover( { force: true } );
		await row.getByRole( 'link' ).first().click();
		await page.waitForURL( actionSchedulerLink );
		await expect( row ).not.toBeVisible();
	} );
} );
