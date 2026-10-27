/**
 * External dependencies
 */
import { test, expect } from '@playwright/test';

/**
 * Internal dependencies
 */
import {
	clearOnboardedMerchant,
	setOnboardedMerchant,
	setCompletedAdsSetup,
	setCompleteMCSetup,
	clearCompleteMCSetup,
	clearCompletedAdsSetup,
	setServiceBasedMerchant,
	clearServiceBasedMerchant,
} from '../../utils/api';
import DashboardPage from '../../utils/pages/dashboard';
import ReportsPage from '../../utils/pages/reports';

test.use( { storageState: process.env.ADMINSTATE } );

test.describe.configure( { mode: 'serial' } );

const campaignName = 'Service PMax Campaign';

/**
 * @type {import('../../utils/pages/reports.js').default} reportsPage
 */
let reportsPage = null;

/**
 * @type {import('@playwright/test').Page} page
 */
let page = null;

/**
 * Requests made to the Merchant Center reports endpoint.
 *
 * @type {string[]}
 */
const mcReportRequests = [];

test.describe( 'Programs report for Ads only setup', () => {
	test.beforeAll( async ( { browser } ) => {
		page = await browser.newPage();
		reportsPage = new ReportsPage( page );

		await Promise.all( [
			setOnboardedMerchant(),
			setServiceBasedMerchant(),
			clearCompleteMCSetup(),
			setCompletedAdsSetup(),
		] );

		// Reuse the dashboard mocks for accounts and reports, then override the
		// Ads data with a single Performance Max campaign.
		await new DashboardPage( page ).mockRequests();
		await reportsPage.mockMCNotConnected();
		await reportsPage.mockAdsAccountConnected();
		await reportsPage.fulfillAdsCampaignsRequest( [
			{
				id: 1,
				name: campaignName,
				status: 'enabled',
				type: 'performance_max',
				amount: 1,
				country: 'US',
				targeted_locations: [ 'US' ],
			},
		] );
		await reportsPage.fulfillAdsReportProgram( {
			campaigns: [
				{
					id: 1,
					name: campaignName,
					status: 'enabled',
					isConverted: false,
					subtotals: {
						clicks: 12,
						impressions: 340,
						sales: 0,
						conversions: 1,
						spend: 25,
					},
				},
			],
			intervals: null,
			totals: {
				clicks: 12,
				impressions: 340,
				sales: 0,
				conversions: 1,
				spend: 25,
			},
			next_page: null,
		} );

		page.on( 'request', ( request ) => {
			if ( /\/wc\/gla\/mc\/reports\//.test( request.url() ) ) {
				mcReportRequests.push( request.url() );
			}
		} );

		await reportsPage.goto();
	} );

	test.afterAll( async () => {
		await Promise.all( [
			clearOnboardedMerchant(),
			clearServiceBasedMerchant(),
			setCompleteMCSetup(),
			clearCompletedAdsSetup(),
		] );
		await page.close();
	} );

	test( 'Should display the Reports tab in the main navigation', async () => {
		await expect(
			page.locator( '.app-tab-nav [role="tab"]', { hasText: 'Reports' } )
		).toBeVisible();
	} );

	test( 'Should display only the "Programs" sub-tab', async () => {
		const titles = await reportsPage.getSubNavTitles();
		expect( titles ).toEqual( [ 'Programs' ] );
	} );

	test( 'Should list the Ads campaign without a "Product Feed" row', async () => {
		await expect(
			reportsPage.programsTable.getByText( campaignName )
		).toBeVisible();
		await expect(
			reportsPage.programsTable.getByText( 'Product Feed' )
		).toHaveCount( 0 );
	} );

	test( 'Should not request the Merchant Center report or show an error', async () => {
		expect( mcReportRequests ).toEqual( [] );
		await expect(
			page.getByText( 'There was an error loading report.' )
		).toHaveCount( 0 );
	} );

	test( 'Should open the Programs report when the Products report is requested', async () => {
		await reportsPage.goto( 'products' );

		const titles = await reportsPage.getSubNavTitles();
		expect( titles ).toEqual( [ 'Programs' ] );
		await expect(
			reportsPage.programsTable.getByText( campaignName )
		).toBeVisible();
	} );
} );
