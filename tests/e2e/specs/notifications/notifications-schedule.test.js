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
	clearNotificationsReady,
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

const getASJobRowName = ( itemId, notificationType, status = 'Pending' ) => {
	return `gla/jobs/notifications/products/process_item Run | Cancel ${ status } 0 => array ( 'item_id' => ${ itemId }, 'topic' => '${ notificationType }'`;
};

test.describe( 'Notifications Schedule', () => {
	test.beforeAll( async ( { browser } ) => {
		page = await browser.newPage();
		productEditor = getClassicProductEditorUtils( page );
		mockRequests = new MockRequests( page );
		await setOnboardedMerchant();
		await setNotificationsReady();
		await Promise.all( [
			// Mock Jetpack as connected
			mockRequests.mockJetpackConnected(),

			// Mock google as connected.
			mockRequests.mockGoogleConnected(),
			mockRequests.mockMCConnected( 1234, true, 'approved' ),
		] );
	} );

	test.afterAll( async () => {
		await clearOnboardedMerchant();
		await clearNotificationsReady();
		await page.close();
	} );

	test( 'When Notifications are ready. Notifications are scheduled', async () => {
		// Create a new fresh product
		await productEditor.gotoAddProductPage();
		await productEditor.fillProductName();
		await productEditor.publish();
		const id = productEditor.getPostID();

		// Check the product.create job is scheduled.
		await page.goto( actionSchedulerLink );
		let row = page.getByRole( 'row', {
			name: getASJobRowName( id, 'product.create' ),
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
			name: getASJobRowName( id, 'product.update' ),
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
			name: getASJobRowName( id, 'product.delete' ),
		} );
		await expect( row ).toBeVisible();
		await row.hover( { force: true } );
		await row.getByRole( 'link' ).first().click();
		await page.waitForURL( actionSchedulerLink );
		await expect( row ).not.toBeVisible();
	} );

	test( 'When Notifications are not ready. Notifications are not scheduled', async () => {
		await clearNotificationsReady();
		// Create a new fresh product
		await productEditor.gotoAddProductPage();
		await productEditor.fillProductName();
		await productEditor.publish();
		const id = productEditor.getPostID();

		// Check the product.create job is not scheduled.
		await page.goto( actionSchedulerLink );
		const row = page.getByRole( 'row', {
			name: getASJobRowName( id, 'product.create' ),
		} );
		await expect( row ).not.toBeVisible();
		await setNotificationsReady();
	} );

	test( 'When Merchant Center has not completed setup. Notifications are not scheduled', async () => {
		await clearOnboardedMerchant();
		// Create a new fresh product
		await productEditor.gotoAddProductPage();
		await productEditor.fillProductName();
		await productEditor.publish();
		const id = productEditor.getPostID();

		// Check the product.create job is not scheduled.
		await page.goto( actionSchedulerLink );
		const row = page.getByRole( 'row', {
			name: getASJobRowName( id, 'product.create' ),
		} );
		await expect( row ).not.toBeVisible();
		await setOnboardedMerchant();
	} );

	test( 'When product has not notified creation. Notifications for product.update and product.delete are not scheduled.', async () => {
		await productEditor.gotoAddProductPage();
		await productEditor.fillProductName();
		await productEditor.publish();
		const id = productEditor.getPostID();

		// Check the product.update job is not scheduled.
		await page.goto( actionSchedulerLink );
		let row = page.getByRole( 'row', {
			name: getASJobRowName( id, 'product.update' ),
		} );
		await expect( row ).not.toBeVisible();

		await productEditor.gotoEditProductPage( id );
		await productEditor.unpublish();

		// Check the product.delete job is not scheduled.
		await page.goto( actionSchedulerLink );
		row = page.getByRole( 'row', {
			name: getASJobRowName( id, 'product.delete' ),
		} );
		await expect( row ).not.toBeVisible();
	} );

	test( 'Unpublish a notified product schedules product.delete notification.', async () => {
		await productEditor.gotoAddProductPage();
		await productEditor.fillProductName();
		await productEditor.publish();
		const id = productEditor.getPostID();

		await productEditor.gotoEditProductPage( id );
		await productEditor.mockNotificationStatus( 'created' );
		await productEditor.unpublish();

		// Check the product.update job is scheduled.
		await page.goto( actionSchedulerLink );
		let row = page.getByRole( 'row', {
			name: getASJobRowName( id, 'product.delete' ),
		} );
		await expect( row ).toBeVisible();

		// Simulate that delete notification was successful and publish the product again.
		await productEditor.gotoEditProductPage( id );
		await productEditor.mockNotificationStatus( 'deleted' );
		await productEditor.publish();

		// Check the product.create job is scheduled again.
		await page.goto( actionSchedulerLink );
		row = page.getByRole( 'row', {
			name: getASJobRowName( id, 'product.create' ),
		} );
		await expect( row ).toBeVisible();
	} );

	test( 'Set as "Dont sync and show" a notified product schedules product.delete notification.', async () => {
		await productEditor.gotoAddProductPage();
		await productEditor.fillProductName();
		await productEditor.publish();
		const id = productEditor.getPostID();

		// Check the product.create job is scheduled.
		await page.goto( actionSchedulerLink );
		let row = page.getByRole( 'row', {
			name: getASJobRowName( id, 'product.create' ),
		} );
		await expect( row ).toBeVisible();
		// Hover the row, so the Run button gets visible
		await row.hover( { force: true } );
		await row.getByRole( 'link' ).first().click();

		await productEditor.gotoEditProductPage( id );
		await productEditor.mockNotificationStatus( 'created' );
		await productEditor.setChannelVisibility( "Don't Sync and show" );
		await productEditor.save();
		// Check the product.delete job is scheduled.
		await page.goto( actionSchedulerLink );
		row = page.getByRole( 'row', {
			name: getASJobRowName( id, 'product.delete' ),
		} );
		await expect( row ).toBeVisible();

		// Hover the row, so the Run button gets visible
		await row.hover( { force: true } );
		await row.getByRole( 'link' ).first().click();

		// Simulate that delete notification was successful and set as "Sync and show" the product again.
		await productEditor.gotoEditProductPage( id );
		await productEditor.mockNotificationStatus( 'deleted' );
		await productEditor.setChannelVisibility( 'Sync and show' );
		await productEditor.save();

		// Check the product.create job is scheduled.
		await page.goto( actionSchedulerLink );
		row = page.getByRole( 'row', {
			name: getASJobRowName( id, 'product.create' ),
		} );
		await expect( row ).toBeVisible();
	} );

	test( 'Set a notified product visibility as "Not Public" schedules product.delete notification.', async () => {
		await productEditor.gotoAddProductPage();
		await productEditor.fillProductName();
		await productEditor.publish();
		const id = productEditor.getPostID();

		// Check the product.create job is scheduled.
		await page.goto( actionSchedulerLink );
		let row = page.getByRole( 'row', {
			name: getASJobRowName( id, 'product.create' ),
		} );
		await expect( row ).toBeVisible();
		// Hover the row, so the Run button gets visible
		await row.hover( { force: true } );
		await row.getByRole( 'link' ).first().click();

		await productEditor.gotoEditProductPage( id );
		await productEditor.mockNotificationStatus( 'created' );
		await productEditor.setVisibility( 'Private' );
		await productEditor.save();
		// Check the product.delete job is scheduled.
		await page.goto( actionSchedulerLink );
		row = page.getByRole( 'row', {
			name: getASJobRowName( id, 'product.delete' ),
		} );
		await expect( row ).toBeVisible();

		// Hover the row, so the Run button gets visible
		await row.hover( { force: true } );
		await row.getByRole( 'link' ).first().click();

		// Simulate that delete notification was successful and set as "Visibility: Public" the product again.
		await productEditor.gotoEditProductPage( id );
		await productEditor.mockNotificationStatus( 'deleted' );
		await productEditor.setVisibility( 'Public' );
		await productEditor.save();

		// Check the product.create job is scheduled.
		await page.goto( actionSchedulerLink );
		row = page.getByRole( 'row', {
			name: getASJobRowName( id, 'product.create' ),
		} );
		await expect( row ).toBeVisible();
	} );
} );
