/**
 * Internal dependencies
 */
import { LOAD_STATE } from '../constants';
import MockRequests from '../mock-requests';
import adsReportProductsData from '../__fixtures__/ads-report-products.json';
import mcProductStatistics from '../__fixtures__/mc-product-statistics.json';

/**
 * Dashboard page object class.
 */
export default class DashboardPage extends MockRequests {
	/**
	 * @param {import('@playwright/test').Page} page
	 */
	constructor( page ) {
		super( page );
		this.page = page;
		this.googleAdsSummaryCard = this.page.locator(
			'.gla-dashboard__performance .gla-summary-card:nth-child(1)'
		);
		this.paidFeatures =
			this.googleAdsSummaryCard.locator( '.gla-paid-features' );
		this.createCampaignButton = this.paidFeatures.locator( 'button', {
			hasText: 'Create Campaign',
		} );
		this.addPaidCampaignButton = this.page.locator(
			'.gla-all-programs-table-card button',
			{
				hasText: 'Add campaign',
			}
		);
	}

	/**
	 * Get the visible tab titles from the main navigation.
	 *
	 * @return {Promise<string[]>} An array of tab titles in display order.
	 */
	async getTabTitles() {
		const tabs = this.page.locator( '.app-tab-nav [role="tab"]' );
		try {
			await tabs.first().waitFor( { state: 'visible' } );
		} catch ( e ) {
			// Do nothing if tabs are not visible
		}
		return ( await tabs.allTextContents() ).map( ( t ) => t.trim() );
	}

	/**
	 * Close the current page.
	 *
	 * @return {Promise<void>}
	 */
	async closePage() {
		await this.page.close();
	}

	/**
	 * Get summary cards on the dashboard.
	 *
	 * @return {Promise<import('@playwright/test').Locator>} Locator for summary cards.
	 */
	async getSummaryCards() {
		return this.page.locator( '.gla-summary-card' );
	}

	/**
	 * Mock all requests related to external accounts such as Merchant Center, Google, etc.
	 *
	 * @return {Promise<void>}
	 */
	async mockRequests() {
		// Mock Reports Programs
		await this.fulfillMCReportProgram( {
			free_listings: null,
			products: null,
			intervals: null,
			totals: {
				clicks: 0,
				impressions: 0,
			},
			next_page: null,
		} );

		await this.fulfillAdsReportProgram( adsReportProductsData );
		await this.fulfillProductStatisticsRequest( mcProductStatistics );

		await this.fulfillTargetAudience( {
			location: 'selected',
			countries: [ 'US' ],
			locale: 'en_US',
			language: 'English',
		} );

		await this.fulfillJetPackConnection( {
			active: 'yes',
			owner: 'yes',
			displayName: 'John',
			email: 'john@email.com',
		} );

		await this.mockGoogleConnected();

		await this.fulfillAdsConnection( {
			id: 0,
			currency: null,
			symbol: '$',
			status: 'disconnected',
		} );

		await this.mockAdsRecommendations();
		await this.fulfillAdsReportProducts( adsReportProductsData );
		await this.mockHasNoMissingEUDeclarationCampaigns();
	}

	/**
	 * Go to the dashboard page.
	 *
	 * @return {Promise<void>}
	 */
	async goto() {
		await this.page.goto(
			'/wp-admin/admin.php?page=wc-admin&path=%2Fgoogle%2Fdashboard',
			{ waitUntil: LOAD_STATE.DOM_CONTENT_LOADED }
		);
	}
}
