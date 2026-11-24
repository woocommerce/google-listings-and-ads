/**
 * Internal dependencies
 */
import { GLA_NOTIFICATIONS_COUNT_CHANGED } from '~/constants';

jest.mock( '@wordpress/data', () => ( {
	dispatch: jest.fn(),
	resolveSelect: jest.fn(),
	select: jest.fn(),
	subscribe: jest.fn(),
} ) );

jest.mock( '@wordpress/hooks', () => ( {
	doAction: jest.fn(),
} ) );

jest.mock( '@woocommerce/navigation', () => ( {
	getPath: jest.fn(),
	getHistory: jest.fn(),
} ) );

jest.mock( '~/notifications-system/woo-marketing-notifications-slot', () => ( {
	registerNotificationsInMarketingSlot: jest.fn(),
	useDismissNotificationFromMarketingSlot: jest.fn(),
} ) );

jest.mock( './notification', () => () => null );
jest.mock( './useNotificationsSystemMap', () => jest.fn() );
jest.mock( '~/utils/tracks', () => ( {
	recordGlaEvent: jest.fn(),
	CONTEXT_MARKETING_OVERVIEW: 'marketing-overview',
} ) );

const NOTIFICATION_A = { id: 'notification-a', triggered_at: 1 };
const NOTIFICATION_B = { id: 'notification-b', triggered_at: 2 };
const OTHER_EXTENSION_NOTIFICATION = { id: 'other-extension', triggered_at: 3 };

function flushPromises() {
	return new Promise( ( resolve ) => setTimeout( resolve, 0 ) );
}

