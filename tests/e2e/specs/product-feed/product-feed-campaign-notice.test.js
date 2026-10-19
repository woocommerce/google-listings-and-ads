/**
 * External dependencies
 */
import { expect, test } from '@playwright/test';
/**
 * Internal dependencies
 */
import {
	clearOnboardedMerchant,
	setOnboardedMerchant,
	setCompletedAdsSetup,
	clearCompletedAdsSetup,
} from '../../utils/api';
import ProductFeedPage, {
	buildProductJourney,
} from '../../utils/pages/product-feed';
import { LOAD_STATE } from '../../utils/constants';

test.use( { storageState: process.env.ADMINSTATE } );

test.describe.configure( { mode: 'serial' } );

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
		productFeedPage = new ProductFeedPage( page );
		await Promise.all( [
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
		} );

		test( 'No approved product and no campaign; Do not display campaign notice', async () => {
			await productFeedPage.fulfillProductJourney(
				buildProductJourney( 0 )
			);

			await productFeedPage.goto();
			await expect(
				page.getByRole( 'heading', { level: 1, name: 'Product Feed' } )
			).toBeVisible();

			await expect(
				page.getByRole( 'heading', {
					name: 'Overview',
				} )
			).toBeVisible();

			await expect(
				productFeedPage.getApprovedProductsHeadline()
			).toHaveText( '0 of 137 products approved by Google' );

			await expect(
				await productFeedPage.getCampaignNoticeSection()
			).not.toBeVisible();
		} );

		test( 'Has approved product but no campaign; Display campaign notice', async () => {
			await clearCompletedAdsSetup();
			await productFeedPage.fulfillProductJourney(
				buildProductJourney( 1 )
			);

			await productFeedPage.goto();

			await expect(
				productFeedPage.getApprovedProductsHeadline()
			).toHaveText( '1 of 138 products approved by Google' );

			const noticeSection =
				await productFeedPage.getCampaignNoticeSection();
			const createCampaignButton =
				productFeedPage.getInNoticeCreateCampaignButton();

			await expect( noticeSection ).toBeVisible();
			await expect( createCampaignButton ).toBeVisible();
			await createCampaignButton.click();
			await page.waitForLoadState( LOAD_STATE.DOM_CONTENT_LOADED );
			await productFeedPage.mockAdsAccountsResponse( [] );
			await expect(
				page.getByRole( 'heading', {
					level: 1,
					name: 'Set up your accounts',
				} )
			).toBeVisible();
		} );
	} );

	test.describe( 'Has campaign', () => {
		test.beforeAll( async () => {
			await setCompletedAdsSetup();
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

		test.afterAll( async () => {
			await clearCompletedAdsSetup();
			await page.close();
		} );

		test( 'Has approved product and a campaign; Do not display campaign notice', async () => {
			await productFeedPage.fulfillProductJourney(
				buildProductJourney( 1 )
			);
			await productFeedPage.goto();

			await expect(
				productFeedPage.getApprovedProductsHeadline()
			).toHaveText( '1 of 138 products approved by Google' );

			await expect(
				await productFeedPage.getCampaignNoticeSection()
			).not.toBeVisible();
		} );

		test( 'Has campaign but no approved product; Do not display campaign notice', async () => {
			await productFeedPage.fulfillProductJourney(
				buildProductJourney( 0 )
			);
			await productFeedPage.goto();

			await expect(
				productFeedPage.getApprovedProductsHeadline()
			).toHaveText( '0 of 137 products approved by Google' );

			await expect(
				await productFeedPage.getCampaignNoticeSection()
			).not.toBeVisible();
		} );
	} );
} );
