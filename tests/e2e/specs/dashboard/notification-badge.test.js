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
import {
	clearGCRNotificationsDismissed,
	clearOnboardedMerchant,
	getNotifications,
	setGCRNotificationsDismissed,
	setOnboardedMerchant,
} from '../../utils/api';
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

/**
 * Expected badge count: the real notification system's current count, plus
 * the test-only `++` this environment's `google_for_woocommerce_admin_menu_notification_count`
 * filter always adds (see tests/e2e/test-snippets/test-snippets.php). Read
 * live rather than hardcoded — other E2E specs' own real orders, coupons,
 * etc. legitimately push the real count above this test's own minimal setup,
 * and nothing in the suite guarantees those get cleaned up first.
 *
 * @type {string}
 */
let expectedBadgeCount = null;

test.use( { storageState: process.env.ADMINSTATE } );

test.describe( 'Notification Badge', () => {
	test.beforeAll( async ( { browser } ) => {
		page = await browser.newPage();
		dashboardPage = new DashboardPage( page );
		await setOnboardedMerchant();
		await setGCRNotificationsDismissed();
		await dashboardPage.mockRequests();
		await dashboardPage.goto();

		const notifications = await getNotifications();
		expectedBadgeCount = String( notifications.length + 1 );
	} );

	test.afterAll( async () => {
		await clearGCRNotificationsDismissed();
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
				.filter( { hasText: expectedBadgeCount } );

			await expect( badge ).toBeVisible();
		} );

		test( 'In Google for WooCommerce sub-menu when Marketing menu is expanded', async () => {
			const badge = dashboardPage.page
				.getByRole( 'link', { name: 'Overview' } )
				.locator( 'span.update-plugins' )
				.filter( { hasText: expectedBadgeCount } );

			await expect( badge ).toBeVisible();
		} );

		test( 'On Marketing menu when switched to Analytics menu', async () => {
			const badge = dashboardPage.page
				.getByRole( 'link', { name: 'Overview' } )
				.locator( 'span.update-plugins' )
				.filter( { hasText: expectedBadgeCount } );

			await expect( badge ).toBeVisible();

			await dashboardPage.page
				.getByRole( 'link', { name: 'Analytics' } )
				.click();

			const badgeMoved = dashboardPage.page
				.getByRole( 'link', { name: 'Marketing' } )
				.locator( 'span.update-plugins' )
				.filter( { hasText: expectedBadgeCount } );

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

			// The `no_notifications=true` test-only filter forces the count to
			// a hard 0 regardless of the real notification system's state, so
			// no badge of any kind should render — not just one that happens
			// to avoid matching this file's own dynamic expected count.
			const badge = page
				.getByRole( 'link', { name: 'Marketing' } )
				.locator( 'span.update-plugins' );

			await expect( badge ).not.toBeVisible();
		} );
	} );
} );
