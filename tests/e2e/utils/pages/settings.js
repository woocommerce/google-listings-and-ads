/**
 * Internal dependencies
 */
import MockRequests from '../mock-requests';
import { LOAD_STATE } from '../constants';

export default class SettingsPage extends MockRequests {
	/**
	 * @param {import('@playwright/test').Page} page
	 */
	constructor( page ) {
		super( page );
		this.page = page;
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
	 * Get the Grant Access Button.
	 *
	 * @return {Promise<import('@playwright/test').Locator>}  The Grant Access Button
	 */
	getGrantAccessBtn() {
		return this.page.getByRole( 'button', {
			name: 'Get early access',
			exact: true,
		} );
	}
}
