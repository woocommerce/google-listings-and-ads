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
	 * Get ads audience section.
	 *
	 * @return {import('@playwright/test').Locator} Get ads audience section.
	 */
	getAdsAudienceSection() {
		return this.getSections().nth( 1 );
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
	 * Click skip paid ads creation button.
	 *
	 * @return {Promise<void>}
	 */
	async clickSkipPaidAdsCreationButton() {
		const button = this.getSkipPaidAdsCreationButton();
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

	/**
	 * Retrieves the "Complete setup without setting up ads" button from the skip paid ads creation modal.
	 *
	 * @return {import('@playwright/test').Locator} Locator for the "Complete setup without setting up ads" button.
	 */
	getCompleteSetupModalButton() {
		return this.page.getByRole( 'button', {
			name: 'Complete setup without setting up ads',
			exact: true,
		} );
	}

	/**
	 * Clicks the "Complete setup without setting up ads" button in the skip paid ads creation modal.
	 *
	 * @return {Promise<void>} Resolves when the click action is completed and the page has loaded.
	 */
	async clickCompleteSetupModalButton() {
		const button = this.getCompleteSetupModalButton();
		await button.click();
		await this.page.waitForLoadState( LOAD_STATE.DOM_CONTENT_LOADED );
	}

	/**
	 * Retrieves the "Cancel" button from the skip paid ads creation modal.
	 *
	 * @return {import('@playwright/test').Locator} Locator for the "Cancel" button.
	 */
	getCancelModalButton() {
		return this.page.getByRole( 'button', {
			name: 'Cancel',
			exact: true,
		} );
	}

	/**
	 * Clicks the "Cancel" button in the skip paid ads creation modal.
	 *
	 * @return {Promise<void>} Resolves when the click action is completed.
	 */
	async clickCancelModalButton() {
		const button = this.getCancelModalButton();
		await button.click();
	}

	/**
	 * Retrieves the skip paid ads creation modal element.
	 *
	 * @return {import('@playwright/test').Locator} Locator for the modal containing the text "Skip setting up ads?".
	 */
	getSkipPaidAdsCreationModal() {
		return this.page.locator( '.components-modal__content' ).filter( {
			hasText: 'Skip setting up ads?',
		} );
	}

	/**
	 * Retrieves the setup success modal element.
	 *
	 * @return {import('@playwright/test').Locator} Locator for the modal containing the text "You’ve successfully set up Google for WooCommerce!".
	 */
	getSetupSuccessModal() {
		return this.page.locator( '.components-modal__content' ).filter( {
			hasText: 'You’ve successfully set up Google for WooCommerce!',
		} );
	}
}
