/**
 * Internal dependencies
 */
import SetUpAccountsPage from '../../utils/pages/setup-mc/step-1-set-up-accounts.js';

/**
 * External dependencies
 */
const { test, expect } = require( '@playwright/test' );

test.use( { storageState: process.env.ADMINSTATE } );

test.describe.configure( { mode: 'serial' } );

/**
 * @type {import('../../utils/pages/setup-mc/step-1-set-up-accounts.js').default} setUpAccountsPage
 */
let setUpAccountsPage = null;

/**
 * @type {import('@playwright/test').Page} page
 */
let page = null;

test.describe( 'Set up accounts', () => {
	test.beforeAll( async ( { browser } ) => {
		page = await browser.newPage();
		setUpAccountsPage = new SetUpAccountsPage( page );
	} );

	test.afterAll( async () => {
		await setUpAccountsPage.closePage();
	} );

	test( 'should see accounts step header, "Connect your WordPress.com account" & connect button', async () => {
		await setUpAccountsPage.goto();

		await expect(
			page.getByRole( 'heading', { name: 'Set up your accounts' } )
		).toBeVisible();

		await expect(
			page.getByText(
				'Connect the accounts required to use Google Listings & Ads.'
			)
		).toBeVisible();

		await expect(
			page.getByRole( 'button', { name: 'Connect' } ).first()
		).toBeEnabled();
	} );

	test.describe( 'Connect WordPress.com account', () => {
		test( 'should send an API request to connect Jetpack, and redirect to the returned URL', async ( { baseURL } ) => {
			// Mock Jetpack connect
			await setUpAccountsPage.mockJetpackConnect( baseURL + 'auth_url' );

			// Click the enabled connect button.
			page.locator( "//button[text()='Connect'][not(@disabled)]" ).click();
			await page.waitForLoadState( 'networkidle' );

			// Expect the user to be redirected
			await page.waitForURL( baseURL + 'auth_url' );
		} );
	} );

	test.describe( 'Connect Google account', () => {
		test.beforeAll( async () => {
			// Mock Jetpack as connected
			await setUpAccountsPage.mockJetpackConnected( 'Test user', 'jetpack@example.com' );

			// Mock google as not connected.
			// When pending even WPORG will not render yet.
			// If not mocked will fail and render nothing,
			// as Jetpack is mocked only on the client-side.
			await setUpAccountsPage.mockGoogleNotConnected();

			await setUpAccountsPage.goto();

			page.getByRole( 'heading', { name: 'Set up your accounts' } );
		} );

		test( 'should see their WPORG email, "Google" title & connect button', async () => {
			const jetpackDescriptionRow = setUpAccountsPage.getJetpackDescriptionRow();
			await expect( jetpackDescriptionRow  ).toContainText( 'jetpack@example.com' );

			await expect(
				page.getByText( 'Google', { exact: true } )
			).toBeVisible();

			await expect(
				page.getByRole( 'button', { name: 'Connect' } ).first()
			).toBeEnabled();
		} );

		test( 'after clicking the "Connect your Google account" button should send an API request to connect Google account, and redirect to the returned URL', async ( { baseURL } ) => {
			// Mock google connect.
			await setUpAccountsPage.mockGoogleConnect( baseURL + 'google_auth' );

			// Click the enabled connect button
			page.locator( "//button[text()='Connect'][not(@disabled)]" ).click();
			await page.waitForLoadState( 'networkidle' );

			// Expect the user to be redirected
			await page.waitForURL( baseURL + 'google_auth' );
		} );
	} );

	test.describe( 'Connect Merchant Center account', () => {
		test.beforeAll( async () => {

			await Promise.all( [
				// Mock Jetpack as connected.
				setUpAccountsPage.mockJetpackConnected( 'Test user', 'jetpack@example.com' ),

				// Mock google as connected.
				setUpAccountsPage.mockGoogleConnected( 'google@example.com' ),

				// Mock merchant center as not connected.
				setUpAccountsPage.mockMCNotConnected(),
			] );
		} );

		test.describe( 'Create a new Merchant Center account', () => {
			test.beforeAll( async () => {
				// Mock merchant center has no accounts
				await setUpAccountsPage.mockMCHasNoAccounts();
				await setUpAccountsPage.goto();
			} );

			test( 'should see their WPORG email, Google email, "Google Merchant Center" title & "Create account" button', async () => {
				const jetpackDescriptionRow = setUpAccountsPage.getJetpackDescriptionRow();
				await expect( jetpackDescriptionRow  ).toContainText( 'jetpack@example.com' );

				const googleDescriptionRow = setUpAccountsPage.getGoogleDescriptionRow();
				await expect( googleDescriptionRow  ).toContainText( 'google@example.com' );

				const mcTitleRow = setUpAccountsPage.getMCTitleRow();
				await expect( mcTitleRow ).toContainText( 'Google Merchant Center' );

				const createAccountButton = setUpAccountsPage.getMCCreateAccountButtonFromPage();
				await expect( createAccountButton ).toBeEnabled();
			} );

			test( 'click "Create account" button should see the modal of confirmation of creating account', async () => {
				// Click the create account button
				const createAccountButton = setUpAccountsPage.getMCCreateAccountButtonFromPage();
				await createAccountButton.click()
				await page.waitForLoadState( 'domcontentloaded' );

				const modalHeader = setUpAccountsPage.getModalHeader();
				await expect( modalHeader ).toContainText( 'Create Google Merchant Center Account' );

				const modalCheckbox = setUpAccountsPage.getModalCheckbox();
				await expect( modalCheckbox ).toBeEnabled();

				const createAccountButtonFromModal = setUpAccountsPage.getMCCreateAccountButtonFromModal();
				await expect( createAccountButtonFromModal ).toBeDisabled();

				// Click the checkbox of accepting ToS, the create account button will be enabled.
				await modalCheckbox.click();
				await expect( createAccountButtonFromModal ).toBeEnabled();
			} );

			test.describe( 'click "Create account" button from the modal', () => {
				test( 'should see Merchant Center "Connected" when the website is not claimed', async ( { baseURL } ) => {
					await Promise.all( [
						// Mock Merchant Center create accounts
						setUpAccountsPage.mockMCCreateAccountWebsiteNotClaimed(),

						// Mock Merchant Center as connected with ID 12345
						setUpAccountsPage.mockMCConnected( 12345 ),
					] );

					const createAccountButtonFromModal = setUpAccountsPage.getMCCreateAccountButtonFromModal();
					await createAccountButtonFromModal.click();
					await page.waitForLoadState( 'networkidle' );
					const mcConnectedLabel = setUpAccountsPage.getMCConnectedLabel();
					await expect( mcConnectedLabel ).toContainText( 'Connected' );

					const host = new URL( baseURL ).host;
					const mcDescriptionRow = setUpAccountsPage.getMCDescriptionRow();
					await expect( mcDescriptionRow ).toContainText( `${host} (12345)` );
				} );

				test.describe( 'when the website is already claimed', () => {
					test( 'should see "Reclaim my URL" button, "Switch account" button, and site URL input', async ( { baseURL } ) => {
						const host = new URL( baseURL ).host;

						await Promise.all( [
							// Mock merchant center has no accounts
							setUpAccountsPage.mockMCHasNoAccounts(),

							// Mock Merchant Center as not connected
							setUpAccountsPage.mockMCNotConnected(),
						] );

						await setUpAccountsPage.goto();

						// Mock Merchant Center create accounts
						await setUpAccountsPage.mockMCCreateAccountWebsiteClaimed( 12345, host );

						// Click "Create account" button from the page.
						const createAccountButton = setUpAccountsPage.getMCCreateAccountButtonFromPage();
						await createAccountButton.click();
						await page.waitForLoadState( 'domcontentloaded' );

						// Check the checkbox to accept ToS.
						const modalCheckbox = setUpAccountsPage.getModalCheckbox();
						await modalCheckbox.click();

						// Click "Create account" button from the modal.
						const createAccountButtonFromModal = setUpAccountsPage.getMCCreateAccountButtonFromModal();
						await createAccountButtonFromModal.click();
						await page.waitForLoadState( 'networkidle' );

						const reclaimButton = setUpAccountsPage.getReclaimMyURLButton();
						await expect( reclaimButton ).toBeVisible();

						const switchAccountButton = setUpAccountsPage.getSwitchAccountButton();
						await expect( switchAccountButton ).toBeVisible();

						const reclaimingURLInput = setUpAccountsPage.getReclaimingURLInput();
						await expect( reclaimingURLInput ).toHaveValue( baseURL );
					} );

					test( 'click "Reclaim my URL" should send a claim overwrite request and see Merchant Center "Connected" ', async ( { baseURL } ) => {
						await Promise.all( [
							// Mock Merchant Center accounts claim overwrite
							setUpAccountsPage.mockMCAccountsClaimOverwrite( 12345 ),

							// Mock Merchant Center as connected with ID 12345
							setUpAccountsPage.mockMCConnected( 12345 ),
						] );

						const reclaimButton = setUpAccountsPage.getReclaimMyURLButton();
						await reclaimButton.click();
						await page.waitForLoadState( 'networkidle' );

						const mcConnectedLabel = setUpAccountsPage.getMCConnectedLabel();
						await expect( mcConnectedLabel ).toContainText( 'Connected' );

						const host = new URL( baseURL ).host;
						const mcDescriptionRow = setUpAccountsPage.getMCDescriptionRow();
						await expect( mcDescriptionRow ).toContainText( `${host} (12345)` );
					} );
				} );
			} );
		} );
	} );
} );
