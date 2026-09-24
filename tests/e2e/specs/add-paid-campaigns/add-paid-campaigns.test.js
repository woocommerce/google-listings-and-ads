/**
 * External dependencies
 */
import { expect, test } from '@playwright/test';

/**
 * Internal dependencies
 */
import {
	clearOnboardedMerchant,
	setOnboardedMerchant,
	setCompletedAdsSetup,
	clearCompletedAdsSetup,
} from '../../utils/api';
import DashboardPage from '../../utils/pages/dashboard';
import SetupAdsAccountsPage from '../../utils/pages/ads-onboarding/setup-ads-accounts';
import SetupBudgetPage from '../../utils/pages/ads-onboarding/setup-budget';
import CreateCampaignPage from '../../utils/pages/create-campaign';
import { LOAD_STATE } from '../../utils/constants';
import {
	getFAQPanelTitle,
	getFAQPanelRow,
	checkFAQExpandable,
	checkSnackBarMessage,
} from '../../utils/page';

const ADS_ACCOUNTS = [
	{
		id: 1111111,
		name: 'Test 1',
	},
	{
		id: 2222222,
		name: 'Test 2',
	},
];

test.use( { storageState: process.env.ADMINSTATE } );

test.describe.configure( { mode: 'serial' } );

/**
 * @type {import('../../utils/pages/dashboard.js').default} dashboardPage
 */
let dashboardPage = null;

/**
 * @type {import('../../utils/pages/create-campaign').default} createCampaignPage
 */
let createCampaignPage = null;

/**
 * @type {import('../../utils/pages/ads-onboarding/setup-ads-accounts').default} setupAdsAccounts
 */
let setupAdsAccounts = null;

/**
 * @type {import('../../utils/pages/ads-onboarding/setup-budget.js').default} setupBudgetPage
 */
let setupBudgetPage = null;

/**
 * @type {import('@playwright/test').Page} page
 */
let page = null;

