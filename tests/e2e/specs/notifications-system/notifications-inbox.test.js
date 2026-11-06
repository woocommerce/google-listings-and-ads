/**
 * External dependencies
 */
import { test, expect } from '@playwright/test';

/**
 * Internal dependencies
 */
import { LOAD_STATE } from '../../utils/constants';
import MockRequests from '../../utils/mock-requests';

test.use( { storageState: process.env.ADMINSTATE } );

test.describe.configure( { mode: 'serial' } );

const ENHANCED_CONVERSIONS_OFF = {
	id: 'enhanced-conversions-off',
	triggered_at: 1700000000,
};

const ENHANCED_CONVERSIONS_OFF_TITLE =
	'Enable Enhanced Conversions for accurate reporting';

/**
 * @type {import('@playwright/test').Page} page
 */
let page = null;

/**
 * @type {import('../../utils/mock-requests.js').default} mockRequests
 */
let mockRequests = null;

/**
 * The notifications the mocked server currently considers active.
 *
 * @type {Array<Object>}
 */
let activeNotifications = [];

/**
 * Dispatch an action of the GLA data store in the page, the same way the
 * Google Ads account card does when connecting or disconnecting the account.
 *
 * @param {string} actionName The name of the action to dispatch.
 * @return {Promise<void>}
 */
async function dispatchGlaAction( actionName ) {
	await page.evaluate( async ( name ) => {
		await window.wp.data.dispatch( 'wc/gla' )[ name ]();
	}, actionName );
}

/**
 * Wait until the GLA notifications are refetched, then get how many times the
 * notification is registered in the marketing notifications store.
 *
 * Reading the store once after the refetch settles, instead of using a retrying
 * `toHaveCount`, makes sure a duplicate or a re-added card can't go unnoticed.
 *
 * @param {string} id The notification ID.
 * @return {Promise<number>} The number of registered notifications with that ID.
 */
async function getSyncedNotificationCount( id ) {
	await page.waitForFunction( () =>
		window.wp.data
			.select( 'wc/gla' )
			.hasFinishedResolution( 'getNotifications', [] )
	);

	return page.evaluate(
		( notificationId ) =>
			window.wp.data
				.select( 'wc/marketing-notifications-system' )
				.getNotifications()
				.filter(
					( { id: registeredId } ) => registeredId === notificationId
				).length,
		id
	);
}

/**
 * Get the "Enable Enhanced Conversions" notification cards in the inbox.
 *
 * @return {import('@playwright/test').Locator} The notification cards.
 */
function getEnhancedConversionsOffNotifications() {
	return page
		.locator( '.gla-notification' )
		.filter( { hasText: ENHANCED_CONVERSIONS_OFF_TITLE } );
}

test.describe( 'Notifications inbox', () => {
	test.beforeAll( async ( { browser } ) => {
		page = await browser.newPage();
		mockRequests = new MockRequests( page );

		await page.route( /\/wc\/gla\/notifications\b/, ( route ) => {
			const request = route.request();

			if ( request.method() === 'DELETE' ) {
				const [ , id ] = decodeURIComponent( request.url() ).match(
					/\/notifications\/([^/?&]+)/
				);

				activeNotifications = activeNotifications.filter(
					( notification ) => notification.id !== id
				);

				return route.fulfill( { json: { status: 'success' } } );
			}

			return route.fulfill( {
				json: { notifications: activeNotifications },
			} );
		} );

		await mockRequests.fulfillAdsConnection( {
			id: 12345,
			currency: 'USD',
			symbol: '$',
			status: 'connected',
		} );

		activeNotifications = [ ENHANCED_CONVERSIONS_OFF ];

		await page.goto(
			'/wp-admin/admin.php?page=wc-admin&path=%2Fmarketing',
			{ waitUntil: LOAD_STATE.DOM_CONTENT_LOADED }
		);
	} );

	test.afterAll( async () => {
		await page.close();
	} );

	test( 'Shows the active notification on load', async () => {
		await expect( getEnhancedConversionsOffNotifications() ).toHaveCount(
			1
		);
	} );

	test( 'Removes the notification without a reload when the Ads account is disconnected', async () => {
		activeNotifications = [];

		await dispatchGlaAction( 'disconnectGoogleAdsAccount' );

		await expect( getEnhancedConversionsOffNotifications() ).toHaveCount(
			0
		);
	} );

	test( 'Shows the notification again without a reload when the Ads account is reconnected', async () => {
		activeNotifications = [ ENHANCED_CONVERSIONS_OFF ];

		await dispatchGlaAction( 'fetchGoogleAdsAccount' );

		await expect( getEnhancedConversionsOffNotifications() ).toHaveCount(
			1
		);
	} );

	test( 'Does not duplicate the notification when it is refetched again', async () => {
		const refetch = page.waitForResponse( /\/wc\/gla\/notifications\b/ );

		await dispatchGlaAction( 'fetchGoogleAdsAccount' );
		await refetch;

		expect(
			await getSyncedNotificationCount( ENHANCED_CONVERSIONS_OFF.id )
		).toBe( 1 );
		await expect( getEnhancedConversionsOffNotifications() ).toHaveCount(
			1
		);
	} );

	test( 'Keeps a dismissed notification hidden after the Ads account is reconnected', async () => {
		await getEnhancedConversionsOffNotifications()
			.getByRole( 'button', { name: 'Dismiss notification' } )
			.click();

		await expect( getEnhancedConversionsOffNotifications() ).toHaveCount(
			0
		);

		const refetch = page.waitForResponse(
			( response ) =>
				/\/wc\/gla\/notifications\b/.test( response.url() ) &&
				response.request().method() === 'GET'
		);

		await dispatchGlaAction( 'fetchGoogleAdsAccount' );
		await refetch;

		expect(
			await getSyncedNotificationCount( ENHANCED_CONVERSIONS_OFF.id )
		).toBe( 0 );
		await expect( getEnhancedConversionsOffNotifications() ).toHaveCount(
			0
		);
	} );
} );