describe( 'notifications-system', () => {
	let doAction;
	let getPath;
	let registerNotificationsInMarketingSlot;
	let dismissNotification;
	let syncOnStoreChange;

	// The GLA notifications returned by the GLA data store.
	let glaNotifications;

	// The notifications in the marketing notifications store, i.e. the inbox.
	let inbox;

	/**
	 * Loads the module, which syncs right away when the path is the Marketing overview.
	 *
	 * @param {string} [path='/marketing'] The current wc-admin path.
	 */
	async function loadModule( path = '/marketing' ) {
		jest.resetModules();

		const data = await import( '@wordpress/data' );
		const navigation = await import( '@woocommerce/navigation' );
		const slot =
			await import( '~/notifications-system/woo-marketing-notifications-slot' );
		( { doAction } = await import( '@wordpress/hooks' ) );
		( { getPath } = await import( '@woocommerce/navigation' ) );
		( { registerNotificationsInMarketingSlot } = slot );

		dismissNotification = jest.fn( ( id ) => {
			inbox = inbox.filter( ( notification ) => notification.id !== id );
		} );

		data.resolveSelect.mockImplementation( () => ( {
			getNotifications: () => Promise.resolve( glaNotifications ),
		} ) );
		data.select.mockImplementation( () => ( {
			getNotifications: () => inbox,
		} ) );
		data.dispatch.mockImplementation( () => ( { dismissNotification } ) );
		data.subscribe.mockImplementation( ( listener ) => {
			syncOnStoreChange = listener;
		} );
		navigation.getHistory.mockReturnValue( { listen: jest.fn() } );
		getPath.mockReturnValue( path );
		registerNotificationsInMarketingSlot.mockImplementation(
			( notifications ) => {
				inbox = [ ...inbox, ...notifications ];
			}
		);

		await import( './index' );
		await flushPromises();
	}

	/**
	 * Simulates the GLA notifications being refetched with the given list.
	 *
	 * @param {Array<Object>} notifications The refetched notifications.
	 */
	async function refetch( notifications ) {
		glaNotifications = notifications;
		syncOnStoreChange();
		await flushPromises();
	}

	function getInboxIds() {
		return inbox.map( ( { id } ) => id );
	}

	beforeEach( () => {
		glaNotifications = [ NOTIFICATION_A ];
		inbox = [ OTHER_EXTENSION_NOTIFICATION ];
	} );

	it( 'registers the notifications and reports the inbox count on the first sync', async () => {
		await loadModule();

		expect( getInboxIds() ).toEqual( [
			'other-extension',
			'notification-a',
		] );
		expect( doAction ).toHaveBeenCalledTimes( 1 );
		expect( doAction ).toHaveBeenCalledWith(
			GLA_NOTIFICATIONS_COUNT_CHANGED,
			1
		);
	} );

	it( 'reports 0 when the first sync has no notifications', async () => {
		glaNotifications = [];

		await loadModule();

		expect( registerNotificationsInMarketingSlot ).not.toHaveBeenCalled();
		expect( doAction ).toHaveBeenCalledWith(
			GLA_NOTIFICATIONS_COUNT_CHANGED,
			0
		);
	} );

	it( 'does not sync outside the Marketing overview', async () => {
		await loadModule( '/google/settings' );
		await refetch( [ NOTIFICATION_A, NOTIFICATION_B ] );

		expect( registerNotificationsInMarketingSlot ).not.toHaveBeenCalled();
		expect( doAction ).not.toHaveBeenCalled();
	} );

	it( 'does nothing when the store changes without a refetch', async () => {
		await loadModule();
		doAction.mockClear();

		syncOnStoreChange();
		await flushPromises();

		expect( registerNotificationsInMarketingSlot ).toHaveBeenCalledTimes(
			1
		);
		expect( doAction ).not.toHaveBeenCalled();
	} );

	it( 'does not register the same notification twice when it is refetched', async () => {
		await loadModule();
		doAction.mockClear();

		await refetch( [ { ...NOTIFICATION_A } ] );

		expect( getInboxIds() ).toEqual( [
			'other-extension',
			'notification-a',
		] );
		expect( registerNotificationsInMarketingSlot ).toHaveBeenCalledTimes(
			1
		);
		expect( doAction ).toHaveBeenCalledWith(
			GLA_NOTIFICATIONS_COUNT_CHANGED,
			1
		);
	} );

	it( 'registers only the new notifications when more are refetched', async () => {
		await loadModule();
		doAction.mockClear();

		await refetch( [ NOTIFICATION_A, NOTIFICATION_B ] );

		expect( registerNotificationsInMarketingSlot ).toHaveBeenLastCalledWith(
			[ expect.objectContaining( { id: 'notification-b' } ) ]
		);
		expect( getInboxIds() ).toEqual( [
			'other-extension',
			'notification-a',
			'notification-b',
		] );
		expect( doAction ).toHaveBeenCalledWith(
			GLA_NOTIFICATIONS_COUNT_CHANGED,
			2
		);
	} );

	it( 'removes the notifications that are no longer active, leaving other extensions alone', async () => {
		glaNotifications = [ NOTIFICATION_A, NOTIFICATION_B ];
		await loadModule();
		doAction.mockClear();

		await refetch( [ NOTIFICATION_B ] );

		expect( dismissNotification ).toHaveBeenCalledTimes( 1 );
		expect( dismissNotification ).toHaveBeenCalledWith( 'notification-a' );
		expect( getInboxIds() ).toEqual( [
			'other-extension',
			'notification-b',
		] );
		expect( doAction ).toHaveBeenCalledWith(
			GLA_NOTIFICATIONS_COUNT_CHANGED,
			1
		);
	} );

	it( 'shows a notification again when it comes back after being removed', async () => {
		await loadModule();
		await refetch( [] );
		doAction.mockClear();

		await refetch( [ NOTIFICATION_A ] );

		expect( getInboxIds() ).toEqual( [
			'other-extension',
			'notification-a',
		] );
		expect( doAction ).toHaveBeenCalledWith(
			GLA_NOTIFICATIONS_COUNT_CHANGED,
			1
		);
	} );

	it( 'keeps a notification the merchant dismissed hidden and out of the count', async () => {
		glaNotifications = [ NOTIFICATION_A, NOTIFICATION_B ];
		await loadModule();
		doAction.mockClear();

		// The merchant dismisses notification A from the inbox.
		inbox = inbox.filter( ( { id } ) => id !== 'notification-a' );

		// A refetch can still return it, e.g. one that started before the dismissal.
		await refetch( [ NOTIFICATION_A, NOTIFICATION_B ] );

		expect( getInboxIds() ).toEqual( [
			'other-extension',
			'notification-b',
		] );
		expect( doAction ).toHaveBeenCalledWith(
			GLA_NOTIFICATIONS_COUNT_CHANGED,
			1
		);
	} );
} );
