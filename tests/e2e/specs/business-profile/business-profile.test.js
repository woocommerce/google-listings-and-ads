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

const DOWNTOWN = {
	id: '1111',
	accountId: '9000001',
	title: "Jane's Bakery",
	address: '1 Main Street, Springfield',
	placeId: 'place-1111',
	mapsUri: 'https://maps.google.com/?cid=1111',
};
const RIVERSIDE = {
	id: '2222',
	accountId: '9000001',
	title: "Jane's Bakery Riverside",
	address: '5 River Road, Springfield',
	placeId: 'place-2222',
	mapsUri: 'https://maps.google.com/?cid=2222',
};

// Not covered: whether a location can receive posts. The location selector lists every location
// the Google account manages, with no eligibility check yet.
test.describe( 'Google Business Profile', () => {
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

	test.describe( 'when Google Business Profile access is not granted', () => {
		test( 'should offer to connect, explaining that this grants access', async () => {
			await settingsPage.gotoAccounts();

			await expect(
				settingsPage.googleBusinessProfileAccountCard.getByText(
					'By connecting you are granting Google Business Profile access.'
				)
			).toBeVisible();
			await expect(
				settingsPage.getGoogleBusinessProfileConnectButton()
			).toBeEnabled();
		} );

		test( 'should ask Google for access for the connected Google account when Connect is clicked', async () => {
			await settingsPage
				.withFulfillTimes( 1 )
				.mockGoogleBusinessProfileConnect(
					'/wp-admin/admin.php?page=wc-admin&path=%2Fgoogle%2Fsettings&section=accounts&from-connect=1'
				);

			const requestPromise =
				settingsPage.registerGoogleBusinessProfileConnectRequest();

			await settingsPage.getGoogleBusinessProfileConnectButton().click();

			const request = await requestPromise;

			expect(
				new URL( request.url() ).searchParams.get( 'login_hint' )
			).toBe( 'mail@example.com' );
			await expect( page ).toHaveURL(
				/path=%2Fgoogle%2Fsettings&section=accounts&from-connect=1$/
			);
		} );

		test( 'should say access was not granted when the merchant comes back without it', async () => {
			await settingsPage.gotoAccounts(
				'google-service=business-profile'
			);

			await expect(
				settingsPage.googleBusinessProfileAccountCard.getByText(
					"Google Business Profile access wasn't granted"
				)
			).toBeVisible();
			await expect(
				settingsPage.googleBusinessProfileAccountCard.getByText(
					'Click Connect to try again, and allow access when Google asks.'
				)
			).toBeVisible();
			await expect( page ).not.toHaveURL( /google-service=/ );
		} );
	} );

	test.describe( 'once Google Business Profile access is granted', () => {
		test.beforeAll( async () => {
			await settingsPage.mockGoogleConnectedWithBusinessProfileScope();
		} );

		test.describe( 'with a single location', () => {
			test( 'should show the location with a Connect button', async () => {
				await settingsPage.mockGoogleBusinessProfileLocations( [
					DOWNTOWN,
				] );
				await settingsPage.gotoAccounts();

				await expect(
					settingsPage.googleBusinessProfileAccountCard.getByText(
						'We found a Google Business Profile location.'
					)
				).toBeVisible();
				await expect(
					settingsPage.googleBusinessProfileAccountCard.getByText(
						DOWNTOWN.address
					)
				).toBeVisible();
				await expect(
					settingsPage.getGoogleBusinessProfileConnectButton()
				).toBeEnabled();
			} );

			test( 'should connect the location when Connect is clicked', async () => {
				await settingsPage.mockGoogleBusinessProfileSelectLocationSuccess();
				await settingsPage.mockGoogleBusinessProfileConnected(
					DOWNTOWN
				);

				const requestPromise =
					settingsPage.registerGoogleBusinessProfileSelectLocationRequest();

				await settingsPage
					.getGoogleBusinessProfileConnectButton()
					.click();

				const request = await requestPromise;

				expect( request.postDataJSON() ).toEqual( {
					id: DOWNTOWN.id,
				} );
				await expect(
					settingsPage.googleBusinessProfileAccountCard.getByText(
						'Connected',
						{ exact: true }
					)
				).toBeVisible();
				await expect(
					settingsPage.googleBusinessProfileAccountCard.getByRole(
						'link',
						{ name: DOWNTOWN.address }
					)
				).toBeVisible();
			} );
		} );

		test.describe( 'with multiple locations', () => {
			test.beforeAll( async () => {
				await settingsPage.mockGoogleBusinessProfileNotConnected();
				await settingsPage.mockGoogleBusinessProfileLocations( [
					DOWNTOWN,
					RIVERSIDE,
				] );
			} );

			test( 'should show a selector defaulting to the first location, switchable to another', async () => {
				await settingsPage.gotoAccounts();

				await expect(
					settingsPage.googleBusinessProfileAccountCard.getByText(
						'We found multiple Google Business Profile locations. Pick one to connect.'
					)
				).toBeVisible();
				await expect(
					settingsPage.getGoogleBusinessProfileLocationSelect()
				).toHaveValue( DOWNTOWN.id );
				await expect(
					settingsPage.getGoogleBusinessProfileSaveButton()
				).toBeEnabled();

				await settingsPage
					.getGoogleBusinessProfileLocationSelect()
					.selectOption( RIVERSIDE.id );

				await expect(
					settingsPage.getGoogleBusinessProfileLocationSelect()
				).toHaveValue( RIVERSIDE.id );
			} );

			test( 'should connect the picked location when Save is clicked', async () => {
				await settingsPage.mockGoogleBusinessProfileSelectLocationSuccess();
				await settingsPage.mockGoogleBusinessProfileConnected(
					RIVERSIDE
				);

				const requestPromise =
					settingsPage.registerGoogleBusinessProfileSelectLocationRequest();

				await settingsPage
					.getGoogleBusinessProfileLocationSelect()
					.selectOption( RIVERSIDE.id );
				await settingsPage.getGoogleBusinessProfileSaveButton().click();

				const request = await requestPromise;

				expect( request.postDataJSON() ).toEqual( {
					id: RIVERSIDE.id,
				} );
				await expect(
					settingsPage.googleBusinessProfileAccountCard.getByRole(
						'link',
						{ name: RIVERSIDE.address }
					)
				).toBeVisible();
			} );
		} );

		test.describe( 'disconnect and reconnect', () => {
			test.beforeAll( async () => {
				await settingsPage.mockGoogleBusinessProfileConnected(
					RIVERSIDE
				);
				await settingsPage.mockGoogleBusinessProfileLocations( [
					DOWNTOWN,
					RIVERSIDE,
				] );
				await settingsPage.gotoAccounts();
			} );

			test( 'should disconnect and return to location selection, since access stays granted', async () => {
				await settingsPage.mockGoogleBusinessProfileNotConnected();
				await settingsPage.mockGoogleBusinessProfileDisconnect();

				const requestPromise =
					settingsPage.registerGoogleBusinessProfileDisconnectRequest();

				await settingsPage
					.getGoogleBusinessProfileAccountActionsButton()
					.click();
				await settingsPage.getDisconnectMenuItem().click();

				const modal = page.getByRole( 'dialog', {
					name: 'Disconnect Google Business Profile?',
				} );

				await modal
					.getByRole( 'checkbox', {
						name: 'Yes, I want to disconnect my Google Business Profile.',
					} )
					.check();
				await modal
					.getByRole( 'button', { name: 'Disconnect' } )
					.click();

				await requestPromise;

				await expect(
					settingsPage.getGoogleBusinessProfileLocationSelect()
				).toBeVisible();
				await expect(
					settingsPage.getGoogleBusinessProfileSaveButton()
				).toBeVisible();
				await expect(
					settingsPage.googleBusinessProfileAccountCard.getByText(
						'Connected',
						{ exact: true }
					)
				).toHaveCount( 0 );
			} );

			test( 'should reconnect cleanly afterward', async () => {
				await settingsPage.mockGoogleBusinessProfileSelectLocationSuccess();
				await settingsPage.mockGoogleBusinessProfileConnected(
					DOWNTOWN
				);

				const requestPromise =
					settingsPage.registerGoogleBusinessProfileSelectLocationRequest();

				await settingsPage
					.getGoogleBusinessProfileLocationSelect()
					.selectOption( DOWNTOWN.id );
				await settingsPage.getGoogleBusinessProfileSaveButton().click();

				const request = await requestPromise;

				expect( request.postDataJSON() ).toEqual( {
					id: DOWNTOWN.id,
				} );
				await expect(
					settingsPage.googleBusinessProfileAccountCard.getByRole(
						'link',
						{ name: DOWNTOWN.address }
					)
				).toBeVisible();
			} );
		} );
	} );
} );
