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

const SOLO_ACCOUNT = { id: '111', name: 'Solo Business' };
const FIRST_ACCOUNT = { id: '111', name: 'Account One' };
const SECOND_ACCOUNT = { id: '222', name: 'Account Two' };
const CONNECTED_ACCOUNT = { id: '6000001', name: 'My Business' };
const CONNECTED_CONTAINER = {
	id: '7000001',
	name: 'My Website Container',
	publicId: 'GTM-ABC1234',
};
const SECOND_CONTAINER = {
	id: '7000002',
	name: 'My Blog Container',
	publicId: 'GTM-DEF5678',
};

test.describe( 'Google Tag Manager', () => {
	test.beforeAll( async ( { browser } ) => {
		page = await browser.newPage();
		settingsPage = new SettingsPage( page );

		await setOnboardedMerchant();
		await settingsPage.mockRequests();
		// The Accounts page waits on this connection status to resolve
		// regardless of scope, so every test needs it mocked from the start.
		await settingsPage.mockTagManagerAccountNotConnected();
	} );

	test.afterAll( async () => {
		await clearOnboardedMerchant();
		await page.close();
	} );

	test.describe( 'when the connected Google account lacks the Tag Manager scope', () => {
		test.beforeAll( async () => {
			await settingsPage.gotoAccounts();
		} );

		test( 'should show the "Allow access" gate instead of account detection', async () => {
			await expect(
				page.getByRole( 'heading', {
					name: 'Tracking and site tools',
				} )
			).toBeVisible();
			await expect(
				settingsPage.getGoogleTagManagerAllowAccessButton()
			).toBeVisible();
			await expect(
				settingsPage.googleTagManagerAccountCard.getByText(
					'Google needs your permission before this store can connect to your Google Tag Manager account.'
				)
			).toBeVisible();
		} );
	} );

	test.describe( 'once the Tag Manager scope is granted', () => {
		test.beforeAll( async () => {
			await settingsPage.mockGoogleConnectedWithTagManagerScope();
		} );

		test.describe( 'account selection', () => {
			test( 'should render under "Tracking and site tools" with the zero-account CTA', async () => {
				await settingsPage.mockTagManagerAccountsList( [] );
				await settingsPage.gotoAccounts();

				await expect(
					page.getByRole( 'heading', {
						name: 'Tracking and site tools',
					} )
				).toBeVisible();
				await expect(
					settingsPage.googleTagManagerAccountCard.getByText(
						"We couldn't find a Google Tag Manager account",
						{ exact: false }
					)
				).toBeVisible();
				await expect(
					settingsPage.getGoogleTagManagerCreateAccountLink()
				).toBeVisible();
			} );

			test( 'should auto-select the account when exactly one exists', async () => {
				await settingsPage.mockTagManagerAccountsList( [
					SOLO_ACCOUNT,
				] );
				await settingsPage.gotoAccounts();

				await expect(
					settingsPage.googleTagManagerAccountCard.getByText(
						SOLO_ACCOUNT.name
					)
				).toBeVisible();
				await expect(
					settingsPage.getGoogleTagManagerConnectButton()
				).toBeEnabled();
			} );

			test( 'should show a selector defaulting to the first account, switchable to another', async () => {
				await settingsPage.mockTagManagerAccountsList( [
					FIRST_ACCOUNT,
					SECOND_ACCOUNT,
				] );
				await settingsPage.gotoAccounts();

				// The selector auto-selects the first option, so "Connect" is
				// already enabled — picking a different account keeps it so.
				await expect(
					settingsPage.getGoogleTagManagerSelect()
				).toHaveValue( FIRST_ACCOUNT.id );
				await expect(
					settingsPage.getGoogleTagManagerConnectButton()
				).toBeEnabled();

				await settingsPage
					.getGoogleTagManagerSelect()
					.selectOption( SECOND_ACCOUNT.id );

				await expect(
					settingsPage.getGoogleTagManagerSelect()
				).toHaveValue( SECOND_ACCOUNT.id );
				await expect(
					settingsPage.getGoogleTagManagerConnectButton()
				).toBeEnabled();
			} );

			test( 'should connect the picked account and move to container selection', async () => {
				await settingsPage.mockTagManagerAccountsList( [
					FIRST_ACCOUNT,
					SECOND_ACCOUNT,
				] );
				await settingsPage.gotoAccounts();

				await settingsPage
					.getGoogleTagManagerSelect()
					.selectOption( SECOND_ACCOUNT.id );

				await settingsPage.mockTagManagerSelectAccountSuccess();
				await settingsPage.mockTagManagerAccountIncomplete(
					SECOND_ACCOUNT
				);
				await settingsPage.mockTagManagerContainersList( [] );

				const requestPromise =
					settingsPage.registerGoogleTagManagerSelectAccountRequest();

				await settingsPage.getGoogleTagManagerConnectButton().click();
				await requestPromise;

				await expect(
					settingsPage.googleTagManagerAccountCard.getByText(
						'Action needed'
					)
				).toBeVisible();
				await expect(
					settingsPage.googleTagManagerAccountCard.getByText(
						'No container found'
					)
				).toBeVisible();
			} );
		} );

		test.describe( 'container selection', () => {
			test.beforeAll( async () => {
				await settingsPage.mockTagManagerAccountIncomplete(
					CONNECTED_ACCOUNT
				);
			} );

			test( 'should show "Create new container" in place of a selector when the account has none', async () => {
				await settingsPage.mockTagManagerContainersList( [] );
				await settingsPage.gotoAccounts();

				await expect(
					settingsPage.googleTagManagerAccountCard.getByText(
						'No container found'
					)
				).toBeVisible();
				await expect(
					settingsPage.getGoogleTagManagerCreateContainerLink()
				).toBeVisible();
			} );

			test( 'should show the refresh-page notice after clicking "Create new container"', async () => {
				await settingsPage
					.getGoogleTagManagerCreateContainerLink()
					.click();

				await expect(
					settingsPage.googleTagManagerAccountCard.getByText(
						'Refresh the page to see your new container'
					)
				).toBeVisible();
			} );

			test( 'should show a selector defaulting to the first container, switchable to another, with Save enabled', async () => {
				await settingsPage.mockTagManagerContainersList( [
					CONNECTED_CONTAINER,
					SECOND_CONTAINER,
				] );
				await settingsPage.gotoAccounts();

				// The selector auto-selects the first option, so "Save" is
				// already enabled — picking a different container keeps it so.
				await expect(
					settingsPage.getGoogleTagManagerSelect()
				).toHaveValue( CONNECTED_CONTAINER.id );
				await expect(
					settingsPage.getGoogleTagManagerSaveButton()
				).toBeEnabled();

				await settingsPage
					.getGoogleTagManagerSelect()
					.selectOption( SECOND_CONTAINER.id );

				await expect(
					settingsPage.getGoogleTagManagerSelect()
				).toHaveValue( SECOND_CONTAINER.id );
				await expect(
					settingsPage.getGoogleTagManagerSaveButton()
				).toBeEnabled();
			} );

			test( 'should save the selected container and complete the connection', async () => {
				await settingsPage.mockTagManagerContainersList( [
					CONNECTED_CONTAINER,
					SECOND_CONTAINER,
				] );
				await settingsPage.gotoAccounts();

				await settingsPage
					.getGoogleTagManagerSelect()
					.selectOption( CONNECTED_CONTAINER.id );

				await settingsPage.mockTagManagerSelectContainerSuccess();
				await settingsPage.mockTagManagerAccountConnected(
					CONNECTED_ACCOUNT,
					CONNECTED_CONTAINER
				);

				const requestPromise =
					settingsPage.registerGoogleTagManagerSelectContainerRequest();

				await settingsPage.getGoogleTagManagerSaveButton().click();
				await requestPromise;

				await expect(
					settingsPage.googleTagManagerAccountCard.getByText(
						'Connected'
					)
				).toBeVisible();
			} );
		} );

		test.describe( 'connection-attempt failure', () => {
			test.beforeAll( async () => {
				await settingsPage.mockTagManagerAccountNotConnected();
				await settingsPage.mockTagManagerAccountsList( [
					SOLO_ACCOUNT,
				] );
				await settingsPage.gotoAccounts();
			} );

			test( 'should show an error notice when the connection attempt fails', async () => {
				await settingsPage.mockTagManagerSelectAccountFailure();

				await settingsPage.getGoogleTagManagerConnectButton().click();

				await expect(
					settingsPage.googleTagManagerAccountCard.getByText(
						"We couldn't connect Google Tag Manager"
					)
				).toBeVisible();
				await expect(
					settingsPage.getGoogleTagManagerTryAgainButton()
				).toBeVisible();
			} );

			test( '"Try again" restarts a fresh account selection rather than retrying the same target', async () => {
				await settingsPage.getGoogleTagManagerTryAgainButton().click();

				await expect(
					settingsPage.googleTagManagerAccountCard.getByText(
						"We couldn't connect Google Tag Manager"
					)
				).toHaveCount( 0 );
				await expect(
					settingsPage.googleTagManagerAccountCard.getByText(
						SOLO_ACCOUNT.name
					)
				).toBeVisible();
				await expect(
					settingsPage.getGoogleTagManagerConnectButton()
				).toBeVisible();
			} );
		} );

		test.describe( 'connected state', () => {
			test.beforeAll( async () => {
				await settingsPage.mockTagManagerAccountConnected(
					CONNECTED_ACCOUNT,
					CONNECTED_CONTAINER
				);
				await settingsPage.gotoAccounts();
			} );

			test( 'should show the connected badge, account link, and container name/public ID', async () => {
				await expect(
					settingsPage.googleTagManagerAccountCard.getByText(
						'Connected'
					)
				).toBeVisible();
				await expect(
					settingsPage.googleTagManagerAccountCard.getByRole(
						'link',
						{ name: CONNECTED_ACCOUNT.id }
					)
				).toBeVisible();
				await expect(
					settingsPage.googleTagManagerAccountCard.getByText(
						`${ CONNECTED_CONTAINER.name } (${ CONNECTED_CONTAINER.publicId })`
					)
				).toBeVisible();
			} );

			test( 'should offer "Open Google Tag Manager" from the actions menu', async () => {
				await settingsPage
					.getGoogleTagManagerAccountActionsButton()
					.click();

				await expect(
					settingsPage.getGoogleTagManagerOpenMenuItem()
				).toHaveAttribute( 'href', /tagmanager\.google\.com/ );
			} );
		} );

		test.describe( 'Ads Conversion Duplicate-Tracking notice', () => {
			test( 'should render on the not-connected state', async () => {
				await settingsPage.mockTagManagerAccountNotConnected();
				await settingsPage.mockTagManagerAccountsList( [] );
				await settingsPage.gotoAccounts();

				await expect(
					settingsPage.googleTagManagerAccountCard.getByRole(
						'link',
						{ name: 'use this snippet' }
					)
				).toBeVisible();
			} );

			test( 'should render on the container-selection state', async () => {
				await settingsPage.mockTagManagerAccountIncomplete(
					CONNECTED_ACCOUNT
				);
				await settingsPage.mockTagManagerContainersList( [] );
				await settingsPage.gotoAccounts();

				await expect(
					settingsPage.googleTagManagerAccountCard.getByRole(
						'link',
						{ name: 'use this snippet' }
					)
				).toBeVisible();
			} );

			test( 'should render on the connected state', async () => {
				await settingsPage.mockTagManagerAccountConnected(
					CONNECTED_ACCOUNT,
					CONNECTED_CONTAINER
				);
				await settingsPage.gotoAccounts();

				await expect(
					settingsPage.googleTagManagerAccountCard.getByRole(
						'link',
						{ name: 'use this snippet' }
					)
				).toBeVisible();
			} );
		} );

		test.describe( 'disconnect and reconnect', () => {
			test.beforeAll( async () => {
				await settingsPage.mockTagManagerAccountConnected(
					CONNECTED_ACCOUNT,
					CONNECTED_CONTAINER
				);
				await settingsPage.gotoAccounts();
			} );

			test( 'should disconnect via the confirmation modal and return to the not-connected state', async () => {
				// Disconnect clears state locally (no refetch), but the account
				// card that mounts afterward re-resolves its accounts list.
				await settingsPage.mockTagManagerAccountsList( [] );
				await settingsPage.mockTagManagerDisconnect();

				const requestPromise =
					settingsPage.registerGoogleTagManagerDisconnectRequest();

				await settingsPage
					.getGoogleTagManagerAccountActionsButton()
					.click();
				await settingsPage
					.getGoogleTagManagerDisconnectMenuItem()
					.click();
				await page
					.getByRole( 'checkbox', {
						name: 'Yes, I want to disconnect my Google Tag Manager account.',
					} )
					.check();
				await page
					.getByRole( 'button', {
						name: 'Disconnect Google Tag Manager account',
					} )
					.click();

				await requestPromise;

				await expect(
					settingsPage.getGoogleTagManagerCreateAccountLink()
				).toBeVisible();
			} );

			test( 'should reconnect cleanly afterward', async () => {
				// The previous test only disconnected in local client state (no
				// refetch) — a fresh page load re-fetches connection status, so
				// it must be re-mocked as disconnected before reloading here.
				await settingsPage.mockTagManagerAccountNotConnected();
				await settingsPage.mockTagManagerAccountsList( [
					SOLO_ACCOUNT,
				] );
				await settingsPage.gotoAccounts();

				// Confirm the not-connected state has actually rendered before
				// re-mocking the endpoints for the post-click state — otherwise
				// the re-mock can win the race against the page's own first
				// connection-status fetch, since `mockTagManagerAccountIncomplete()`
				// matches every HTTP method, not just the POST it's meant for.
				await expect(
					settingsPage.getGoogleTagManagerConnectButton()
				).toBeEnabled();

				await settingsPage.mockTagManagerSelectAccountSuccess();
				await settingsPage.mockTagManagerAccountIncomplete(
					SOLO_ACCOUNT
				);
				await settingsPage.mockTagManagerContainersList( [
					CONNECTED_CONTAINER,
				] );

				const accountRequestPromise =
					settingsPage.registerGoogleTagManagerSelectAccountRequest();

				await settingsPage.getGoogleTagManagerConnectButton().click();
				await accountRequestPromise;

				await settingsPage
					.getGoogleTagManagerSelect()
					.selectOption( CONNECTED_CONTAINER.id );

				await settingsPage.mockTagManagerSelectContainerSuccess();
				await settingsPage.mockTagManagerAccountConnected(
					SOLO_ACCOUNT,
					CONNECTED_CONTAINER
				);

				const containerRequestPromise =
					settingsPage.registerGoogleTagManagerSelectContainerRequest();

				await settingsPage.getGoogleTagManagerSaveButton().click();
				await containerRequestPromise;

				await expect(
					settingsPage.googleTagManagerAccountCard.getByText(
						'Connected'
					)
				).toBeVisible();
			} );
		} );
	} );
} );
