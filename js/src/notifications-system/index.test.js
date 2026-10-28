/**
 * External dependencies
 */
import { select, subscribe } from '@wordpress/data';
import { getPath, getHistory } from '@woocommerce/navigation';

/**
 * Internal dependencies
 */
import {
	registerNotificationsInMarketingSlot,
	removeNotificationFromMarketingSlot,
} from '~/notifications-system/woo-marketing-notifications-slot';

jest.mock( '@wordpress/data', () => ( {
	select: jest.fn(),
	subscribe: jest.fn(),
} ) );

jest.mock( '@woocommerce/navigation', () => ( {
	getPath: jest.fn(),
	getHistory: jest.fn(),
} ) );

jest.mock( '~/notifications-system/woo-marketing-notifications-slot', () => ( {
	registerNotificationsInMarketingSlot: jest.fn(),
	removeNotificationFromMarketingSlot: jest.fn(),
	useDismissNotificationFromMarketingSlot: jest.fn(),
} ) );

jest.mock( './notification', () => () => null );
jest.mock( './useNotificationsSystemMap', () => jest.fn() );
jest.mock( '~/utils/tracks', () => ( {
	recordGlaEvent: jest.fn(),
	CONTEXT_MARKETING_OVERVIEW: 'marketing-overview',
} ) );

const registeredIds = () =>
	registerNotificationsInMarketingSlot.mock.calls.map(
		( [ notifications ] ) => notifications.map( ( { id } ) => id )
	);

describe( 'notifications-system sync', () => {
	let glaNotifications;
	let hasFinishedResolution;
	let storeListener;
	let historyListener;

	const loadModule = () => {
		jest.isolateModules( () => {
			require( './index' );
		} );
		storeListener = subscribe.mock.calls[ 0 ][ 0 ];
	};

	beforeEach( () => {
		jest.clearAllMocks();

		glaNotifications = [];
		hasFinishedResolution = true;

		getPath.mockReturnValue( '/marketing' );
		getHistory.mockReturnValue( {
			listen: ( listener ) => {
				historyListener = listener;
			},
		} );
		select.mockImplementation( () => ( {
			getNotifications: () => glaNotifications,
			hasFinishedResolution: () => hasFinishedResolution,
		} ) );
	} );

	it( 'registers the notifications on the Marketing overview page', () => {
		glaNotifications = [
			{ id: 'a', triggered_at: 1 },
			{ id: 'b', triggered_at: 2 },
		];

		loadModule();

		expect( registeredIds() ).toEqual( [ [ 'a', 'b' ] ] );
		expect(
			registerNotificationsInMarketingSlot.mock.calls[ 0 ][ 0 ][ 0 ]
		).toEqual( {
			id: 'a',
			triggered_at: 1,
			component: expect.any( Function ),
		} );
	} );

	it( 'does nothing outside the Marketing overview page', () => {
		getPath.mockReturnValue( '/google/settings' );
		glaNotifications = [ { id: 'a', triggered_at: 1 } ];

		loadModule();
		storeListener();

		expect( select ).not.toHaveBeenCalled();
		expect( registerNotificationsInMarketingSlot ).not.toHaveBeenCalled();
	} );

	it( 'syncs once SPA navigation reaches the Marketing overview page', () => {
		getPath.mockReturnValue( '/google/settings' );
		glaNotifications = [ { id: 'a', triggered_at: 1 } ];

		loadModule();

		getPath.mockReturnValue( '/marketing' );
		historyListener();

		expect( registeredIds() ).toEqual( [ [ 'a' ] ] );
	} );

	it( 'waits for the notifications to finish resolving', () => {
		hasFinishedResolution = false;
		glaNotifications = [ { id: 'a', triggered_at: 1 } ];

		loadModule();

		expect( registerNotificationsInMarketingSlot ).not.toHaveBeenCalled();

		hasFinishedResolution = true;
		storeListener();

		expect( registeredIds() ).toEqual( [ [ 'a' ] ] );
	} );

	it( 'adds newly active and removes no longer active notifications on refetch, leaving the rest untouched', () => {
		glaNotifications = [
			{ id: 'a', triggered_at: 1 },
			{ id: 'b', triggered_at: 2 },
		];

		loadModule();
		jest.clearAllMocks();

		glaNotifications = [
			{ id: 'b', triggered_at: 2 },
			{ id: 'enhanced-conversions-off', triggered_at: 3 },
		];
		storeListener();

		expect( removeNotificationFromMarketingSlot ).toHaveBeenCalledTimes(
			1
		);
		expect( removeNotificationFromMarketingSlot ).toHaveBeenCalledWith(
			'a'
		);
		expect( registeredIds() ).toEqual( [ [ 'enhanced-conversions-off' ] ] );
	} );

	it( 're-adds a notification that becomes active again', () => {
		glaNotifications = [
			{ id: 'enhanced-conversions-off', triggered_at: 1 },
		];
		loadModule();

		glaNotifications = [];
		storeListener();

		glaNotifications = [
			{ id: 'enhanced-conversions-off', triggered_at: 2 },
		];
		storeListener();

		expect( removeNotificationFromMarketingSlot ).toHaveBeenCalledWith(
			'enhanced-conversions-off'
		);
		expect( registeredIds() ).toEqual( [
			[ 'enhanced-conversions-off' ],
			[ 'enhanced-conversions-off' ],
		] );
	} );

	it( 'does not reconcile again when the notifications are unchanged', () => {
		glaNotifications = [ { id: 'a', triggered_at: 1 } ];
		loadModule();

		storeListener();
		historyListener();

		expect( registerNotificationsInMarketingSlot ).toHaveBeenCalledTimes(
			1
		);
		expect( removeNotificationFromMarketingSlot ).not.toHaveBeenCalled();
	} );
} );
