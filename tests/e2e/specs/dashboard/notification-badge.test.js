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
		test( 'On Marketing menu by default', async ( { browser } ) => {
			page = await browser.newPage();

			await page.goto( '/wp-admin/index.php', {
				waitUntil: LOAD_STATE.DOM_CONTENT_LOADED,
			} );

			const badge = page
				.getByRole( 'link', { name: 'Marketing' } )
				.locator( 'span.update-plugins' )
				.filter( { hasText: '2' } );

			await expect( badge ).toBeVisible();
		} );

		test( 'In Google for WooCommerce sub-menu when Marketing menu is expanded', async () => {
			const badge = dashboardPage.page
				.getByRole( 'link', { name: 'Overview' } )
				.locator( 'span.update-plugins' )
				.filter( { hasText: '2' } );

			await expect( badge ).toBeVisible();
		} );

		test( 'On Marketing menu when switched to Analytics menu', async () => {
			const badge = dashboardPage.page
				.getByRole( 'link', { name: 'Overview' } )
				.locator( 'span.update-plugins' )
				.filter( { hasText: '2' } );

			await expect( badge ).toBeVisible();

			await dashboardPage.page
				.getByRole( 'link', { name: 'Analytics' } )
				.click();

			const badgeMoved = dashboardPage.page
				.getByRole( 'link', { name: 'Marketing' } )
				.locator( 'span.update-plugins' )
				.filter( { hasText: '2' } );

			await expect( badgeMoved ).toBeVisible();
		} );
	} );

	test.describe( 'Should not display', () => {
		test( 'On Marketing menu when there are no notifications', async ( {
			browser,
		} ) => {
			page = await browser.newPage();

			await page.goto( '/wp-admin/index.php?no_notifications=true', {
				waitUntil: LOAD_STATE.DOM_CONTENT_LOADED,
			} );

			const badge = page
				.getByRole( 'link', { name: 'Marketing' } )
				.locator( 'span.update-plugins' )
				.filter( { hasText: '2' } );

			await expect( badge ).not.toBeVisible();
		} );
	} );
} );
