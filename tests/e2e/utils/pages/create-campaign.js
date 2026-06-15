/**
 * Internal dependencies
 */
import { LOAD_STATE } from '../constants';
import MockRequests from '../mock-requests';

/**
 * Dashboard page object class.
 */
export default class CreateCampaignPage extends MockRequests {
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
	 * Mock all requests related to external accounts such as Merchant Center, Google, etc.
	 *
	 * @return {Promise<void>}
	 */
	async mockRequests() {
		// Mock Reports Programs
		await this.fulfillJetPackConnection( {
			active: 'yes',
			owner: 'yes',
			displayName: 'John',
			email: 'john@email.com',
		} );

		await this.mockGoogleConnected();
		await this.mockAdsAccountConnected();
		await this.mockHasNoMissingEUDeclarationCampaigns();
	}

	/**
	 * Go to the Create Campaign page.
	 *
	 * @return {Promise<void>}
	 */
	async goto() {
		await this.page.goto(
			'/wp-admin/admin.php?page=wc-admin&path=%2Fgoogle%2Fdashboard&subpath=%2Fcampaigns%2Fcreate',
			{ waitUntil: LOAD_STATE.DOM_CONTENT_LOADED }
		);
	}

	/**
	 * Get the Continue button.
	 *
	 * @return {import('@playwright/test').Locator} Continue button.
	 */
	getContinueButton() {
		return this.page.getByRole( 'button', {
			name: 'Continue',
			exact: true,
		} );
	}

