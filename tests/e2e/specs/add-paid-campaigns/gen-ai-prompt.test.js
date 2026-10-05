/**
 * External dependencies
 */
import { expect, test } from '@playwright/test';

/**
 * Internal dependencies
 */
import {
	clearCompletedAdsSetup,
	clearOnboardedMerchant,
	setCompletedAdsSetup,
	setOnboardedMerchant,
} from '../../utils/api';
import DashboardPage from '../../utils/pages/dashboard';
import SetupBudgetPage from '../../utils/pages/ads-onboarding/setup-budget';
import CreateCampaignPage from '../../utils/pages/create-campaign';
import { checkSnackBarMessage } from '../../utils/page';

const FINAL_URL = 'https://woo.com/shop/';

const PROMPT = 'Place the product on a marble kitchen counter';

/**
 * Image sections under test. `initialUrls` mirrors the per-type items returned by
 * `mockGenerateImageAssetsSuccess()`, in the order the picker renders them.
 */
const IMAGE_SECTIONS = [
	{
		aspect: 'landscape',
		type: 'marketing_image',
		size: '400x225',
		getSection: ( campaignPage ) =>
			campaignPage.getLandscapeImagesSection(),
		getGenerateButton: ( campaignPage ) =>
			campaignPage.getGenerateLandscapeImagesButton(),
		initialUrls: [ 1, 2, 3, 4 ].map(
			( n ) => `https://placehold.co/400x225?text=Marketing+Image+${ n }`
		),
	},
	{
		aspect: 'square',
		type: 'square_marketing_image',
		size: '200x200',
		getSection: ( campaignPage ) => campaignPage.getSquareImagesSection(),
		getGenerateButton: ( campaignPage ) =>
			campaignPage.getGenerateSquareImagesButton(),
		initialUrls: [ 1, 2, 3 ].map(
			( n ) =>
				`https://placehold.co/200x200?text=Square+Marketing+Image+${ n }`
		),
	},
	{
		aspect: 'portrait',
		type: 'portrait_marketing_image',
		size: '200x300',
		getSection: ( campaignPage ) => campaignPage.getPortraitImagesSection(),
		getGenerateButton: ( campaignPage ) =>
			campaignPage.getGeneratePortraitImagesButton(),
		initialUrls: [ 1, 2 ].map(
			( n ) =>
				`https://placehold.co/200x300?text=Portrait+Marketing+Image+${ n }`
		),
	},
];

test.use( { storageState: process.env.ADMINSTATE } );

test.describe.configure( { mode: 'serial' } );

/**
 * @type {import('../../utils/pages/create-campaign').default} createCampaignPage
 */
let createCampaignPage = null;

/**
 * @type {import('@playwright/test').Page} page
 */
let page = null;

