/**
 * External dependencies
 */
import { expect, test } from '@playwright/test';

/**
 * Internal dependencies
 */
import { clearOnboardedMerchant, setOnboardedMerchant } from '../../utils/api';
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

test.describe( 'Google Search Console', () => {
	test.beforeAll( async ( { browser } ) => {
		page = await browser.newPage();
		settingsPage = new SettingsPage( page );

		await setOnboardedMerchant();
		await settingsPage.mockRequests();
		await settingsPage.mockAllToursChecked();
	} );

	test.afterAll( async () => {
		await clearOnboardedMerchant();
		await page.close();
	} );

	test.describe( 'Accounts subtab, not connected', () => {
		test.beforeAll( async () => {
			await settingsPage.mockSearchConsoleAccountNotConnected();
			await settingsPage.gotoAccounts();
		} );

		test( 'should render the card under Tracking and Site tools with a Connect button', async () => {
			await expect(
				page.getByRole( 'heading', { name: 'Tracking and Site tools' } )
			).toBeVisible();
			await expect(
				page
					.locator( '.gla-account-card__title' )
					.filter( { hasText: /^Google Search Console$/ } )
			).toBeVisible();
			await expect(
				settingsPage.getSearchConsoleConnectButton()
			).toBeVisible();
		} );

		test( 'should request a Search Console connection when Connect is clicked', async () => {
			await settingsPage
				.withFulfillTimes( 1 )
				.mockSearchConsoleConnect(
					'/wp-admin/admin.php?page=wc-admin&path=%2Fgoogle%2Fsettings&section=accounts&from-connect=1'
				);

			const requestPromise =
				settingsPage.registerSearchConsoleConnectRequest();

			await settingsPage.getSearchConsoleConnectButton().click();

			await requestPromise;

			await expect( page ).toHaveURL(
				/path=%2Fgoogle%2Fsettings&section=accounts&from-connect=1$/
			);
		} );
	} );

	test.describe( 'Property resolution', () => {
		test.afterEach( async () => {
			await page.unroute( /\/wc\/gla\/search-console\/connection\b/ );
			await page.unroute( /\/wc\/gla\/search-console\/property\b/ );
		} );

		test( 'resolves straight to the connected state with no merchant action, whether a single property was auto-selected or none existed to silently create one', async () => {
			// Both cases resolve server-side within the same status check and are
			// indistinguishable to the merchant — a single usable property is auto-selected,
			// or a new one is silently created when none exists — so one mocked response
			// covers both.
			await settingsPage.mockSearchConsoleAccountConnected(
				'https://example.com/',
				true
			);
			await settingsPage.gotoAccounts();

			await expect(
				settingsPage.searchConsoleAccountCard.getByText( 'Connected', {
					exact: true,
				} )
			).toBeVisible();
			await expect(
				settingsPage.getSearchConsoleConnectButton()
			).toHaveCount( 0 );
		} );

		test( 'shows a selector for multiple usable properties, and selecting one completes resolution', async () => {
			await settingsPage.mockSearchConsoleMultiMatch( [
				{ siteUrl: 'https://example.com/', usable: true },
				{ siteUrl: 'sc-domain:example.com', usable: true },
			] );
			await settingsPage.gotoAccounts();

			await expect(
				settingsPage.searchConsoleAccountCard.getByText(
					'We found multiple Google Search Console properties'
				)
			).toBeVisible();

			const saveButton =
				settingsPage.getSearchConsoleSavePropertyButton();
			await expect( saveButton ).toBeDisabled();

			await settingsPage
				.getSearchConsolePropertySelect()
				.selectOption( 'https://example.com/' );

			await settingsPage.fulfillSearchConsoleProperty( {
				status: 'connected',
			} );
			await settingsPage.mockSearchConsoleAccountConnected(
				'https://example.com/'
			);

			const requestPromise =
				settingsPage.registerSearchConsolePropertyRequest();

			await saveButton.click();

			const request = await requestPromise;
			expect( request.postDataJSON() ).toEqual( {
				site_url: 'https://example.com/',
			} );

			await expect(
				settingsPage.searchConsoleAccountCard.getByText( 'Connected', {
					exact: true,
				} )
			).toBeVisible();
		} );

		test( 'the "Create new" option completes resolution without an existing selection', async () => {
			await settingsPage.mockSearchConsoleMultiMatch( [
				{ siteUrl: 'https://example.com/', usable: true },
				{ siteUrl: 'https://example.com/shop/', usable: true },
			] );
			await settingsPage.gotoAccounts();

			const createNewButton =
				settingsPage.getSearchConsoleCreateNewPropertyButton();
			// Wait for the initial multi-match fetch to actually resolve and render
			// before re-mocking the endpoints it's about to call next — otherwise this
			// re-mock can race ahead of the app's first fetch and the card mounts
			// straight into the connected state, skipping the selector entirely.
			await expect( createNewButton ).toBeVisible();

			await settingsPage.fulfillSearchConsoleProperty( {
				status: 'connected',
			} );
			await settingsPage.mockSearchConsoleAccountConnected(
				'https://example.com/new-property/'
			);

			const requestPromise =
				settingsPage.registerSearchConsolePropertyRequest();

			await createNewButton.click();

			const request = await requestPromise;
			expect( request.postDataJSON() ).toEqual( {} );

			await expect(
				settingsPage.searchConsoleAccountCard.getByText( 'Connected', {
					exact: true,
				} )
			).toBeVisible();
		} );
	} );

	test.describe( 'Verification', () => {
		test.afterEach( async () => {
			await page.unroute( /\/wc\/gla\/search-console\/connection\b/ );
			await page.unroute( /\/wc\/gla\/search-console\/verify\b/ );
		} );

		test( 'shows a single Verify site action and completes without a full page reload', async () => {
			await settingsPage.mockSearchConsoleActionNeeded();
			await settingsPage.gotoAccounts();

			await expect(
				settingsPage.searchConsoleAccountCard.getByText(
					'Verify your site with Google'
				)
			).toBeVisible();

			await settingsPage.fulfillSearchConsoleVerify( { status: 'ok' } );
			await settingsPage.mockSearchConsoleAccountConnected(
				'https://example.com/'
			);

			// A marker that only a full page navigation would clear, to confirm the
			// transition to the connected state happens via a state update, not a reload.
			await page.evaluate( () => {
				window.__searchConsoleVerifyMarker = 'persisted';
			} );

			await settingsPage.getSearchConsoleVerifyButton().click();

			await expect(
				settingsPage.searchConsoleAccountCard.getByText( 'Connected', {
					exact: true,
				} )
			).toBeVisible();
			await expect
				.poll( () =>
					page.evaluate( () => window.__searchConsoleVerifyMarker )
				)
				.toBe( 'persisted' );
		} );
	} );

	test.describe( 'Connected state', () => {
		test.beforeAll( async () => {
			await settingsPage.mockSearchConsoleAccountConnected(
				'https://example.com/'
			);
			await settingsPage.gotoAccounts();
		} );

		test.afterAll( async () => {
			await page.unroute( /\/wc\/gla\/search-console\/connection\b/ );
		} );

		test( 'shows the Connected badge and the property identifier with an outbound link', async () => {
			await expect(
				settingsPage.searchConsoleAccountCard.getByText( 'Connected', {
					exact: true,
				} )
			).toBeVisible();

			const propertyLink =
				settingsPage.searchConsoleAccountCard.getByRole( 'link', {
					name: 'https://example.com/',
				} );
			await expect( propertyLink ).toHaveAttribute(
				'href',
				/^https:\/\/search\.google\.com\/search-console\?resource_id=/
			);
		} );

		test( 'shows a "View Organic Search report" action', async () => {
			await settingsPage.getSearchConsoleAccountActionsButton().click();

			await expect(
				page.getByRole( 'menuitem', {
					name: 'View Organic Search report',
				} )
			).toBeVisible();
		} );
	} );

	test.describe( 'Disconnect', () => {
		test.beforeAll( async () => {
			await settingsPage.mockSearchConsoleAccountConnected(
				'https://example.com/'
			);
			await settingsPage.gotoAccounts();
		} );

		test.afterAll( async () => {
			await page.unroute( /\/wc\/gla\/search-console\/connection\b/ );
		} );

		test( 'disconnects via the confirmation modal and returns to the not-connected state', async () => {
			await settingsPage.mockSearchConsoleAccountNotConnected();
			await settingsPage.mockSearchConsoleDisconnect();

			const requestPromise =
				settingsPage.registerSearchConsoleDisconnectRequest();

			await settingsPage.getSearchConsoleAccountActionsButton().click();
			await settingsPage.getSearchConsoleDisconnectMenuItem().click();
			await page
				.getByRole( 'checkbox', {
					name: 'Yes, I want to disconnect my Google Search Console account.',
				} )
				.check();
			await page
				.getByRole( 'button', {
					name: 'Disconnect Google Search Console account',
				} )
				.click();

			await requestPromise;

			await expect(
				settingsPage.getSearchConsoleConnectButton()
			).toBeVisible();
		} );

		test( 'reconnecting after a disconnect completes the flow again cleanly', async () => {
			// Both mocks are armed before clicking Connect: the connect click triggers a
			// real navigation back to this same accounts URL, which re-mounts the app and
			// re-fetches connection status fresh — by then it should already report connected.
			await settingsPage.withFulfillTimes( 1 ).mockSearchConsoleConnect();
			await settingsPage.mockSearchConsoleAccountConnected(
				'https://example.com/'
			);

			const requestPromise =
				settingsPage.registerSearchConsoleConnectRequest();

			await settingsPage.getSearchConsoleConnectButton().click();

			await requestPromise;

			await expect( page ).toHaveURL(
				/path=%2Fgoogle%2Fsettings&section=accounts$/
			);
			await expect(
				settingsPage.searchConsoleAccountCard.getByText( 'Connected', {
					exact: true,
				} )
			).toBeVisible();
		} );
	} );
} );
