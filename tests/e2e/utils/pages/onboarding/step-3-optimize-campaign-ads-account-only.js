/**
 * Internal dependencies
 */
import { LOAD_STATE } from '../../constants';
import MockRequests from '../../mock-requests';

/**
 * Optimize campaign page object class.
 */
export default class OptimizeCampaign extends MockRequests {
	/**
	 * @param {import('@playwright/test').Page} page
	 */
	constructor( page ) {
		super( page );
		this.page = page;
	}

	/**
	 * Get save changes button.
	 *
	 * @return {import('@playwright/test').Locator} Get save changes button.
	 */
	getSaveChangesButton() {
		return this.page.getByRole( 'button', {
			name: 'Save changes',
			exact: true,
		} );
	}

	/**
	 * Get create campaign button.
	 *
	 * @return {import('@playwright/test').Locator} Get create campaign button.
	 */
	getCreateCampaignButton() {
		return this.page.getByRole( 'button', {
			name: 'Create campaign',
			exact: true,
		} );
	}

	/**
	 * Click create campaign button.
	 *
	 * @return {Promise<void>}
	 */
	async clickCreateCampaignButton() {
		const createCampaignButton = this.getCreateCampaignButton();
		await createCampaignButton.click();
	}

	/**
	 * Get final URL select dropdown.
	 *
	 * @return {import('@playwright/test').Locator} Get final URL select dropdown.
	 */
	getFinalUrlSelect() {
		return this.page
			.locator( '.gla-assets-loader' )
			.getByRole( 'combobox' );
	}

	/**
	 * Get select button.
	 *
	 * @return {import('@playwright/test').Locator} Get select button.
	 */
	getSelectButton() {
		return this.page.getByRole( 'button', { name: 'Select' } );
	}

	/**
	 * Get select different final URL button.
	 *
	 * @return {import('@playwright/test').Locator} Get select different final URL button.
	 */
	getSelectDifferentFinalUrlButton() {
		return this.page.getByRole( 'button', {
			name: 'Or, select a different Final URL',
		} );
	}

	/**
	 * Select URL option.
	 *
	 * @return {Promise<void>}
	 */
	async selectUrlOption() {
		const finalUrlSelect = this.getFinalUrlSelect();
		await finalUrlSelect.focus();
		const option = this.page.getByRole( 'option', {
			name: 'Shop',
		} );
		await option.click();

		const selectButton = this.getSelectButton();
		await selectButton.click();
	}

	/**
	 * Click Skip this step button.
	 *
	 * @return {Promise<void>}
	 */
	async clickSkipThisStepButton() {
		const skipThisStepButton = this.page.getByRole( 'button', {
			name: 'Skip this step',
		} );
		await skipThisStepButton.click();
		await this.page.waitForLoadState( LOAD_STATE.DOM_CONTENT_LOADED );
	}

	/**
	 * Click Save changes button.
	 *
	 * @return {Promise<void>}
	 */
	async clickSaveChangesButton() {
		const saveChangesButton = this.getSaveChangesButton();
		await saveChangesButton.click();
	}

	/**
	 * Mock optimize campaign requests.
	 *
	 * @return {Promise<void>}
	 */
	async mockOptimizeCampaignRequests() {
		await this.mockFinalUrlSuggestions( [
			{
				id: 0,
				type: 'homepage',
				title: 'Homepage',
				url: 'https://woo.com',
			},
			{
				id: 7,
				type: 'post',
				title: 'Shop',
				url: 'https://woo.com/shop/',
			},
		] );

		await this.mockAssetSuggestions( {
			logo: [
				'https://tpc.googlesyndication.com/simgad/2643735098967285793',
			],
			business_name: 'My Woo Store',
			square_marketing_image: [
				'https://tpc.googlesyndication.com/simgad/2643735098967285793',
			],
			marketing_image: [
				'https://tpc.googlesyndication.com/simgad/6792129722137622820',
			],
			portrait_marketing_image: [],
			call_to_action_selection: null,
			final_url: 'https://woo.com/shop/',
			display_url_path: [ 'shop', '' ],
			headline: [ 'My Woo Store', 'Shop', 'Buy Now' ],
			description: [ 'Best products available here.', 'Shop today!' ],
			long_headline: [ 'My Woo Store: Shop' ],
		} );

		await this.fulfillAssetGroupsForCampaign( 1, [
			{
				id: 1,
				final_url: '',
				display_url_path: [ '', '' ],
				assets: {},
			},
		] );
	}
}