test.describe( 'Gen AI prompt-driven image generation', () => {
	test.beforeAll( async ( { browser } ) => {
		page = await browser.newPage();
		createCampaignPage = new CreateCampaignPage( page );
		const dashboardPage = new DashboardPage( page );
		const setupBudgetPage = new SetupBudgetPage( page );

		await setOnboardedMerchant();
		await dashboardPage.mockRequests();
		await setCompletedAdsSetup();
		await setupBudgetPage.fulfillBillingStatusRequest( {
			status: 'approved',
		} );
		await setupBudgetPage.fulfillBudgetRecommendations( {
			currency: 'EUR',
			daily_budget_baseline: 12,
			recommendations: [
				{
					level: 'Recommended',
					country: 'FR',
					daily_budget: 15,
					metrics: {
						cost: 105,
						conversions: 2.2,
						conversions_value: 89.98,
					},
				},
			],
		} );
		await setupBudgetPage.mockBudgetMetrics();
		await setupBudgetPage.mockAdsIncentiveCredits();
		await setupBudgetPage.mockMCConnected();
		await createCampaignPage.mockRequests();
		await createCampaignPage.mockOptimizeCampaignRequests();
		await createCampaignPage.mockGenerateTextAssetsSuccess();
		await createCampaignPage.mockGenerateImageAssetsSuccess();
		await createCampaignPage.goto();
		await createCampaignPage.getContinueButton().click();

		await expect(
			page.getByRole( 'heading', { name: 'Optimize your campaign' } )
		).toBeVisible();
	} );

	test.afterAll( async () => {
		await clearCompletedAdsSetup();
		await clearOnboardedMerchant();
		await page.close();
	} );

	IMAGE_SECTIONS.forEach(
		( {
			aspect,
			type,
			size,
			getSection,
			getGenerateButton,
			initialUrls,
		} ) => {
			test.describe( `${ aspect } images`, () => {
				/**
				 * The picker's image URLs as of the last completed step, so each test can
				 * assert exactly what it added or changed.
				 */
				let expectedUrls = [];

				const newImageUrl = ( label ) =>
					`https://placehold.co/${ size }?text=${ aspect }+${ label }`;

				test.beforeAll( async () => {
					await getGenerateButton( createCampaignPage ).click();

					const generatedImages = getSection(
						createCampaignPage
					).locator( '.gla-gen-ai-image-picker__medium-button' );
					await expect( generatedImages ).toHaveCount(
						initialUrls.length
					);

					expectedUrls =
						await createCampaignPage.getGeneratedImageUrls(
							getSection( createCampaignPage )
						);
				} );

				test.describe( 'Generate with prompt', () => {
					test( 'Trigger opens the modal', async () => {
						await createCampaignPage
							.getGenerateWithPromptButton( aspect )
							.click();

						const modal =
							createCampaignPage.getGenerateWithPromptModal();
						await expect( modal ).toBeVisible();
						await expect(
							modal.getByRole( 'button', { name: 'Generate' } )
						).toBeDisabled();
					} );

					test( 'Generating appends a new image to the section grid', async () => {
						const modal =
							createCampaignPage.getGenerateWithPromptModal();

						await createCampaignPage.mockGenerateImageAssetsWithItems(
							[
								{
									temporary_image_url:
										newImageUrl( 'Generated' ),
									type,
								},
							],
							200,
							{ times: 1 }
						);

						const request =
							createCampaignPage.awaitForGenerateImageRequest(
								FINAL_URL,
								[ type ],
								{ prompt: PROMPT }
							);

						await modal
							.getByLabel( 'Image generation prompt' )
							.fill( PROMPT );
						await modal
							.getByRole( 'button', { name: 'Generate' } )
							.click();
						await request;

						await expect( modal ).not.toBeVisible();

						const section = getSection( createCampaignPage );
						await expect(
							section.locator(
								'.gla-gen-ai-image-picker__medium-button'
							)
						).toHaveCount( expectedUrls.length + 1 );

						const urls =
							await createCampaignPage.getGeneratedImageUrls(
								section
							);
						expect( urls.slice( 0, expectedUrls.length ) ).toEqual(
							expectedUrls
						);

						expectedUrls = urls;
					} );

					test( 'Cancelling an in-flight generate closes the modal and adds nothing', async () => {
						let releaseResponse;
						const heldResponse = new Promise( ( resolve ) => {
							releaseResponse = resolve;
						} );

						await createCampaignPage.mockGenerateImageAssetsWithItems(
							[
								{
									temporary_image_url:
										newImageUrl( 'Cancelled' ),
									type,
								},
							],
							200,
							{ times: 1, beforeFulfill: heldResponse }
						);

						await createCampaignPage
							.getGenerateWithPromptButton( aspect )
							.click();

						const modal =
							createCampaignPage.getGenerateWithPromptModal();
						await modal
							.getByLabel( 'Image generation prompt' )
							.fill( PROMPT );
						await modal
							.getByRole( 'button', { name: 'Generate' } )
							.click();

						await expect(
							modal.getByText( 'Generating asset' )
						).toBeVisible();

						await modal
							.getByRole( 'button', { name: 'Close' } )
							.click();
						await expect( modal ).not.toBeVisible();

						releaseResponse();

						const section = getSection( createCampaignPage );
						await expect(
							section.locator(
								'.gla-gen-ai-image-picker__medium-button'
							)
						).toHaveCount( expectedUrls.length );
						expect(
							await createCampaignPage.getGeneratedImageUrls(
								section
							)
						).toEqual( expectedUrls );
					} );

					test( 'A failed request shows an error notice and keeps the modal open', async () => {
						await createCampaignPage.mockGenerateImageAssetsFailure(
							{ times: 1 }
						);

						await createCampaignPage
							.getGenerateWithPromptButton( aspect )
							.click();

						const modal =
							createCampaignPage.getGenerateWithPromptModal();
						await modal
							.getByLabel( 'Image generation prompt' )
							.fill( PROMPT );
						await modal
							.getByRole( 'button', { name: 'Generate' } )
							.click();

						await checkSnackBarMessage(
							page,
							"Google AI isn't able to generate media assets for this page."
						);
						await expect( modal ).toBeVisible();
						await expect(
							modal.getByLabel( 'Image generation prompt' )
						).toHaveValue( PROMPT );

						await modal
							.getByRole( 'button', { name: 'Cancel' } )
							.click();
						await expect( modal ).not.toBeVisible();

						// Dismiss the notice so it doesn't linger into the next section's checks.
						const snackbar = page.locator( '.components-snackbar' );
						await snackbar.click();
						await expect( snackbar ).toHaveCount( 0 );
					} );

					test( 'A request that produces no image shows an inline error and keeps the modal open', async () => {
						await createCampaignPage.mockGenerateImageAssetsWithItems(
							[],
							200,
							{ times: 1 }
						);

						await createCampaignPage
							.getGenerateWithPromptButton( aspect )
							.click();

						const modal =
							createCampaignPage.getGenerateWithPromptModal();
						await modal
							.getByLabel( 'Image generation prompt' )
							.fill( PROMPT );
						await modal
							.getByRole( 'button', { name: 'Generate' } )
							.click();

						await expect(
							modal.getByText(
								'Something went wrong while generating the image. Please try again.'
							)
						).toBeVisible();
						await expect( modal ).toBeVisible();

						await modal
							.getByRole( 'button', { name: 'Cancel' } )
							.click();
						await expect( modal ).not.toBeVisible();

						expect(
							await createCampaignPage.getGeneratedImageUrls(
								getSection( createCampaignPage )
							)
						).toEqual( expectedUrls );
					} );
				} );
			} );
		}
	);
} );
