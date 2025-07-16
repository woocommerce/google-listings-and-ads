/**
 * Internal dependencies
 */
import { LOAD_STATE } from '../../utils/constants';

/**
 * External dependencies
 */
const { test, expect } = require( '@playwright/test' );

/**
 * Internal dependencies
 */
import { clearOnboardedMerchant, setOnboardedMerchant } from '../../utils/api';
import DashboardPage from '../../utils/pages/dashboard';

test.use( { storageState: process.env.ADMINSTATE } );

test.describe.configure( { mode: 'serial' } );

/**
 * @type {import('../../utils/pages/dashboard.js').default} dashboardPage
 */
let dashboardPage = null;

/**
 * @type {import('@playwright/test').Page} page
 */
let page = null;

test.use( { storageState: process.env.ADMINSTATE } );

test.describe( 'Notification Badge', () => {
	test.beforeAll( async ( { browser } ) => {
		page = await browser.newPage();
		dashboardPage = new DashboardPage( page );
		await setOnboardedMerchant();
		await dashboardPage.mockRequests();
		await dashboardPage.goto();
	} );

	test.afterAll( async () => {
		await clearOnboardedMerchant();
		await page.close();
	} );

	test.describe( 'Should display badge', () => {
		test( 'Show notification badge on Marketing menu by default', async ( {
			browser,
		} ) => {
			page = await browser.newPage();

			await page.goto( '/wp-admin/index.php', {
				waitUntil: LOAD_STATE.DOM_CONTENT_LOADED,
			} );

			const badge = page
				.getByRole( 'link', { name: 'Marketing' } )
				.locator( 'span.update-plugins' )
				.filter( { hasText: '1' } );

			expect( badge ).toBeVisible();
		} );

		test( 'Show notification badge on Google for WooCommerce when inside sub-menu', () => {
			const badge = dashboardPage.page
				.getByRole( 'link', { name: 'Google for WooCommerce' } )
				.locator( 'span.update-plugins' )
				.filter( { hasText: '1' } );

			expect( badge ).toBeVisible();
		} );

		test( 'Notification badge moves to the correct location on menu change', async () => {
			const badge = dashboardPage.page
				.getByRole( 'link', { name: 'Google for WooCommerce' } )
				.locator( 'span.update-plugins' )
				.filter( { hasText: '1' } );

			expect( badge ).toBeVisible();

			await dashboardPage.page
				.getByRole( 'link', { name: 'Analytics' } )
				.click();

			const badgeMoved = dashboardPage.page
				.getByRole( 'link', { name: 'Marketing' } )
				.locator( 'span.update-plugins' )
				.filter( { hasText: '1' } );

			expect( badgeMoved ).toBeVisible();
		} );
	} );

	test.describe( 'Should not display badge', () => {
		test( 'Does not show notification badge on Marketing menu when there are no notifications', async ( {
			browser,
		} ) => {
			page = await browser.newPage();

			await page.goto( '/wp-admin/index.php?no_notifications=true', {
				waitUntil: LOAD_STATE.DOM_CONTENT_LOADED,
			} );

			const badge = page
				.getByRole( 'link', { name: 'Marketing' } )
				.locator( 'span.update-plugins' )
				.filter( { hasText: '1' } );

			expect( badge ).not.toBeVisible();
		} );
	} );
} );
