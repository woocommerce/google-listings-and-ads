/**
 * Internal dependencies
 */
import { LOAD_STATE } from '../../constants';
import MockRequests from '../../mock-requests';

/**
 * Configure product listings page object class.
 */
export default class CompleteCampaign extends MockRequests {
	/**
	 * @param {import('@playwright/test').Page} page
	 */
	constructor( page ) {
		super( page );
		this.page = page;
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
	 * Go to the set up mc page.
	 *
	 * @return {Promise<void>}
	 */
	async goto() {
		await this.page.goto(
			'/wp-admin/admin.php?page=wc-admin&path=%2Fgoogle%2Fsetup-mc&google-mc=connected',
			{ waitUntil: LOAD_STATE.DOM_CONTENT_LOADED }
		);
	}

	/**
	 * Get sections by .wcdl-section class.
	 *
	 * @return {import('@playwright/test').Locator} Get sections by .wcdl-section class.
	 */
	getSections() {
		return this.page.locator( '.wcdl-section' );
	}

	/**
	 * Get budget section.
	 *
	 * @return {import('@playwright/test').Locator} Get budget section.
	 */
	getBudgetSection() {
		return this.page.locator( '.gla-budget-section' ).nth( 0 );
	}

	/**
	 * Get skip this step for now button.
	 *
	 * @return {import('@playwright/test').Locator} Get skip this step for now button.
	 */
	getSkipStepButton() {
		return this.page.getByRole( 'button', {
			name: 'Skip this step for now',
			exact: true,
		} );
	}

	/**
	 * Get create a paid ad button.
	 *
	 * @return {import('@playwright/test').Locator} Get create a paid ad button.
	 */
	getCreatePaidAdButton() {
		return this.page.getByRole( 'button', {
			name: 'Create campaign',
			exact: true,
		} );
	}

	/**
	 * Get complete setup button.
	 *
	 * @return {import('@playwright/test').Locator} Get complete setup button.
	 */
	getCompleteSetupButton() {
		return this.page.getByRole( 'button', {
			name: 'Complete setup',
			exact: true,
		} );
	}

	/**
	 * Get skip paid ads creation button.
	 *
	 * @return {import('@playwright/test').Locator} Get skip paid ads creation button.
	 */
	getSkipPaidAdsCreationButton() {
		return this.page.getByRole( 'button', {
			name: 'Skip paid ads creation',
			exact: true,
		} );
	}

	/**
	 * Click skip this step for now button.
	 *
	 * @return {Promise<void>}
	 */
	async clickSkipStepButton() {
		const button = this.getSkipStepButton();
		await button.click();
		await this.page.waitForLoadState( LOAD_STATE.DOM_CONTENT_LOADED );
	}

	/**
	 * Click skip paid ads creation button.
	 *
	 * @return {Promise<void>}
	 */
	async clickSkipPaidAdsCreationButon() {
		const button = this.getSkipPaidAdsCreationButton();
		await button.click();
		await this.page.waitForLoadState( LOAD_STATE.DOM_CONTENT_LOADED );
	}

	/**
	 * Click create a paid ad campaign button.
	 *
	 * @return {Promise<void>}
	 */
	async clickCreatePaidAdButton() {
		const button = this.getCreatePaidAdButton();
		await button.click();
		await this.page.waitForLoadState( LOAD_STATE.DOM_CONTENT_LOADED );
	}

	/**
	 * Click complete setup button.
	 *
	 * @return {Promise<void>}
	 */
	async clickCompleteSetupButton() {
		const button = this.getCompleteSetupButton();
		await button.click();
		await this.page.waitForLoadState( LOAD_STATE.DOM_CONTENT_LOADED );
	}

	/**
	 * Register the requests when completing setup.
	 *
	 * @return {Promise<import('@playwright/test').Request[]>} The request.
	 */
	registerCompleteSetupRequests() {
		const campaignsRequestPromise = this.page.waitForRequest(
			( request ) =>
				request.url().includes( '/gla/ads/campaigns' ) &&
				request.method() === 'POST'
		);

		const mcSettingsSyncRequestPromise = this.page.waitForRequest(
			( request ) =>
				request.url().includes( '/gla/mc/settings/sync' ) &&
				request.method() === 'POST'
		);

		return Promise.all( [
			campaignsRequestPromise,
			mcSettingsSyncRequestPromise,
		] );
	}
}
