/**
 * External dependencies
 */
import { expect, test } from '@playwright/test';
/**
 * Internal dependencies
 */
import { clearOnboardedMerchant, setOnboardedMerchant } from '../../utils/api';
import DashboardPage from '../../utils/pages/dashboard.js';
import ProductFeedPage from '../../utils/pages/product-feed';
import SetupAdsAccountsPage from '../../utils/pages/setup-ads/setup-ads-accounts.js';
import { LOAD_STATE } from '../../utils/constants';

test.use( { storageState: process.env.ADMINSTATE } );

test.describe.configure( { mode: 'serial' } );

/**
 * @type {import('../../utils/pages/dashboard.js').default} dashboardPage
 */
let dashboardPage = null;

/**
 * @type {import('../../utils/pages/setup-ads/setup-ads-accounts').default} setupAdsAccounts
 */
let setupAdsAccounts = null;

/**
 * @type {import('../../utils/pages/product-feed').default} productFeedPage
 */
let productFeedPage = null;

/**
 * @type {import('@playwright/test').Page} page
 */
let page = null;

test.describe( 'Product Feed Page', () => {
	test.beforeAll( async ( { browser } ) => {
		page = await browser.newPage();
		dashboardPage = new DashboardPage( page );
		productFeedPage = new ProductFeedPage( page );
		setupAdsAccounts = new SetupAdsAccountsPage( page );
		await Promise.all( [
			setupAdsAccounts.mockAdsAccountsResponse( [] ),
			productFeedPage.mockRequests(),
			setOnboardedMerchant(),
		] );
	} );

	test.afterAll( async () => {
		await clearOnboardedMerchant();
		await page.close();
	} );

	test.describe( 'No campaign', () => {
		test.beforeAll( async () => {
			await productFeedPage.fulfillAdsCampaignsRequest( [] );
			await productFeedPage.goto();
		} );

		test( 'No active product and no campaign; Do not display campaign notice', async () => {
			await expect(
				page.getByRole( 'heading', { level: 1, name: 'Product Feed' } )
			).toBeVisible();

			await productFeedPage.fulfillProductStatisticsRequest( {
				timestamp: 1695011644,
				statistics: {
					active: 0,
					expiring: 0,
					pending: 0,
					disapproved: 0,
					not_synced: 1137,
				},
				scheduled_sync: 0,
				loading: false,
			} );

			await page.reload();
			await page.waitForLoadState( LOAD_STATE.DOM_CONTENT_LOADED );

			await expect(
				page.getByRole( 'heading', {
					name: 'Overview',
				} )
			).toBeVisible();

			await expect(
				await productFeedPage.getActiveProductValueElement()
			).toBeVisible();

			await expect(
				( await productFeedPage.getActiveProductValue() ).trim()
			).toBe( '0' );

			await expect(
				await productFeedPage.getCampaignNoticeSection()
			).not.toBeVisible();
		} );

		test( 'Has active product but no campaign; Display campaign notice', async () => {
			await productFeedPage.fulfillProductStatisticsRequest( {
				timestamp: 1695011644,
				statistics: {
					active: 1,
					expiring: 0,
					pending: 0,
					disapproved: 0,
					not_synced: 1137,
				},
				scheduled_sync: 0,
				loading: false,
			} );

			await page.reload();
			await page.waitForLoadState( LOAD_STATE.DOM_CONTENT_LOADED );

			await expect(
				await productFeedPage.getActiveProductValueElement()
			).toBeVisible();

			await expect(
				( await productFeedPage.getActiveProductValue() ).trim()
			).toBe( '1' );

			const noticeSection =
				await productFeedPage.getCampaignNoticeSection();
			const createCampaignButton =
				await productFeedPage.getInNoticeCreateCampaignButton();

			await expect( noticeSection ).toBeVisible();
			await expect( createCampaignButton ).toBeVisible();
			await createCampaignButton.click();
			await page.waitForLoadState( LOAD_STATE.DOM_CONTENT_LOADED );
			await expect(
				page.getByRole( 'heading', {
					level: 1,
					name: 'Set up your accounts',
				} )
			).toBeVisible();

			await productFeedPage.goto();
			await page.waitForLoadState( LOAD_STATE.DOM_CONTENT_LOADED );

			await expect(
				page.getByRole( 'heading', { level: 1, name: 'Product Feed' } )
			).toBeVisible();
		} );
	} );

	test.describe( 'Has campaign', () => {
		test.beforeAll( async () => {
			await productFeedPage.fulfillAdsCampaignsRequest( [
				{
					id: 111111111,
					name: 'Test Campaign',
					status: 'enabled',
					type: 'performance_max',
					amount: 1,
					country: 'US',
					targeted_locations: [ 'US' ],
				},
			] );
		} );

		test( 'Has active product and a campaign; Do not display campaign notice', async () => {
			await productFeedPage.goto();
			await page.waitForLoadState( LOAD_STATE.DOM_CONTENT_LOADED );

			await expect(
				await productFeedPage.getActiveProductValueElement()
			).toBeVisible();

			await expect(
				( await productFeedPage.getActiveProductValue() ).trim()
			).toBe( '1' );

			await expect(
				await productFeedPage.getCampaignNoticeSection()
			).not.toBeVisible();
		} );

		test( 'Has campaign but no active product; Do not display campaign notice', async () => {
			await productFeedPage.fulfillProductStatisticsRequest( {
				timestamp: 1695011644,
				statistics: {
					active: 0,
					expiring: 0,
					pending: 0,
					disapproved: 0,
					not_synced: 1137,
				},
				scheduled_sync: 0,
				loading: false,
			} );
			await page.reload();
			await page.waitForLoadState( LOAD_STATE.DOM_CONTENT_LOADED );

			await expect(
				await productFeedPage.getActiveProductValueElement()
			).toBeVisible();

			await expect(
				( await productFeedPage.getActiveProductValue() ).trim()
			).toBe( '0' );

			await expect(
				await productFeedPage.getCampaignNoticeSection()
			).not.toBeVisible();
		} );
	} );
} );
