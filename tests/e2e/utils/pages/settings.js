/**
 * Internal dependencies
 */
import MockRequests from '../mock-requests';
import { LOAD_STATE } from '../constants';
import adsReportProductsData from '../__fixtures__/ads-report-products.json';
import mcProductStatistics from '../__fixtures__/mc-product-statistics.json';

export default class SettingsPage extends MockRequests {
	/**
	 * @param {import('@playwright/test').Page} page
	 */
	constructor( page ) {
		super( page );
		this.page = page;
		this.youTubeCard = this.page
			.locator( '.gla-account-card' )
			.filter( { hasText: 'YouTube' } );
	}

	/**
	 * Close the Settings page.
	 *
	 * @return {Promise<void>}
	 */
	async closePage() {
		await this.page.close();
	}

	/**
	 * Go to the Settings page.
	 *
	 * @return {Promise<void>}
	 */
	async goto() {
		await this.page.goto(
			'/wp-admin/admin.php?page=wc-admin&path=%2Fgoogle%2Fsettings',
			{ waitUntil: LOAD_STATE.DOM_CONTENT_LOADED }
		);
	}

	/**
	 * Mock all requests related to external accounts such as Merchant Center, Google, etc.
	 *
	 * @return {Promise<void>}
	 */
	async mockRequests() {
		await this.mockJetpackConnected();
		await this.mockGoogleConnected();
		await this.mockMCConnected();
		await this.mockAdsAccountConnected();
		await this.mockContactInformation();
		await this.mockSuccessfulSettingsSyncRequest();
		await this.mockEnhancedConversionsStatus();
		await this.fulfillAdsReportProducts( adsReportProductsData );
		await this.fulfillProductStatisticsRequest( mcProductStatistics );
		await this.mockYouTubeAccountNotConnected();
	}

	/**
	 * Mock the target audience request with the given countries.
	 *
	 * @param {Array<string>} [countries=['US']] country codes to be mocked.
	 * @return {Promise<void>}
	 */
	async mockTargetAudienceCountries( ...countries ) {
		await this.fulfillTargetAudience( {
			location: 'selected',
			countries: countries.length ? countries : [ 'US' ],
			locale: 'en_US',
			language: 'English',
		} );
	}

	/**
	 * Get the Tax rate setup section.
	 *
	 * Scoped so that radio queries within it don't also match unrelated
	 * radio groups elsewhere on the Settings page (e.g. Shipping rates).
	 *
	 * @return {import('@playwright/test').Locator} The Tax rate section.
	 */
	getTaxRateSection() {
		return this.page.locator( '.gla-section' ).filter( {
			has: this.page.getByRole( 'heading', {
				name: 'Tax rate (required for U.S. only)',
			} ),
		} );
	}

	/**
	 * Get the Grant Access Button.
	 *
	 * @return {Promise<import('@playwright/test').Locator>}  The Grant Access Button
	 */
	getGrantAccessBtn() {
		return this.page.getByRole( 'button', {
			name: 'Grant access',
			exact: true,
		} );
	}

	/**
	 * Get the Enhanced Conversions checkbox.
	 *
	 * @return {Promise<import('@playwright/test').Locator>} The Enhanced Conversions checkbox
	 */
	getEnhancedConversionsCheckbox() {
		return this.page.getByRole( 'checkbox', {
			name: 'Send Enhanced Conversions data to Google Ads',
		} );
	}

	/**
	 * Get the Complete YouTube Setup button.
	 *
	 * @return {Promise<import('@playwright/test').Locator>} The Complete YouTube Setup button
	 */
	getYouTubeCompleteSetupButton() {
		return this.youTubeCard.getByRole( 'button', {
			name: 'Complete setup',
		} );
	}

	/**
	 * Get the YouTube Connect button.
	 *
	 * @return {Promise<import('@playwright/test').Locator>} The Connect button.
	 */
	getYouTubeConnectButton() {
		return this.youTubeCard.getByRole( 'button', { name: 'Connect' } );
	}

	/**
	 * Get the YouTube Disconnect button.
	 *
	 * @return {Promise<import('@playwright/test').Locator>} The Disconnect button.
	 */
	getYouTubeDisconnectButton() {
		return this.youTubeCard.getByRole( 'button', {
			name: 'Disconnect YouTube account',
		} );
	}

	/**
	 * Register a wait for the YouTube disconnect request.
	 *
	 * Matches the POST that @wordpress/api-fetch sends for DELETE operations,
	 * identified by the X-HTTP-Method-Override: DELETE header.
	 *
	 * @return {Promise<import('@playwright/test').Request>} The request.
	 */
	registerYouTubeDisconnectRequest() {
		return this.page.waitForRequest(
			( request ) =>
				request.url().includes( '/gla/youtube/connection' ) &&
				request.method() === 'POST' &&
				request.headers()[ 'x-http-method-override' ] === 'DELETE'
		);
	}

	/**
	 * Await for the YouTube connect request.
	 *
	 * @return {Promise<import('@playwright/test').Request>} The request.
	 */
	registerYouTubeConnectRequest() {
		return this.page.waitForRequest(
			( request ) =>
				request.url().includes( '/gla/youtube/connect' ) &&
				request.method() === 'GET'
		);
	}

	/**
	 * Register the request when the enhanced conversions checkbox is checked or unchecked.
	 *
	 * @return {Promise<import('@playwright/test').Request>} The request.
	 */
	async registerEnhancedConversionsStatusRequests() {
		return this.page.waitForRequest(
			( request ) =>
				request.url().includes( '/gla/ads/settings' ) &&
				request.method() === 'POST'
		);
	}

	/**
	 * Register requests sent when saving target audience settings.
	 *
	 * @return {Promise<import('@playwright/test').Request>} The request.
	 */
	registerTargetAudienceSaveRequests() {
		return this.page.waitForRequest(
			( request ) =>
				request.url().includes( '/gla/mc/target_audience' ) &&
				request.method() === 'POST'
		);
	}
}
