/**
 * Internal dependencies
 */
import { STORE_KEY } from '~/data/constants';

jest.mock( '@woocommerce/navigation', () => ( {
	getPath: jest.fn(),
	getHistory: jest.fn(),
} ) );

jest.mock( '@wordpress/data', () => ( {
	resolveSelect: jest.fn(),
} ) );

jest.mock( '~/notifications-system/woo-marketing-notifications-slot', () => ( {
	registerNotificationsInMarketingSlot: jest.fn(),
	useDismissNotificationFromMarketingSlot: jest.fn(),
} ) );

// `./notification` and `./useNotificationsSystemMap` are only used inside a
// notification component's render closure, never while fetching/registering
// notifications — mock them out so importing `./index` doesn't drag in the
// unrelated `@wordpress/components` tree (and its own `@wordpress/data` use).
jest.mock( './notification', () => () => null );
jest.mock( './useNotificationsSystemMap', () => () => ( {} ) );

// `~/utils/tracks` pulls in the whole `~/data` store (and, through it, the
// real `@wordpress/data`) just to provide `recordGlaEvent`, which is only
// used inside a dismissed-notification's click handler — irrelevant here.
jest.mock( '~/utils/tracks', () => ( {
	recordGlaEvent: jest.fn(),
	CONTEXT_MARKETING_OVERVIEW: 'marketing-overview',
} ) );

const flushPromises = () =>
	new Promise( ( resolve ) => setImmediate( resolve ) );

/**
 * `./index` self-initializes on import — it fetches/registers notifications
 * for the current SPA route and subscribes to future navigations — so each
 * test needs its own fresh module instance rather than a shared one. Setting
 * up the mocks and requiring `./index` inside the same `isolateModules` call
 * keeps them all in the same sandboxed registry, so the mock references
 * returned here are the exact ones `./index` calls internally.
 */
function bootstrap( { path, notifications = [] } ) {
	let deps;

	jest.isolateModules( () => {
		const { getPath, getHistory } = require( '@woocommerce/navigation' );
		const { resolveSelect } = require( '@wordpress/data' );
		const {
			registerNotificationsInMarketingSlot,
		} = require( '~/notifications-system/woo-marketing-notifications-slot' );

		const getNotifications = jest.fn().mockResolvedValue( notifications );
		const listen = jest.fn();

		getPath.mockReturnValue( path );
		getHistory.mockReturnValue( { listen } );
		resolveSelect.mockReturnValue( { getNotifications } );

		require( './index' );

		deps = {
			getPath,
			getHistory,
			resolveSelect,
			registerNotificationsInMarketingSlot,
			getNotifications,
			listen,
		};
	} );

	return deps;
}

describe( 'notifications-system bootstrap', () => {
	it( 'fetches and registers notifications when the current SPA route is Marketing overview', async () => {
		const {
			resolveSelect,
			getNotifications,
			registerNotificationsInMarketingSlot,
		} = bootstrap( {
			path: '/marketing',
			notifications: [ { id: 'gcr-badge-widget', triggered_at: 123 } ],
		} );

		await flushPromises();

		expect( resolveSelect ).toHaveBeenCalledWith( STORE_KEY );
		expect( getNotifications ).toHaveBeenCalledTimes( 1 );
		expect( registerNotificationsInMarketingSlot ).toHaveBeenCalledWith( [
			expect.objectContaining( {
				id: 'gcr-badge-widget',
				triggered_at: 123,
				component: expect.any( Function ),
			} ),
		] );
	} );

	it( 'does not fetch or register notifications when the current route is not Marketing overview', async () => {
		const { resolveSelect, registerNotificationsInMarketingSlot } =
			bootstrap( { path: '/settings' } );

		await flushPromises();

		expect( resolveSelect ).not.toHaveBeenCalled();
		expect( registerNotificationsInMarketingSlot ).not.toHaveBeenCalled();
	} );

	it( 'registers an empty list, clearing any stale notifications, when the fetch resolves with none', async () => {
		const { registerNotificationsInMarketingSlot } = bootstrap( {
			path: '/marketing',
			notifications: [],
		} );

		await flushPromises();

		expect( registerNotificationsInMarketingSlot ).toHaveBeenCalledWith(
			[]
		);
	} );

	it( 're-syncs on every subsequent visit to Marketing overview, not just the first', async () => {
		const {
			getPath,
			resolveSelect,
			registerNotificationsInMarketingSlot,
			listen,
		} = bootstrap( { path: '/marketing' } );

		await flushPromises();
		expect( resolveSelect ).toHaveBeenCalledTimes( 1 );
		expect( registerNotificationsInMarketingSlot ).toHaveBeenCalledTimes(
			1
		);

		const onNavigate = listen.mock.calls[ 0 ][ 0 ];
		getPath.mockReturnValue( '/marketing' );
		onNavigate();
		await flushPromises();

		expect( resolveSelect ).toHaveBeenCalledTimes( 2 );
		expect( registerNotificationsInMarketingSlot ).toHaveBeenCalledTimes(
			2
		);
	} );

	it( 'does not fetch or register on SPA navigation to a page other than Marketing overview', async () => {
		const {
			getPath,
			resolveSelect,
			registerNotificationsInMarketingSlot,
			listen,
		} = bootstrap( { path: '/marketing' } );

		await flushPromises();
		expect( registerNotificationsInMarketingSlot ).toHaveBeenCalledTimes(
			1
		);

		const onNavigate = listen.mock.calls[ 0 ][ 0 ];
		getPath.mockReturnValue( '/settings' );
		onNavigate();
		await flushPromises();

		expect( resolveSelect ).toHaveBeenCalledTimes( 1 );
		expect( registerNotificationsInMarketingSlot ).toHaveBeenCalledTimes(
			1
		);
	} );
} );
