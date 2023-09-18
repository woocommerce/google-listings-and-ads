/**
 * External dependencies
 */
const { test, expect } = require( '@playwright/test' );

/**
 * Internal dependencies
 */
import CompleteCampaign from '../../utils/pages/setup-mc/step-4-complete-campaign';
import {
	getFAQPanelTitle,
	getFAQPanelRow,
	checkFAQExpandable,
} from '../../utils/page';

test.use( { storageState: process.env.ADMINSTATE } );

test.describe.configure( { mode: 'serial' } );

/**
 * @type {import('../../utils/pages/setup-mc/step-4-complete-campaign.js').default} completeCampaign
 */
let completeCampaign = null;

/**
 * @type {import('@playwright/test').Page} page
 */
let page = null;

test.describe( 'Complete your campaign', () => {
	test.beforeAll( async ( { browser } ) => {
		page = await browser.newPage();
		completeCampaign = new CompleteCampaign( page );
		await Promise.all( [
			// Mock Jetpack as connected
			completeCampaign.mockJetpackConnected(),

			// Mock google as connected.
			completeCampaign.mockGoogleConnected(),

			// Mock Merchant Center as connected
			completeCampaign.mockMCConnected(),

			// Mock MC step as paid_ads
			completeCampaign.mockMCSetup( 'incomplete', 'paid_ads' ),

			// Mock syncable products count
			completeCampaign.fulfillSyncableProductsCountRequest( {
				count: 1024,
			} ),
		] );

		await completeCampaign.goto();
	} );

	test.afterAll( async () => {
		await completeCampaign.closePage();
	} );

	test( 'should see the heading and the texts below', async () => {
		await expect(
			page.getByRole( 'heading', {
				name: 'Complete your campaign with paid ads',
			} )
		).toBeVisible();

		await expect(
			page.getByText(
				'As soon as your products are approved, your listings and ads will be live. In the meantime, let’s set up your ads.'
			)
		).toBeVisible();
	} );

	test.describe( 'Product feed status', () => {
		test( 'should see the correct syncable products count', async () => {
			const syncableProductCountTooltip =
				completeCampaign.getSyncableProductsCountTooltip();
			await expect( syncableProductCountTooltip ).toContainText( '1024' );
		} );
	} );

	test.describe( 'FAQ panels', () => {
		test( 'should see five questions in FAQ', async () => {
			const faqTitles = getFAQPanelTitle( page );
			await expect( faqTitles ).toHaveCount( 5 );
		} );

		test( 'should not see FAQ rows when FAQ titles are not clicked', async () => {
			const faqRows = getFAQPanelRow( page );
			await expect( faqRows ).toHaveCount( 0 );
		} );

		test( 'should see FAQ rows when all FAQ titles are clicked', async () => {
			await checkFAQExpandable( page );
		} );
	} );

	test.describe( 'Click "Skip this step for now"', () => {
		test.beforeAll( async () => {
			await Promise.all( [
				// Mock settings sync request
				completeCampaign.mockSuccessfulSettingsSyncRequest(),

				// Mock product statistics request
				completeCampaign.fulfillProductStatisticsRequest( {
					timestamp: 1695011644,
					statistics: {
						active: 0,
						expiring: 0,
						pending: 0,
						disapproved: 0,
						not_synced: 1137,
					},
					scheduled_sync: 1,
				} ),
			] );
			await completeCampaign.clickSkipStepButtonButon();
		} );

		test( 'should see the setup success modal', async () => {
			const setupSuccessModal = page
				.locator( '.components-modal__content' )
				.filter( {
					hasText:
						'You’ve successfully set up Google Listings & Ads!',
				} );
			await expect( setupSuccessModal ).toBeVisible();
		} );

		test( 'should see the url contains product-feed', async () => {
			expect( page.url() ).toMatch( /path=%2Fgoogle%2Fproduct-feed/ );
		} );
	} );
} );
