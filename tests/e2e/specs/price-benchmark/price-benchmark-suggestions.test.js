/**
 * External dependencies
 */
import { expect, test } from '@playwright/test';

/**
 * Internal dependencies
 */
import PriceBenchmarkPage from '../../utils/pages/price-benchmark';

test.use( { storageState: process.env.ADMINSTATE } );

test.describe.configure( { mode: 'serial' } );

/**
 * @type {import('../../utils/pages/price-benchmark').default} priceBenchmarkPage
 */
let priceBenchmarkPage = null;

/**
 * @type {import('@playwright/test').Page} page
 */
let page = null;

test.describe( 'Price Benchmark Page', () => {
	test.beforeAll( async ( { browser } ) => {
		page = await browser.newPage();
		priceBenchmarkPage = new PriceBenchmarkPage( page );
		await Promise.all( [ priceBenchmarkPage.mockRequests() ] );
	} );

	test.afterAll( async () => {
		await page.close();
	} );

	test.describe( 'Has navigation', () => {
		test( 'Goes to the Price Benchmark page', async () => {
			await priceBenchmarkPage.goto();

			const expectedTabs = [
				'Price Benchmark & Suggestions',
				'Price Adjustments',
			];

			for ( const tabText of expectedTabs ) {
				const tabLocator = page.locator(
					`a[role="tab"]:has-text("${ tabText }")`
				);
				await expect( tabLocator ).toBeVisible();
			}
		} );

		test( 'Click on "Price Adjustments" should update the URL', async () => {
			await priceBenchmarkPage.goto();

			const priceAdjustmentsTab = page.locator(
				'a[role="tab"]:has-text("Price Adjustments")'
			);
			await priceAdjustmentsTab.click();

			await expect( page ).toHaveURL(
				'/wp-admin/admin.php?page=wc-admin&tableType=adjustments&path=%2Fgoogle%2Fprice-benchmark'
			);
		} );

		test( 'Click on "Price Benchmark & Suggestions" should update the URL', async () => {
			await priceBenchmarkPage.goto();

			const priceBenchmarkTab = page.locator(
				'a[role="tab"]:has-text("Price Benchmark & Suggestions")'
			);
			await priceBenchmarkTab.click();

			await expect( page ).toHaveURL(
				'/wp-admin/admin.php?page=wc-admin&tableType=suggestions&path=%2Fgoogle%2Fprice-benchmark'
			);
		} );

		test( 'Visiting the adjustments URL should show the "Price Adjustments" tab by default', async () => {
			await priceBenchmarkPage.goto(
				'/wp-admin/admin.php?page=wc-admin&tableType=adjustments&path=%2Fgoogle%2Fprice-benchmark'
			);

			const priceAdjustmentsTab = page.locator(
				'a[role="tab"]:has-text("Price Adjustments")'
			);
			await expect( priceAdjustmentsTab ).toHaveAttribute(
				'aria-selected',
				'true'
			);
		} );
	} );

	test.describe( 'Price Benchmark Tour', () => {
		test( 'Price Benchmark Tour should be visible', async () => {
			await priceBenchmarkPage.goto();

			const tourElement = page.locator(
				'.woocommerce-tour-kit-step__heading'
			);
			await expect( tourElement ).toBeVisible();
		} );

		test( 'Price Benchmark Tour should have the correct heading', async () => {
			await priceBenchmarkPage.goto();

			const tourHeading = page.locator(
				'.woocommerce-tour-kit-step__heading'
			);
			await expect( tourHeading ).toHaveText(
				'Price Benchmark & Suggestions'
			);
		} );

		test( 'Price Benchmark Tour should not be visible after closing', async () => {
			priceBenchmarkPage.fulfillTours( [
				{
					id: 'price-benchmark-tour',
					checked: true,
				},
			] );
			await priceBenchmarkPage.goto();

			const tourElement = page.locator(
				'.woocommerce-tour-kit-step__heading'
			);
			await expect( tourElement ).not.toBeVisible();
		} );
	} );
} );
