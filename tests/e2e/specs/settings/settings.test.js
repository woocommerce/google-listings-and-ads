/**
 * External dependencies
 */
import { expect, test } from '@playwright/test';

/**
 * Internal dependencies
 */
import {
	clearOnboardedMerchant,
	clearServiceBasedMerchant,
	setOnboardedMerchant,
	setServiceBasedMerchant,
} from '../../utils/api';
import SettingsPage from '../../utils/pages/settings';

test.use( { storageState: process.env.ADMINSTATE } );

test.describe.configure( { mode: 'serial' } );

/**
 * @type {import('../../utils/pages/settings.js').default} settingsPage
 */
let settingsPage = null;

/**
 * @type {import('@playwright/test').Page} page
 */
let page = null;

test.describe( 'Settings', () => {
	test.beforeAll( async ( { browser } ) => {
		page = await browser.newPage();
		settingsPage = new SettingsPage( page );

		await setOnboardedMerchant();
		await settingsPage.mockRequests();
	} );

	test.afterAll( async () => {
		await clearOnboardedMerchant();
		await page.close();
	} );

	test.describe( 'Tax rate setup', () => {
		test( 'Should not show the setup when selling in regions unrelated to the US', async () => {
			// Mock the country where the store is located as outside of the US.
			const once = settingsPage.withFulfillTimes( 1 );
			await once.fulfillRequest(
				// Having`(\w+%2C)*` is because multiple option queries may be consolidated into a single request.
				/\/wc-admin\/options\?options=(\w+%2C)*woocommerce_default_country\b/,
				{ woocommerce_default_country: 'JP' }
			);
			await settingsPage.mockTargetAudienceCountries( 'JP' );
			await settingsPage.goto();

			await expect(
				page.getByRole( 'heading', { name: 'Settings' } )
			).toBeVisible();

			await expect(
				page.locator( '.woocommerce-spinner' ).first()
			).not.toBeVisible();

			await expect(
				page.getByText( 'Tax rate (required for U.S. only)' )
			).not.toBeVisible();
		} );

		test( 'Should show the setup when selling to the US and can update the setting', async () => {
			await settingsPage.mockTargetAudienceCountries();
			await settingsPage.goto();

			await expect(
				page.getByText( 'Tax rate (required for U.S. only)' )
			).toBeVisible();

			const taxRateSection = settingsPage.getTaxRateSection();
			const option = taxRateSection.getByRole( 'radio', {
				checked: false,
			} );
			const optionValue = option.getAttribute( 'value' );

			await option.check();

			// Reload to assert the setting has been actually saved.
			await page.reload();
			await expect(
				taxRateSection.getByRole( 'radio', { checked: true } )
			).toHaveAttribute( 'value', optionValue );
		} );
	} );

	test.describe( 'Enhanced Conversions Setting', () => {
		test.describe( 'When ads account is connected', () => {
			test( 'should show the "Enhanced Conversion" setting card', async () => {
				await expect(
					page.getByRole( 'heading', { name: 'Settings' } )
				).toBeVisible();
				await expect(
					page.getByRole( 'heading', {
						name: 'Improve conversion accuracy',
					} )
				).toBeVisible();
			} );

			test( 'checkbox should be unchecked by default', async () => {
				const checkbox = settingsPage.getEnhancedConversionsCheckbox();

				await expect( checkbox ).not.toBeChecked();
			} );

			test( 'checkbox should be checked when the setting is enabled', async () => {
				await settingsPage.mockEnhancedConversionsStatus( true );
				await page.reload();

				const checkbox = settingsPage.getEnhancedConversionsCheckbox();

				await expect( checkbox ).toBeChecked();
			} );

			test( 'should send POST request to disable Enhanced Conversions when enabled with the correct payload', async () => {
				const requestPromise =
					settingsPage.registerEnhancedConversionsStatusRequests();

				await settingsPage.mockEnhancedConversionsStatus( false, [
					'POST',
				] );

				const checkbox = settingsPage.getEnhancedConversionsCheckbox();
				await expect( checkbox ).toBeChecked();

				await checkbox.click();

				const request = await requestPromise;
				const requestPayload = await request.postDataJSON();
				const response = await request.response();
				const responseBody = await response.json();
				const payload = {
					enhanced_conversions_enabled: false,
				};

				expect( requestPayload ).toEqual( payload );
				expect( responseBody ).toEqual( payload );
			} );

			test( 'should show the "Enhanced Conversion" setting saved success notice', async () => {
				// Get the notice with class 'components-notice is-success'
				const notice = page.locator(
					'.components-snackbar:has-text("Enhanced Conversions status updated successfully.")'
				);
				await expect( notice ).toBeVisible();
			} );
		} );

		test.describe( 'When ads account is not connected', () => {
			test.beforeAll( async () => {
				await settingsPage.mockAdsAccountDisconnected();
				await settingsPage.goto();
			} );

			test( 'should show the "Enhanced Conversion" setting card', async () => {
				await expect(
					page.getByRole( 'heading', { name: 'Settings' } )
				).toBeVisible();
				await expect(
					page.getByRole( 'heading', {
						name: 'Improve conversion accuracy',
					} )
				).toBeVisible();
			} );

			test( 'checkbox should be unchecked and disabled by default', async () => {
				const checkbox = settingsPage.getEnhancedConversionsCheckbox();

				await expect( checkbox ).not.toBeChecked();
				await expect( checkbox ).toBeDisabled();
			} );

			test( 'should show the message that Google Ads account is not connected', async () => {
				await expect(
					page.getByText(
						'Please connect your Google Ads account in order to use Enhanced Conversions data.'
					)
				).toBeVisible();
			} );
		} );
	} );

	test.describe( 'Accounts subtab', () => {
		test.beforeAll( async () => {
			await settingsPage.mockAdsAccountConnected();
			await settingsPage.mockMCConnected();
			await settingsPage.mockYouTubeAccountNotConnected();
			await settingsPage.gotoAccounts();
			await page
				.getByRole( 'button', {
					name: 'Disconnect from all accounts',
				} )
				.waitFor();
		} );

		test( 'should render the redesigned account-management UI', async () => {
			const accountTitles = page.locator( '.gla-account-row__title' );

			await expect( page ).toHaveURL(
				/path=%2Fgoogle%2Fsettings&section=accounts$/
			);
			await expect(
				page.getByRole( 'heading', { name: 'Required' } )
			).toBeVisible();
			await expect(
				page.getByText( 'The extension needs these to run.' )
			).toBeVisible();
			await expect(
				page.getByRole( 'heading', { name: 'Grow your reach' } )
			).toBeVisible();
			await expect(
				page.getByText(
					'Optional. Connect more Google services to your store.'
				)
			).toBeVisible();
			await expect(
				accountTitles.filter( { hasText: /^WordPress\.com$/ } )
			).toBeVisible();
			await expect(
				accountTitles.filter( { hasText: /^Google$/ } )
			).toBeVisible();
			await expect(
				accountTitles.filter( { hasText: /^Merchant Center$/ } )
			).toBeVisible();
			await expect(
				accountTitles.filter( { hasText: /^Google Ads$/ } )
			).toBeVisible();
			await expect(
				accountTitles.filter( { hasText: /^YouTube$/ } )
			).toBeVisible();
			await expect(
				page.getByRole( 'button', {
					name: 'Disconnect from all accounts',
				} )
			).toBeVisible();
		} );

		test( 'should not render General settings content on the Accounts subtab', async () => {
			await expect(
				page.getByRole( 'heading', {
					name: 'Improve conversion accuracy',
				} )
			).toHaveCount( 0 );
			await expect(
				page.getByRole( 'checkbox', {
					name: 'Send Enhanced Conversions data to Google Ads',
				} )
			).toHaveCount( 0 );
			await expect(
				page.getByRole( 'heading', { name: 'Contact information' } )
			).toHaveCount( 0 );
			await expect(
				page.getByText( 'Tax rate (required for U.S. only)' )
			).toHaveCount( 0 );
		} );

		test( 'should request a YouTube connection from the Accounts subtab', async () => {
			await settingsPage.mockYouTubeAccountNotConnected();
			await settingsPage
				.withFulfillTimes( 1 )
				.mockYouTubeConnect(
					'/wp-admin/admin.php?page=wc-admin&path=%2Fgoogle%2Fsettings&section=accounts&from-connect=1'
				);
			await settingsPage.gotoAccounts();

			const requestPromise = settingsPage.registerYouTubeConnectRequest();

			await settingsPage.getYouTubeConnectButton().click();

			const request = await requestPromise;

			expect( request.url() ).toContain( 'next_page_name=setup-youtube' );
			await expect( page ).toHaveURL(
				/path=%2Fgoogle%2Fsettings&section=accounts&from-connect=1$/
			);
			await expect(
				page.getByRole( 'button', {
					name: 'Disconnect from all accounts',
				} )
			).toBeVisible();
		} );
	} );

	test.describe( 'YouTube Shopping', () => {
		test.describe( 'when account is not connected', () => {
			test( 'should show connect button when account is not connected', async () => {
				await settingsPage.mockYouTubeAccountNotConnected();
				await settingsPage.gotoAccounts();

				await expect(
					settingsPage.getYouTubeConnectButton()
				).toBeVisible();

				await page.unroute( /\/wc\/gla\/youtube\/connection\b/ );
			} );
		} );

		test.describe( 'when account is connected', () => {
			test.beforeAll( async () => {
				await settingsPage.mockYouTubeAccountConnected();
				await settingsPage.gotoAccounts();
			} );

			test.afterAll( async () => {
				await page.unroute( /\/wc\/gla\/youtube\/connection\b/ );
			} );

			test( 'should show the channel name and disconnect button when account is connected', async () => {
				await expect(
					settingsPage.youTubeAccountRow.getByText(
						'My YouTube Channel'
					)
				).toBeVisible();
				await expect(
					settingsPage.getYouTubeAccountActionsButton()
				).toBeVisible();
			} );

			test( 'should disconnect YouTube account and show Connect button', async () => {
				await settingsPage.mockYouTubeAccountNotConnected();
				await settingsPage.mockYouTubeDisconnect();
				await page.evaluate( () => {
					window.__youtubeDisconnectMarker = 'persisted';
				} );

				const requestPromise =
					settingsPage.registerYouTubeDisconnectRequest();

				await settingsPage.getYouTubeAccountActionsButton().click();
				await settingsPage.getYouTubeDisconnectMenuItem().click();
				await page
					.getByRole( 'checkbox', {
						name: 'Yes, I want to disconnect my YouTube account.',
					} )
					.check();
				await page
					.getByRole( 'button', {
						name: 'Disconnect YouTube account',
					} )
					.click();

				await requestPromise;

				await expect(
					settingsPage.getYouTubeConnectButton()
				).toBeVisible();
				await expect
					.poll( () =>
						page.evaluate( () => window.__youtubeDisconnectMarker )
					)
					.toBe( 'persisted' );
			} );
		} );

		test.describe( 'when account setup is incomplete', () => {
			test.beforeAll( async () => {
				await settingsPage.mockYouTubeAccountIncomplete();
				await settingsPage.gotoAccounts();
			} );

			test.afterAll( async () => {
				await page.unroute( /\/wc\/gla\/youtube\/setup\/complete\b/ );
				await page.unroute( /\/wc\/gla\/youtube\/connection\b/ );
			} );

			test( 'should show a notice if the YouTube account is incomplete', async () => {
				await expect(
					settingsPage.youTubeAccountRow.getByText(
						'Your YouTube account is connected, but setup isn’t complete yet.'
					)
				).toBeVisible();
			} );

			test( 'should display error message when "Complete setup" fails', async () => {
				await settingsPage.mockNotEligibleYouTubeChannel();

				const requestPromise =
					settingsPage.registerYouTubeCompleteSetupRequest();

				await settingsPage.getYouTubeCompleteSetupButton().click();

				await requestPromise;

				await expect(
					settingsPage.youTubeAccountRow.getByText(
						'The channel is not eligible for the linking program.'
					)
				).toBeVisible();
			} );

			test( 'should complete YouTube account setup successfully', async () => {
				await settingsPage.mockEligibleYouTubeChannel();
				// Reload so the page starts from the clean incomplete state.
				await settingsPage.gotoAccounts();

				const requestPromise =
					settingsPage.registerYouTubeCompleteSetupRequest();

				await settingsPage.getYouTubeCompleteSetupButton().click();

				await requestPromise;

				await settingsPage.mockYouTubeAccountConnected();
				await settingsPage.gotoAccounts();

				await expect(
					settingsPage.youTubeAccountRow.getByText(
						'My YouTube Channel'
					)
				).toBeVisible();
			} );
		} );

		test( 'should auto-complete YouTube setup after returning from OAuth to the Accounts tab', async () => {
			await settingsPage.mockYouTubeAccountConnected();
			await settingsPage
				.withFulfillTimes( 1 )
				.mockYouTubeAccountIncomplete();

			try {
				await settingsPage.mockEligibleYouTubeChannel();

				const requestPromise =
					settingsPage.registerYouTubeCompleteSetupRequest();

				await settingsPage.gotoAccounts( 'youtube=connected' );

				await requestPromise;

				await expect(
					settingsPage.youTubeAccountRow.getByText(
						'My YouTube Channel'
					)
				).toBeVisible();
				await expect( page ).toHaveURL( ( url ) => {
					return (
						url.searchParams.get( 'path' ) === '/google/settings' &&
						url.searchParams.get( 'section' ) === 'accounts' &&
						! url.searchParams.has( 'youtube' )
					);
				} );
			} finally {
				await page.unroute( /\/wc\/gla\/youtube\/setup\/complete\b/ );
				await page.unroute( /\/wc\/gla\/youtube\/connection\b/ );
			}
		} );
	} );

	test.describe( 'Connected Google Merchant Center account', () => {
		test( 'should not show the Audience section', async () => {
			await expect(
				page.getByRole( 'heading', { name: 'Audience' } )
			).not.toBeVisible();
		} );
	} );

	test.describe( 'No connected Google Merchant Center account', () => {
		test.beforeAll( async () => {
			await setServiceBasedMerchant();
			await settingsPage.mockJetpackConnected();
			await settingsPage.mockGoogleConnected();
			await settingsPage.mockAdsAccountConnected();
			await settingsPage.mockMCNotConnected();
			await settingsPage.mockTargetAudienceCountries();
			await settingsPage.goto();
		} );

		test.afterAll( async () => {
			await clearServiceBasedMerchant();
		} );

		test( 'should not show Google Merchant Center account card', async () => {
			// There should not be a `gla-account-card` with text 'Google Merchant Center'.
			const gmcCard = page.locator(
				'.gla-account-card:has-text("Google Merchant Center")'
			);
			await expect( gmcCard ).not.toBeVisible();
		} );

		test( 'should not show the tax rate setup section', async () => {
			await expect(
				page.getByText( 'Tax rate (required for U.S. only)' )
			).not.toBeVisible();
		} );

		test( 'should hide YouTube on the Accounts tab until Merchant Center is connected', async () => {
			await settingsPage.gotoAccounts();

			await page
				.getByRole( 'button', { name: 'Disconnect from all accounts' } )
				.waitFor();

			await expect(
				page.getByRole( 'button', { name: 'Set up Merchant Center' } )
			).toBeVisible();
			await expect( settingsPage.youTubeAccountRow ).not.toBeVisible();
			await expect(
				page.getByRole( 'heading', { name: 'Grow your reach' } )
			).not.toBeVisible();

			await settingsPage.goto();
		} );

		test( 'should show the Audience section', async () => {
			await expect(
				page.getByRole( 'heading', { name: 'Audience' } )
			).toBeVisible();
		} );

		test( 'should show Location subsection with country selection options', async () => {
			const sectionTitle = page.locator(
				'.gla-subsection-title:has-text("Location")'
			);
			await expect( sectionTitle ).toBeVisible();
			await expect(
				page.getByRole( 'radio', { name: 'Selected countries only' } )
			).toBeVisible();
			await expect(
				page.getByRole( 'radio', { name: 'All countries' } )
			).toBeVisible();
		} );

		test( 'should send POST request to save endpoint when updating audience settings', async () => {
			const requestPromise =
				settingsPage.registerTargetAudienceSaveRequests();

			await settingsPage.fulfillTargetAudience( { location: 'all' }, [
				'POST',
			] );

			const audienceSection = page.locator(
				'.gla-choose-audience-section'
			);

			const allCountriesRadioBox =
				audienceSection.getByLabel( 'All countries' );
			await allCountriesRadioBox.check();

			await expect( allCountriesRadioBox ).toBeChecked();

			const request = await requestPromise;
			const requestPayload = await request.postDataJSON();

			expect( requestPayload ).toHaveProperty( 'location', 'all' );
		} );
	} );
} );