test.describe( 'Add paid campaign', () => {
	test.beforeAll( async ( { browser } ) => {
		page = await browser.newPage();
		dashboardPage = new DashboardPage( page );
		setupAdsAccounts = new SetupAdsAccountsPage( page );
		setupBudgetPage = new SetupBudgetPage( page );
		createCampaignPage = new CreateCampaignPage( page );
		await setOnboardedMerchant();
		await dashboardPage.fulfillAdsCampaignsRequest( [
			{
				id: 1,
				name: 'Test Campaign 1',
				status: 'enabled',
				type: 'performance_max',
				amount: 1,
				country: 'US',
				targeted_locations: [ 'US' ],
			},
		] );
		await clearCompletedAdsSetup();
		await setupAdsAccounts.mockAdsAccountsResponse( [] );
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
				{
					level: 'High',
					country: 'FR',
					daily_budget: 20.5,
					metrics: {
						cost: 143.5,
						conversions: 2.5,
						conversions_value: 98.59,
					},
				},
				{
					level: 'Low',
					country: 'FR',
					daily_budget: 7,
					metrics: {
						cost: 49,
						conversions: 2,
						conversions_value: 80.48,
					},
				},
			],
		} );
		await setupBudgetPage.mockBudgetMetrics();
		await setupBudgetPage.mockAdsIncentiveCredits();
		await setupBudgetPage.mockMCConnected();
		await dashboardPage.mockRequests();
		await dashboardPage.goto();
	} );

	test.afterAll( async () => {
		await clearOnboardedMerchant();
		await page.close();
	} );

	test( 'Dashboard page contains Add campaign buttons', async () => {
		//Add page campaign in the programs section.
		await expect( dashboardPage.addPaidCampaignButton ).toBeEnabled();
	} );

	test.describe( 'With Ads account not connected', async () => {
		test.describe( 'Set up your accounts page', async () => {
			test.beforeAll( async () => {
				await setupAdsAccounts.mockAdsAccountsResponse( [] );
				await dashboardPage.addPaidCampaignButton.click();
				await page.waitForLoadState( LOAD_STATE.DOM_CONTENT_LOADED );
			} );

			test( 'Page header should be "Set up your accounts"', async () => {
				await expect(
					page.getByRole( 'heading', {
						name: 'Set up your accounts',
					} )
				).toBeVisible();
				await expect(
					page.getByText(
						'Connect your Google account and your Google Ads account to set up a Performance Max campaign.'
					)
				).toBeVisible();
			} );

			test( 'Google Account should show as connected', async () => {
				await expect(
					page.getByText(
						'This Google account is connected to your store’s product feed.'
					)
				).toBeVisible();
			} );

			test( 'Continue Button should be disabled', async () => {
				await expect(
					setupAdsAccounts.getContinueButton()
				).toBeDisabled();
			} );
		} );

		test.describe( 'Add campaigns with no Ads account', async () => {
			test( 'Create an account should be visible', async () => {
				const createAccountButton = page.getByRole( 'button', {
					name: 'Create account',
				} );

				await expect( createAccountButton ).toBeVisible();

				await expect(
					setupAdsAccounts.getContinueButton()
				).toBeDisabled();

				await expect(
					page.getByText(
						'Required to set up conversion measurement and create campaigns.'
					)
				).toBeVisible();

				await createAccountButton.click();
			} );

			test( 'Create account button should be disable if the ToS have not been accepted.', async () => {
				await expect(
					page.getByRole( 'heading', {
						name: 'Create Google Ads Account',
					} )
				).toBeVisible();

				await expect(
					page.getByText(
						'By creating a Google Ads account, you agree to the following terms and conditions:'
					)
				).toBeVisible();

				await expect(
					setupAdsAccounts.getCreateAdsAccountButtonModal()
				).toBeDisabled();
			} );

			test( 'Accept terms and conditions to enable the create account button', async () => {
				await setupAdsAccounts.getAcceptTermCreateAccount().check();

				await expect(
					setupAdsAccounts.getCreateAdsAccountButtonModal()
				).toBeEnabled();
			} );

			test( 'Create an Ads account', async () => {
				// Intercept Ads connection request.
				const connectAdsAccountRequest =
					setupAdsAccounts.registerConnectAdsAccountRequests();

				await setupAdsAccounts.mockAdsAccountsResponse( ADS_ACCOUNTS );

				// Mock request to fulfill Ads connection.
				await setupAdsAccounts.fulfillAdsConnection( {
					id: ADS_ACCOUNTS[ 0 ].id,
					currency: 'USD',
					symbol: '$',
					status: 'incomplete',
					step: 'account_access',
				} );

				await setupAdsAccounts.mockAdsStatusNotClaimed();

				await setupAdsAccounts.getCreateAdsAccountButtonModal().click();

				await connectAdsAccountRequest;

				const modal = setupAdsAccounts.getAcceptAccountModal();
				await expect( modal ).toBeVisible();
			} );

			test( 'Show Unclaimed Ads account', async () => {
				await setupAdsAccounts.clickCloseAcceptAccountButtonFromModal();

				const claimButton = setupAdsAccounts.getAdsClaimAccountButton();
				const claimText = setupAdsAccounts.getAdsClaimAccountText();

				await expect( claimButton ).toBeVisible();
				await expect( claimText ).toBeVisible();

				await expect(
					setupAdsAccounts.getContinueButton()
				).toBeDisabled();
			} );

			test( 'Show Claimed Ads account', async () => {
				// Intercept Ads connection request.
				await setupAdsAccounts.fulfillAdsConnection( {
					id: ADS_ACCOUNTS[ 0 ].id,
					currency: 'USD',
					symbol: '$',
					status: 'connected',
					step: '',
				} );

				await setupAdsAccounts.mockAdsStatusClaimed();

				await page.dispatchEvent( 'body', 'blur' );
				await page.dispatchEvent( 'body', 'focus' );

				await expect(
					setupAdsAccounts.getContinueButton()
				).toBeEnabled();

				await expect(
					page.getByRole( 'link', {
						name: String( ADS_ACCOUNTS[ 0 ].id ),
					} )
				).toBeVisible();

				await expect(
					setupAdsAccounts.getContinueButton()
				).toBeEnabled();
			} );
		} );

		test.describe( 'Add campaigns with existing Ads accounts', () => {
			test.beforeAll( async () => {
				await setupAdsAccounts.mockAdsAccountsResponse( ADS_ACCOUNTS );
				//Disconnect the account from the previous test
				setupAdsAccounts.fulfillAdsConnection( {
					id: ADS_ACCOUNTS[ 1 ].id,
					currency: 'EUR',
					symbol: '\u20ac',
					status: 'disconnected',
				} );

				await page.reload();
			} );

			test( 'Select one existing account', async () => {
				const adsAccountSelected = `${ ADS_ACCOUNTS[ 1 ].id }`;

				await setupAdsAccounts.selectAnExistingAdsAccount(
					adsAccountSelected
				);

				//Intercept Ads connection request
				const connectAdsAccountRequest =
					setupAdsAccounts.registerConnectAdsAccountRequests(
						adsAccountSelected
					);

				//Mock request to fulfill Ads connection
				setupAdsAccounts.fulfillAdsConnection( {
					id: ADS_ACCOUNTS[ 1 ].id,
					currency: 'EUR',
					symbol: '\u20ac',
					status: 'connected',
				} );

				await setupAdsAccounts.clickConnectAds();
				await connectAdsAccountRequest;

				await expect(
					setupAdsAccounts.getContinueButton()
				).toBeEnabled();
			} );
		} );

		test.describe( 'Create your campaign', () => {
			test( 'Continue to create your campaign', async () => {
				await setupAdsAccounts.clickContinue();
				await page.waitForLoadState( LOAD_STATE.DOM_CONTENT_LOADED );
				await expect(
					page.getByRole( 'heading', {
						name: 'Create your campaign',
					} )
				).toBeVisible();

				await expect(
					page.getByRole( 'heading', { name: 'Set your budget' } )
				).toBeVisible();

				await expect(
					page.getByRole( 'link', {
						name: 'See what your ads will look like.',
					} )
				).toBeVisible();
			} );

			test.describe( 'Preview product ad', () => {
				test( 'Preview product ad should be visible', async () => {
					await expect(
						page.getByText( 'Preview product ad' )
					).toBeVisible();
					await expect(
						page.getByText(
							"Each of your product variants will have its own ad. Previews shown here are examples and don't include all possible formats."
						)
					).toBeVisible();
				} );

				test( 'Change image buttons should be enabled', async () => {
					const buttonsToChangeImage = page.locator(
						'.gla-campaign-preview-card__moving-button'
					);

					expect( buttonsToChangeImage ).toHaveCount( 2 );

					for ( const button of await buttonsToChangeImage.all() ) {
						await expect( button ).toBeEnabled();
					}
				} );
			} );

			test.describe( 'FAQ panels', () => {
				test( 'should see five questions in FAQ', async () => {
					const faqTitles = getFAQPanelTitle( page );
					await expect( faqTitles ).toHaveCount( 5 );
				} );

				test( 'should not see FAQ rows when FAQ titles are not clicked', async () => {
					const faqRows = getFAQPanelRow( page );
					await expect( faqRows ).toHaveCount( 0 );
				} );

				// eslint-disable-next-line jest/expect-expect
				test( 'should see FAQ rows when all FAQ titles are clicked', async () => {
					await checkFAQExpandable( page );
				} );
			} );
		} );

		test.describe( 'Create Ads with billing data already setup', () => {
			test.describe( 'Set the budget', async () => {
				test( 'Continue button should be disabled if budget is 0', async () => {
					await setupBudgetPage.fillBudget( '0' );

					await expect(
						setupBudgetPage.getCreateCampaignButton()
					).toBeDisabled();
				} );

				test( 'Continue button should be enabled when selecting an option from the recommendations, even if the entered value is invalid', async () => {
					await setupBudgetPage.fillBudget( '0' );
					await expect(
						setupBudgetPage.getCreateCampaignButton()
					).toBeDisabled();

					await page.getByLabel( 'low' ).click();
					await expect(
						setupBudgetPage.getCreateCampaignButton()
					).toBeEnabled();

					await page.getByLabel( 'custom' ).click();
					await expect(
						setupBudgetPage.getCreateCampaignButton()
					).toBeDisabled();

					await page.getByLabel( 'high' ).click();
					await expect(
						setupBudgetPage.getCreateCampaignButton()
					).toBeEnabled();

					await page.getByLabel( 'custom' ).click();
					await expect(
						setupBudgetPage.getCreateCampaignButton()
					).toBeDisabled();

					await page.getByLabel( 'recommended' ).click();
					await expect(
						setupBudgetPage.getCreateCampaignButton()
					).toBeEnabled();
				} );

				test( 'Continue button should be disabled if budget is less than 30% of the daily budget baseline', async () => {
					await setupBudgetPage.fillBudget( '2' );

					await expect(
						setupBudgetPage.getCreateCampaignButton()
					).toBeDisabled();
				} );

				test( 'User is notified of the minimum value', async () => {
					await setupBudgetPage.fillBudget( '3' );
					await setupBudgetPage.getBudgetInput().blur();

					await expect(
						page.getByText(
							'Please make sure daily average cost is at least €4.00'
						)
					).toBeVisible();
				} );

				test( 'Continue button should be enabled if budget is above the recommended value', async () => {
					await setupBudgetPage.fillBudget( '5' );

					await expect(
						setupBudgetPage.getCreateCampaignButton()
					).toBeEnabled();
				} );

				test( 'Display the recommended budget if the budget is valid but lower than the lowest recommended value', async () => {
					await setupBudgetPage.fillBudget( '6' );

					await expect(
						page.getByText(
							`Your budget is lower than other advertisers' budgets, which may affect performance. For best results, we recommend at least €15.00 per day.`
						)
					).toBeVisible();
				} );
			} );

			test( 'It should show the campaign creation success message', async () => {
				await setupBudgetPage.fillBudget( '6' );
				await setupBudgetPage.getCreateCampaignButton().click();

				const cancelButton = page.getByRole( 'button', {
					name: 'Cancel',
				} );
				await expect(
					page.getByText( 'This offer won’t last long!' )
				).toBeVisible();
				await expect( cancelButton ).toBeEnabled();

				await cancelButton.click();

				await expect( cancelButton ).not.toBeVisible();

				// Mock the campaign creation request.
				const campaignCreation =
					setupBudgetPage.mockCampaignCreationAndAdsSetupCompletion(
						'6',
						[ 'US' ]
					);

				await setupBudgetPage.getCreateCampaignButton().click();

				await campaignCreation;

				//It should redirect to the dashboard page
				await page.waitForURL(
					'/wp-admin/admin.php?page=wc-admin&path=%2Fgoogle%2Fdashboard&guide=campaign-creation-success',
					{
						waitUntil: LOAD_STATE.DOM_CONTENT_LOADED,
					}
				);

				await expect(
					page.getByRole( 'heading', {
						name: "You've set up a Performance Max Campaign!",
					} )
				).toBeVisible();

				await expect(
					page.getByRole( 'button', {
						name: 'Create another campaign',
					} )
				).toBeEnabled();

				await expect(
					page.getByRole( 'button', {
						name: 'Got It',
					} )
				).toBeEnabled();

				await page
					.getByRole( 'button', {
						name: 'Got It',
					} )
					.click();

				await expect( page.getByRole( 'dialog' ) ).not.toBeVisible();
			} );
		} );
	} );

	test.describe( 'With connected Ads account', async () => {
		test.beforeAll( async () => {
			await setCompletedAdsSetup();
			await createCampaignPage.mockRequests();
			await createCampaignPage.mockOptimizeCampaignRequests();
			await createCampaignPage.mockGenerateTextAssetsSuccess();
			await createCampaignPage.mockGenerateImageAssetsSuccess();
			createCampaignPage.goto();
		} );

		test.afterAll( async () => {
			await clearCompletedAdsSetup();
			await page.close();
		} );

		test.describe( 'Create Campaign page', async () => {
			test( 'Page header should be "Create your campaign"', async () => {
				await expect(
					page.getByRole( 'heading', {
						name: 'Create your campaign',
					} )
				).toBeVisible();
				await expect(
					page.getByText(
						'Performance Max campaigns are automatically optimized for you by Google.'
					)
				).toBeVisible();
			} );

			test( 'Clicking the "Continue" button takes you to the "Optimize your campaign" step', async () => {
				const continueButton = createCampaignPage.getContinueButton();
				continueButton.click();

				await expect(
					page.getByRole( 'heading', {
						name: 'Optimize your campaign',
					} )
				).toBeVisible();
			} );
		} );

		test.describe( 'Optimize your campaign step', async () => {
			test( 'Final URL should be selected by default', async () => {
				const finalUrlCard = createCampaignPage.getFinalUrlCard();
				await expect( finalUrlCard ).toContainText(
					'https://woo.com/shop/'
				);
			} );

			test( 'Selecting the "Or, select a different Final URL" button disables the Create Campaign button', async () => {
				const selectDifferentFinalUrlButton =
					createCampaignPage.getSelectDifferentFinalUrlButton();
				await selectDifferentFinalUrlButton.click();

				const createCampaignButton =
					createCampaignPage.getCreateCampaignButton();
				await expect( createCampaignButton ).toBeDisabled();
			} );

			test( 'Selecting final URL enables Create Campaign button', async () => {
				await createCampaignPage.selectUrlOption();

				const createCampaignButton =
					createCampaignPage.getCreateCampaignButton();
				await expect( createCampaignButton ).toBeEnabled();
			} );

			test.describe( 'Gen AI', () => {
				test.describe( 'Text Assets', () => {
					test.describe( 'Headlines', () => {
						test.describe( 'Visibility', () => {
							test( 'Generate headline button is hidden when all inputs are filled', async () => {
								const generateHeadlineButton =
									createCampaignPage.getGenerateHeadlineButton();
								await expect(
									generateHeadlineButton
								).not.toBeVisible();
							} );

							test( 'Generate headline button is visible when at least one input is empty', async () => {
								const generateHeadlineButton =
									createCampaignPage.getGenerateHeadlineButton();
								await expect(
									generateHeadlineButton
								).not.toBeVisible();

								const headlineInputs =
									await createCampaignPage.getHeadlineInputs();
								const lastHeadlineInput = headlineInputs.last();
								await lastHeadlineInput.fill( '' );

								await expect(
									generateHeadlineButton
								).toBeVisible();
							} );
						} );

						test.describe( 'Generate action', () => {
							test.beforeEach( async () => {
								createCampaignPage.mockGenerateTextAssetsSuccess();
							} );

							test( 'Clicking generate headline sends the correct POST request', async () => {
								const generateRequest =
									createCampaignPage.awaitForGenerateTextRequest(
										'https://woo.com/shop/',
										[ 'headline' ]
									);

								const generateHeadlineButton =
									createCampaignPage.getGenerateHeadlineButton();
								await generateHeadlineButton.click();
								await generateRequest;
							} );
						} );

						test.describe( 'Success', () => {
							test( 'Clicking generate headline fills empty headline inputs', async () => {
								const headlineInputsValues =
									await createCampaignPage.getHeadlineInputsValues();
								const lastValue =
									headlineInputsValues[
										headlineInputsValues.length - 1
									];
								expect( lastValue ).toBe(
									'Fast Shipping Available'
								);
							} );
						} );
					} );

					test.describe( 'Long Headlines', () => {
						test.describe( 'Visibility', () => {
							test( 'Generate long headline button is hidden when all inputs are filled', async () => {
								const generateLongHeadlineButton =
									createCampaignPage.getGenerateLongHeadlineButton();
								await expect(
									generateLongHeadlineButton
								).not.toBeVisible();
							} );

							test( 'Generate long headline button is visible when at least one input is empty', async () => {
								const generateLongHeadlineButton =
									createCampaignPage.getGenerateLongHeadlineButton();
								await expect(
									generateLongHeadlineButton
								).not.toBeVisible();

								const longHeadlineInputs =
									await createCampaignPage.getLongHeadlineInputs();
								const lastLongHeadlineInput =
									longHeadlineInputs.last();
								await lastLongHeadlineInput.fill( '' );

								await expect(
									generateLongHeadlineButton
								).toBeVisible();
							} );
						} );

						test.describe( 'Generate action', () => {
							test.beforeEach( async () => {
								createCampaignPage.mockGenerateTextAssetsSuccess();
							} );

							test( 'Clicking generate long headline sends the correct POST request', async () => {
								const generateRequest =
									createCampaignPage.awaitForGenerateTextRequest(
										'https://woo.com/shop/',
										[ 'long_headline' ]
									);

								const generateLongHeadlineButton =
									createCampaignPage.getGenerateLongHeadlineButton();
								await generateLongHeadlineButton.click();
								await generateRequest;
							} );
						} );

						test.describe( 'Success', () => {
							test( 'Clicking generate long headline fills empty long headline inputs', async () => {
								const longHeadlineInputsValues =
									await createCampaignPage.getLongHeadlineInputsValues();
								const lastValue =
									longHeadlineInputsValues[
										longHeadlineInputsValues.length - 1
									];
								expect( lastValue ).toBe(
									'Upgrade your everyday shopping experience'
								);
							} );
						} );
					} );

					test.describe( 'Descriptions', () => {
						test.describe( 'Visibility', () => {
							test( 'Generate description button is hidden when all inputs are filled', async () => {
								const generateDescriptionButton =
									createCampaignPage.getGenerateDescriptionButton();
								await expect(
									generateDescriptionButton
								).not.toBeVisible();
							} );

							test( 'Generate description button is visible when at least one input is empty', async () => {
								const generateDescriptionButton =
									createCampaignPage.getGenerateDescriptionButton();
								await expect(
									generateDescriptionButton
								).not.toBeVisible();

								const descriptionInputs =
									await createCampaignPage.getDescriptionInputs();
								const lastDescriptionInput =
									descriptionInputs.last();
								await lastDescriptionInput.fill( '' );

								await expect(
									generateDescriptionButton
								).toBeVisible();
							} );
						} );

						test.describe( 'Generate action', () => {
							test.beforeEach( async () => {
								createCampaignPage.mockGenerateTextAssetsSuccess();
							} );

							test( 'Clicking generate description sends the correct POST request', async () => {
								const generateRequest =
									createCampaignPage.awaitForGenerateTextRequest(
										'https://woo.com/shop/',
										[ 'description' ]
									);

								const generateDescriptionButton =
									createCampaignPage.getGenerateDescriptionButton();
								await generateDescriptionButton.click();
								await generateRequest;
							} );
						} );

						test.describe( 'Success', () => {
							test( 'Clicking generate description fills empty description inputs', async () => {
								const descriptionInputsValues =
									await createCampaignPage.getDescriptionInputsValues();
								const lastValue =
									descriptionInputsValues[
										descriptionInputsValues.length - 1
									];
								expect( lastValue ).toBe(
									'Browse top picks and enjoy exclusive savings.'
								);
							} );
						} );
					} );

					test.describe( 'AI Icon', () => {
						test( 'is visible next to generated text assets and not visible if changed', async () => {
							const descriptionInputs =
								createCampaignPage.getDescriptionInputs();
							const lastDescriptionInput =
								descriptionInputs.last();

							// Move one level up
							const row = lastDescriptionInput.locator( '..' );
							const aiIcon = row.locator(
								'.gla-texts-editor__ai-icon'
							);

							await expect( aiIcon ).toHaveCount( 1 );

							await lastDescriptionInput.fill(
								'Custom description text'
							);

							await expect( aiIcon ).toHaveCount( 0 );
						} );
					} );

					test.describe( 'Error', () => {
						test.beforeEach( async () => {
							createCampaignPage.mockEmptyGenerateTextAssets();
						} );

						test( 'Displays error message when there are no more generated text', async () => {
							const descriptionInputs =
								await createCampaignPage.getDescriptionInputs();
							const lastDescriptionInput =
								descriptionInputs.last();
							await lastDescriptionInput.fill( '' );

							const generateDescriptionButton =
								createCampaignPage.getGenerateDescriptionButton();
							await generateDescriptionButton.click();

							await checkSnackBarMessage(
								page,
								'No texts were generated. Please try again.'
							);
						} );
					} );
				} );

				test.describe( 'Image Assets', () => {
					test.describe( 'Landscape images', () => {
						test.describe( 'Visibility', () => {
							test( 'Generate landscape images button is visible', async () => {
								const generateLandscapeImagesButton =
									createCampaignPage.getGenerateLandscapeImagesButton();
								await expect(
									generateLandscapeImagesButton
								).toBeVisible();
							} );

							test( 'There is only one image loaded for the campaign', async () => {
								const campaignImages =
									createCampaignPage.getCampaignLandscapeImageItems();
								await expect( campaignImages ).toHaveCount( 1 );
							} );
						} );

						test.describe( 'Generate action', () => {
							test.beforeEach( async () => {
								createCampaignPage.mockGenerateImageAssetsSuccess();
							} );

							test( 'Clicking generate landscape images sends the correct POST request', async () => {
								const generateRequest =
									createCampaignPage.awaitForGenerateImageRequest(
										'https://woo.com/shop/',
										[ 'marketing_image' ]
									);

								const generateLandscapeImagesButton =
									createCampaignPage.getGenerateLandscapeImagesButton();
								await generateLandscapeImagesButton.click();
								await generateRequest;
							} );
						} );

						test.describe( 'Success', () => {
							test( 'Image picker is displayed', async () => {
								const imagePicker =
									createCampaignPage.getLandscapeImagesSectionImagePicker();
								await expect( imagePicker ).toBeVisible();
							} );

							test( 'Image picker renders generated images', async () => {
								const generatedImages =
									createCampaignPage.getLandscapeGeneratedImages();
								await expect( generatedImages ).toHaveCount(
									4
								);
							} );
						} );
					} );

					test.describe( 'Image Picker', () => {
						test.beforeEach( async () => {
							createCampaignPage.mockGenerateImageAssetsSuccess();
						} );

						test( '"Add selected images" button is disabled if no image is selected', async () => {
							const generateLandscapeImagesButton =
								createCampaignPage.getGenerateLandscapeImagesButton();
							await generateLandscapeImagesButton.click();

							const addSelectedImagesButton =
								createCampaignPage.getLandscapeImagePickerAddSelectedImagesButton();
							await expect(
								addSelectedImagesButton
							).toBeDisabled();
						} );

						test( 'Clicking an image enables the "Add selected images" button', async () => {
							const generatedImages =
								createCampaignPage.getLandscapeGeneratedImages();
							generatedImages.first().click();

							const addSelectedImagesButton =
								createCampaignPage.getLandscapeImagePickerAddSelectedImagesButton();
							await expect(
								addSelectedImagesButton
							).toBeEnabled();
						} );

						test( 'Clicking the "Add selected images" button adds the selected images to the campaign and remove them from the image picker ', async () => {
							let generatedImages =
								createCampaignPage.getLandscapeGeneratedImages();
							const firstGeneratedImageUrl = await generatedImages
								.first()
								.locator( 'img' )
								.getAttribute( 'src' );
							const addSelectedImagesButton =
								createCampaignPage.getLandscapeImagePickerAddSelectedImagesButton();
							await addSelectedImagesButton.click();

							generatedImages =
								createCampaignPage.getLandscapeGeneratedImages();
							await expect( generatedImages ).toHaveCount( 3 );

							const campaignImages =
								createCampaignPage.getCampaignLandscapeImageItems();
							const campaignLastImageUrl = await campaignImages
								.last()
								.locator( 'img' )
								.getAttribute( 'src' );
							expect( campaignLastImageUrl ).toEqual(
								firstGeneratedImageUrl
							);
							await expect( campaignImages ).toHaveCount( 2 );
						} );

						test( 'Adding all generated images hides the image picker', async () => {
							const generatedImages =
								createCampaignPage.getLandscapeGeneratedImages();
							await generatedImages.nth( 0 ).click();
							await generatedImages.nth( 1 ).click();
							await generatedImages.nth( 2 ).click();

							const addSelectedImagesButton =
								createCampaignPage.getLandscapeImagePickerAddSelectedImagesButton();
							await addSelectedImagesButton.click();

							const imagePicker =
								createCampaignPage.getLandscapeImagesSectionImagePicker();
							await expect( imagePicker ).not.toBeVisible();

							const campaignImages =
								createCampaignPage.getCampaignLandscapeImageItems();
							await expect( campaignImages ).toHaveCount( 5 );
						} );

						test( 'Removing an image from the campaign shows it back in the image picker', async () => {
							const campaignImageItems =
								createCampaignPage.getCampaignLandscapeImageItems();
							const lastCampaignImageItem =
								campaignImageItems.last();
							await lastCampaignImageItem.hover();
							const lastCampaignImageUrl =
								await lastCampaignImageItem
									.locator( 'img' )
									.getAttribute( 'src' );

							const removeButton = lastCampaignImageItem.locator(
								'.gla-media-selector__remove-medium-button'
							);
							await removeButton.click();

							const imagePicker =
								createCampaignPage.getLandscapeImagesSectionImagePicker();
							await expect( imagePicker ).toBeVisible();

							const generatedImages =
								createCampaignPage.getLandscapeGeneratedImages();
							const firstGeneratedImageUrl = await generatedImages
								.first()
								.locator( 'img' )
								.getAttribute( 'src' );
							expect( firstGeneratedImageUrl ).toEqual(
								lastCampaignImageUrl
							);
						} );
					} );
				} );

				test.describe( 'Square images', () => {
					test.describe( 'Visibility', () => {
						test( 'Generate square images button is visible', async () => {
							const generateSquareImagesButton =
								createCampaignPage.getGenerateSquareImagesButton();
							await expect(
								generateSquareImagesButton
							).toBeVisible();
						} );

						test( 'There is only one image loaded for the campaign', async () => {
							const campaignImages =
								createCampaignPage.getCampaignSquareImageItems();
							await expect( campaignImages ).toHaveCount( 1 );
						} );
					} );

					test.describe( 'Generate action', () => {
						test.beforeEach( async () => {
							createCampaignPage.mockGenerateImageAssetsSuccess();
						} );

						test( 'Clicking generate square images sends the correct POST request', async () => {
							const generateRequest =
								createCampaignPage.awaitForGenerateImageRequest(
									'https://woo.com/shop/',
									[ 'square_marketing_image' ]
								);

							const generateSquareImagesButton =
								createCampaignPage.getGenerateSquareImagesButton();
							await generateSquareImagesButton.click();
							await generateRequest;
						} );
					} );

					test.describe( 'Success', () => {
						test( 'Image picker is displayed', async () => {
							const imagePicker =
								createCampaignPage.getSquareImagesSectionImagePicker();
							await expect( imagePicker ).toBeVisible();
						} );

						test( 'Image picker renders generated images', async () => {
							const generatedImages =
								createCampaignPage.getSquareGeneratedImages();
							await expect( generatedImages ).toHaveCount( 3 );
						} );
					} );
				} );

				test.describe( 'Portrait images', () => {
					test.describe( 'Visibility', () => {
						test( 'Generate portrait images button is visible', async () => {
							const generatePortraitImagesButton =
								createCampaignPage.getGeneratePortraitImagesButton();
							await expect(
								generatePortraitImagesButton
							).toBeVisible();
						} );

						test( 'There are no images loaded for the campaign', async () => {
							const campaignImages =
								createCampaignPage.getCampaignPortraitImageItems();
							await expect( campaignImages ).toHaveCount( 0 );
						} );
					} );

					test.describe( 'Generate action', () => {
						test.beforeEach( async () => {
							createCampaignPage.mockGenerateImageAssetsSuccess();
						} );

						test( 'Clicking generate portrait images sends the correct POST request', async () => {
							const generateRequest =
								createCampaignPage.awaitForGenerateImageRequest(
									'https://woo.com/shop/',
									[ 'portrait_marketing_image' ]
								);

							const generatePortraitImagesButton =
								createCampaignPage.getGeneratePortraitImagesButton();
							await generatePortraitImagesButton.click();
							await generateRequest;
						} );
					} );

					test.describe( 'Success', () => {
						test( 'Image picker is displayed', async () => {
							const imagePicker =
								createCampaignPage.getPortraitImagesSectionImagePicker();
							await expect( imagePicker ).toBeVisible();
						} );

						test( 'Image picker renders generated images', async () => {
							const generatedImages =
								createCampaignPage.getPortraitGeneratedImages();
							await expect( generatedImages ).toHaveCount( 2 );
						} );
					} );
				} );
			} );
		} );
	} );
} );
