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
	 * Scoped to the "Select final URL" search control (`.gla-assets-loader`)
	 * so it doesn't collide with the unrelated Call to Action select that's
	 * also rendered on this step.
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
		const option = this.page
			.locator( '.gla-assets-loader' )
			.getByRole( 'option', {
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
		await this.mockAdsSettings( {
			enhanced_conversions_enabled: false,
			ads_has_unclaimed_incentive: false,
		} );

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

		await this.mockGenerateTextAssetsSuccess();
		await this.mockGenerateImageAssetsSuccess();
	}

	/**
	 * Mock generate text assets success response.
	 *
	 * @return {Promise<void>}
	 */
	async mockGenerateTextAssetsSuccess() {
		await this.fulfillGenerateTextAssetsRequest( {
			final_url: 'https://woo.com/shop/',
			items: [
				// Headlines
				{
					text: 'Latest Deals',
					type: 'headline',
				},
				{
					text: 'Limited-Time Offers',
					type: 'headline',
				},
				{
					text: 'New Arrivals In Store',
					type: 'headline',
				},
				{
					text: 'Top Deals This Week',
					type: 'headline',
				},
				{
					text: 'Fast Shipping Available',
					type: 'headline',
				},

				// Long headlines
				{
					text: 'Discover quality products at great prices',
					type: 'long_headline',
				},
				{
					text: 'Everything you need, delivered fast',
					type: 'long_headline',
				},
				{
					text: 'Upgrade your everyday shopping experience',
					type: 'long_headline',
				},
				{
					text: 'Find your next favorite product today',
					type: 'long_headline',
				},
				{
					text: 'Smart shopping starts right here',
					type: 'long_headline',
				},

				// Descriptions
				{
					text: 'Browse top picks and enjoy exclusive savings.',
					type: 'description',
				},
				{
					text: 'Shop trusted products with fast delivery.',
					type: 'description',
				},
				{
					text: 'Great value items curated just for you.',
					type: 'description',
				},
				{
					text: 'Simple shopping with reliable service.',
					type: 'description',
				},
				{
					text: 'Quality products backed by great support.',
					type: 'description',
				},
			],
		} );
	}

	/**
	 * Mock generate image assets success response.
	 *
	 * @return {Promise<void>}
	 */
	async mockGenerateImageAssetsSuccess() {
		await this.fulfillGenerateImageAssetsRequest( {
			final_url: 'https://woo.com/shop/',
			items: [
				{
					temporary_image_url:
						'https://placehold.co/400x225?text=Marketing+Image+1',
					type: 'marketing_image',
				},
				{
					temporary_image_url:
						'https://placehold.co/400x225?text=Marketing+Image+2',
					type: 'marketing_image',
				},
				{
					temporary_image_url:
						'https://placehold.co/400x225?text=Marketing+Image+3',
					type: 'marketing_image',
				},
				{
					temporary_image_url:
						'https://placehold.co/400x225?text=Marketing+Image+4',
					type: 'marketing_image',
				},
				{
					temporary_image_url:
						'https://placehold.co/200x200?text=Square+Marketing+Image+1',
					type: 'square_marketing_image',
				},
				{
					temporary_image_url:
						'https://placehold.co/200x200?text=Square+Marketing+Image+2',
					type: 'square_marketing_image',
				},
				{
					temporary_image_url:
						'https://placehold.co/200x200?text=Square+Marketing+Image+3',
					type: 'square_marketing_image',
				},
				{
					temporary_image_url:
						'https://placehold.co/200x300?text=Portrait+Marketing+Image+1',
					type: 'portrait_marketing_image',
				},
				{
					temporary_image_url:
						'https://placehold.co/200x300?text=Portrait+Marketing+Image+2',
					type: 'portrait_marketing_image',
				},
			],
		} );
	}
}