	/**
	 * Get create campaign button.
	 *
	 * @return {import('@playwright/test').Locator} Get create campaign button.
	 */
	getCreateCampaignButton() {
		// Intentionally not using getByRole here, as another button with the same accessible name exists in the Stepper header.
		return this.page.locator(
			'button[data-action="submit-campaign-and-assets"]'
		);
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
		return this.page.getByRole( 'combobox' );
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
	 * Get Add headline button.
	 *
	 * @return {import('@playwright/test').Locator} Get Add headline button.
	 */
	getAddHeadlineButton() {
		return this.page
			.getByRole( 'button', { name: 'Add text' } )
			.filter( { hasText: 'Add headline' } );
	}

	/**
	 * Get Add long headline button.
	 *
	 * @return {import('@playwright/test').Locator} Get Add long headline button.
	 */
	getAddLongHeadlineButton() {
		return this.page
			.getByRole( 'button', { name: 'Add text' } )
			.filter( { hasText: 'Add long headline' } );
	}

	/**
	 * Get Add description button.
	 *
	 * @return {import('@playwright/test').Locator} Get Add description button.
	 */
	getAddDescriptionButton() {
		return this.page
			.getByRole( 'button', { name: 'Add text' } )
			.filter( { hasText: 'Add description' } );
	}

	/**
	 * Get generate headline button.
	 *
	 * @return {import('@playwright/test').Locator} Get generate headline button.
	 */
	getGenerateHeadlineButton() {
		return this.page.getByRole( 'button', {
			name: 'Generate headline',
		} );
	}

	/**
	 * Get generate headlines button.
	 *
	 * @return {import('@playwright/test').Locator} Get generate headlines button.
	 */
	getGenerateHeadlinesButton() {
		return this.page.getByRole( 'button', {
			name: 'Generate headlines',
		} );
	}

	/**
	 * Get generate long headline button.
	 *
	 * @return {import('@playwright/test').Locator} Get generate long headline button.
	 */
	getGenerateLongHeadlineButton() {
		return this.page.getByRole( 'button', {
			name: 'Generate long headline',
		} );
	}

	/**
	 * Get generate long headlines button.
	 *
	 * @return {import('@playwright/test').Locator} Get generate long headlines button.
	 */
	getGenerateLongHeadlinesButton() {
		return this.page.getByRole( 'button', {
			name: 'Generate long headlines',
		} );
	}

	/**
	 * Get generate description button.
	 *
	 * @return {import('@playwright/test').Locator} Get generate description button.
	 */
	getGenerateDescriptionButton() {
		return this.page.getByRole( 'button', {
			name: 'Generate description',
		} );
	}

	/**
	 * Get generate descriptions button.
	 *
	 * @return {import('@playwright/test').Locator} Get generate descriptions button.
	 */
	getGenerateDescriptionsButton() {
		return this.page.getByRole( 'button', {
			name: 'Generate descriptions',
		} );
	}

	/**
	 * Get headlines section.
	 *
	 * @return {import('@playwright/test').Locator} Get headlines section.
	 */
	getHeadlinesSection() {
		return this.page
			.locator(
				'.gla-asset-field:has(:where(.gla-asset-field__heading):has-text("Headlines"))'
			)
			.first();
	}

	/**
	 * Get headline inputs.
	 *
	 * @return {import('@playwright/test').Locator} Get headline inputs.
	 */
	getHeadlineInputs() {
		const headlinesSection = this.getHeadlinesSection();
		const headlineInputs = headlinesSection.locator(
			'input[placeholder="Headline"]'
		);

		return headlineInputs;
	}

	/**
	 * Get headline inputs values.
	 *
	 * @return {Promise<string[]>} Get headline inputs values.
	 */
	async getHeadlineInputsValues() {
		const headlineInputs = this.getHeadlineInputs();
		const values = await headlineInputs.evaluateAll( ( inputs ) =>
			inputs.map( ( input ) => input.value )
		);

		return values;
	}

	/**
	 * Get long headlines section.
	 *
	 * @return {import('@playwright/test').Locator} Get long headlines section.
	 */
	getLongHeadlinesSection() {
		return this.page
			.locator(
				'.gla-asset-field:has(:where(.gla-asset-field__heading):has-text("Long headlines"))'
			)
			.first();
	}

	/**
	 * Get headline inputs.
	 *
	 * @return {import('@playwright/test').Locator} Get headline inputs.
	 */
	getLongHeadlineInputs() {
		const longHeadlinesSection = this.getLongHeadlinesSection();
		const longHeadlineInputs = longHeadlinesSection.locator(
			'input[placeholder="Long headline"]'
		);

		return longHeadlineInputs;
	}

	/**
	 * Get descriptions section.
	 *
	 * @return {import('@playwright/test').Locator} Get descriptions section.
	 */
	getDescriptionsSection() {
		return this.page
			.locator(
				'.gla-asset-field:has(:where(.gla-asset-field__heading):has-text("Descriptions"))'
			)
			.first();
	}

	/**
	 * Get description inputs.
	 *
	 * @return {import('@playwright/test').Locator} Get description inputs.
	 */
	getDescriptionInputs() {
		const descriptionsSection = this.getDescriptionsSection();
		const descriptionInputs = descriptionsSection.locator(
			'input[placeholder="Description"]'
		);

		return descriptionInputs;
	}

	/**
	 * Get description inputs values.
	 *
	 * @return {Promise<string[]>} Get description inputs values.
	 */
	async getDescriptionInputsValues() {
		const descriptionInputs = this.getDescriptionInputs();
		const values = await descriptionInputs.evaluateAll( ( inputs ) =>
			inputs.map( ( input ) => input.value )
		);

		return values;
	}

	/**
	 * Get long headline inputs values.
	 *
	 * @return {Promise<string[]>} Get long headline inputs values.
	 */
	async getLongHeadlineInputsValues() {
		const longHeadlineInputs = this.getLongHeadlineInputs();
		const values = await longHeadlineInputs.evaluateAll( ( inputs ) =>
			inputs.map( ( input ) => input.value )
		);

		return values;
	}

	/**
	 * Get landscape images section.
	 *
	 * @return {import('@playwright/test').Locator} Get landscape images section.
	 */
	getLandscapeImagesSection() {
		return this.page
			.locator(
				'.gla-asset-field:has(:where(.gla-asset-field__heading):has-text("Landscape images"))'
			)
			.first();
	}

	/**
	 * Get generate landscape images button.
	 *
	 * @return {import('@playwright/test').Locator} Get generate landscape images button.
	 */
	getGenerateLandscapeImagesButton() {
		return this.page.getByRole( 'button', {
			name: 'Generate landscape images',
		} );
	}

	/**
	 * Get landscape images section image picker.
	 *
	 * @return {import('@playwright/test').Locator} Get landscape images section image picker.
	 */
	getLandscapeImagesSectionImagePicker() {
		const landscapeImagesSection = this.getLandscapeImagesSection();
		return landscapeImagesSection.locator( '.gla-gen-ai-image-picker' );
	}

	/**
	 * Get landscape generated images.
	 *
	 * @return {import('@playwright/test').Locator} Get landscape generated images.
	 */
	getLandscapeGeneratedImages() {
		const landscapeImagesSection = this.getLandscapeImagesSection();
		return landscapeImagesSection.locator(
			'.gla-gen-ai-image-picker__medium-button'
		);
	}

	/**
	 * Get landscape image picker add selected images button.
	 *
	 * @return {import('@playwright/test').Locator} Get landscape image picker add selected images button.
	 */
	getLandscapeImagePickerAddSelectedImagesButton() {
		const landscapeImagesSection = this.getLandscapeImagesSection();
		return landscapeImagesSection.getByRole( 'button', {
			name: 'Add selected images',
		} );
	}

	/**
	 * Get landscape campaign images.
	 *
	 * @return {import('@playwright/test').Locator} Get landscape campaign images.
	 */
	getCampaignLandscapeImageItems() {
		const landscapeImagesSection = this.getLandscapeImagesSection();
		return landscapeImagesSection.locator( '.gla-media-selector__item' );
	}

	/**
	 * Get square images section.
	 *
	 * @return {import('@playwright/test').Locator} Get square images section.
	 */
	getSquareImagesSection() {
		return this.page
			.locator(
				'.gla-asset-field:has(:where(.gla-asset-field__heading):has-text("Square images"))'
			)
			.first();
	}

	/**
	 * Get square campaign images.
	 *
	 * @return {import('@playwright/test').Locator} Get square campaign images.
	 */
	getCampaignSquareImageItems() {
		const squareImagesSection = this.getSquareImagesSection();
		return squareImagesSection.locator( '.gla-media-selector__item' );
	}

	/**
	 * Get square images section image picker.
	 *
	 * @return {import('@playwright/test').Locator} Get square images section image picker.
	 */
	getSquareImagesSectionImagePicker() {
		const squareImagesSection = this.getSquareImagesSection();
		return squareImagesSection.locator( '.gla-gen-ai-image-picker' );
	}

	/**
	 * Get generate square images button.
	 *
	 * @return {import('@playwright/test').Locator} Get generate square images button.
	 */
	getGenerateSquareImagesButton() {
		return this.page.getByRole( 'button', {
			name: 'Generate square images',
		} );
	}

	/**
	 * Get square generated images.
	 *
	 * @return {import('@playwright/test').Locator} Get square generated images.
	 */
	getSquareGeneratedImages() {
		const squareImagesSection = this.getSquareImagesSection();
		return squareImagesSection.locator(
			'.gla-gen-ai-image-picker__medium-button'
		);
	}

	/**
	 * Get portrait section.
	 *
	 * @return {import('@playwright/test').Locator} Get portrait images section.
	 */
	getPortraitImagesSection() {
		return this.page
			.locator(
				'.gla-asset-field:has(:where(.gla-asset-field__heading):has-text("Portrait images"))'
			)
			.first();
	}

	/**
	 * Get generate portrait images button.
	 *
	 * @return {import('@playwright/test').Locator} Get generate portrait images button.
	 */
	getGeneratePortraitImagesButton() {
		return this.page.getByRole( 'button', {
			name: 'Generate portrait images',
		} );
	}

	/**
	 * Get portrait campaign images.
	 *
	 * @return {import('@playwright/test').Locator} Get portrait campaign images.
	 */
	getCampaignPortraitImageItems() {
		const portraitImagesSection = this.getPortraitImagesSection();
		return portraitImagesSection.locator( '.gla-media-selector__item' );
	}

	/**
	 * Get portrait images section image picker.
	 *
	 * @return {import('@playwright/test').Locator} Get portrait images section image picker.
	 */
	getPortraitImagesSectionImagePicker() {
		const portraitImagesSection = this.getPortraitImagesSection();
		return portraitImagesSection.locator( '.gla-gen-ai-image-picker' );
	}

	/**
	 * Get portrait generated images.
	 *
	 * @return {import('@playwright/test').Locator} Get portrait generated images.
	 */
	getPortraitGeneratedImages() {
		const portraitImagesSection = this.getPortraitImagesSection();
		return portraitImagesSection.locator(
			'.gla-gen-ai-image-picker__medium-button'
		);
	}

	/**
	 * Get final URL card.
	 *
	 * @return {import('@playwright/test').Locator} Get final URL card.
	 */
	getFinalUrlCard() {
		return this.page.locator( '.gla-final-url-card' );
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

	/**
	 * Await for the generate text assets request.
	 *
	 * @param {string} finalUrl The final URL.
	 * @param {Array} types The requested asset types.
	 * @return {Promise<Request>} The request.
	 */
	async awaitForGenerateTextRequest( finalUrl, types ) {
		return this.page.waitForRequest( ( request ) => {
			if (
				! request.url().includes( '/gla/ads/assets/generate-text' ) ||
				request.method() !== 'POST'
			) {
				return false;
			}

			const payload = request.postDataJSON();

			return (
				payload.final_url === finalUrl &&
				Array.isArray( payload.types ) &&
				types.every( ( type ) => payload.types.includes( type ) )
			);
		} );
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
	 * Mock generate text assets empty response.
	 *
	 * @return {Promise<void>}
	 */
	async mockEmptyGenerateTextAssets() {
		await this.fulfillGenerateTextAssetsRequest( {
			final_url: 'https://woo.com/shop/',
			items: [],
		} );
	}

	/**
	 * Mock generate media assets success response.
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

	/**
	 * Mock generate image assets empty response.
	 *
	 * @return {Promise<void>}
	 */
	async mockEmptyGenerateImageAssets() {
		await this.fulfillGenerateImageAssetsRequest( {
			final_url: 'https://woo.com/shop/',
			items: [],
		} );
	}

	/**
	 * Await for the generate image assets request.
	 *
	 * @param {string} finalUrl The final URL.
	 * @param {Array} types The requested asset types.
	 * @return {Promise<Request>} The request.
	 */
	async awaitForGenerateImageRequest( finalUrl, types ) {
		return this.page.waitForRequest( ( request ) => {
			if (
				! request.url().includes( '/gla/ads/assets/generate-images' ) ||
				request.method() !== 'POST'
			) {
				return false;
			}

			const payload = request.postDataJSON();

			return (
				payload.final_url === finalUrl &&
				Array.isArray( payload.types ) &&
				types.every( ( type ) => payload.types.includes( type ) )
			);
		} );
	}
}
