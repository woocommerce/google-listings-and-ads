/**
 * External dependencies
 */
import { act, renderHook } from '@testing-library/react';
import { doAction, hasAction } from '@wordpress/hooks';

/**
 * Internal dependencies
 */
import useBusinessProfileSyncStatus, {
	FIRST_CHECK_DELAY,
} from './useBusinessProfileSyncStatus';

const POST_ID = 123;

const queued = {
	outcome: 'queued',
	display_message: null,
	display_status: null,
};

const rejected = {
	outcome: 'content-rejection',
	display_message: 'This update was rejected by Google.',
	display_status: 'error',
};

const notShared = { is_shared: false, search_url: '' };

const shared = {
	is_shared: true,
	search_url: 'https://local.google.com/place?id=1&use=posts',
};

/**
 * Runs a Heartbeat request the way `heartbeat.js` does, and returns the data the request sent.
 *
 * @param {Object} [response] The Heartbeat response.
 * @return {Object} The data sent with the request.
 */
function beat( response = {} ) {
	const data = {};

	act( () => {
		doAction( 'heartbeat.send', data );
		doAction( 'heartbeat.tick', response );
	} );

	return data;
}

/**
 * Builds a Heartbeat response carrying a post's send status.
 *
 * @param {Object|null} syncOutcome The post's sync outcome.
 * @param {Object} shareStatus The post's share status.
 * @return {Object} The response.
 */
function statusResponse( syncOutcome, shareStatus ) {
	return { gla_gbp_sync_status: { syncOutcome, shareStatus } };
}

/**
 * Lets the timer for the first check after a save run.
 */
function runFirstCheckTimer() {
	act( () => {
		jest.advanceTimersByTime( FIRST_CHECK_DELAY );
	} );
}

describe( 'useBusinessProfileSyncStatus', () => {
	beforeEach( () => {
		jest.useFakeTimers();
		window.wp = {
			heartbeat: { connectNow: jest.fn().mockName( 'connectNow' ) },
		};
	} );

	afterEach( () => {
		jest.useRealTimers();
		delete window.wp;
	} );

	it( "returns the editor's status and doesn't ask Heartbeat when the send isn't queued", () => {
		const { result } = renderHook( () =>
			useBusinessProfileSyncStatus( {
				postId: POST_ID,
				syncOutcome: rejected,
				shareStatus: shared,
			} )
		);

		runFirstCheckTimer();

		expect( window.wp.heartbeat.connectNow ).not.toHaveBeenCalled();
		expect( beat() ).toEqual( {} );
		expect( result.current ).toEqual( {
			syncOutcome: rejected,
			isShared: true,
			searchUrl: shared.search_url,
		} );
	} );

	it( "asks for the post's status on Heartbeat requests while the send is queued, starting shortly after the save", () => {
		const { result } = renderHook( () =>
			useBusinessProfileSyncStatus( {
				postId: POST_ID,
				syncOutcome: queued,
				shareStatus: notShared,
			} )
		);

		act( () => {
			jest.advanceTimersByTime( FIRST_CHECK_DELAY - 1 );
		} );
		expect( window.wp.heartbeat.connectNow ).not.toHaveBeenCalled();

		act( () => {
			jest.advanceTimersByTime( 1 );
		} );
		expect( window.wp.heartbeat.connectNow ).toHaveBeenCalledTimes( 1 );
		expect( beat() ).toEqual( { gla_gbp_sync_status: POST_ID } );
		expect( result.current.syncOutcome ).toBe( queued );
	} );

	it( 'keeps asking while Heartbeat reports the send as still queued', () => {
		renderHook( () =>
			useBusinessProfileSyncStatus( {
				postId: POST_ID,
				syncOutcome: queued,
				shareStatus: notShared,
			} )
		);

		beat( statusResponse( queued, notShared ) );

		expect( beat() ).toEqual( { gla_gbp_sync_status: POST_ID } );
		runFirstCheckTimer();
		expect( window.wp.heartbeat.connectNow ).toHaveBeenCalledTimes( 1 );
	} );

	it( 'returns the outcome and link from Heartbeat once the send settles, and stops asking', () => {
		const { result } = renderHook( () =>
			useBusinessProfileSyncStatus( {
				postId: POST_ID,
				syncOutcome: queued,
				shareStatus: notShared,
			} )
		);

		beat( statusResponse( { outcome: 'success' }, shared ) );

		expect( result.current ).toEqual( {
			syncOutcome: { outcome: 'success' },
			isShared: true,
			searchUrl: shared.search_url,
		} );
		expect( beat() ).toEqual( {} );
		expect(
			hasAction(
				'heartbeat.tick',
				'google-listings-and-ads/business-profile-sync-status'
			)
		).toBe( false );
	} );

	it( 'asks while a removal is queued, and returns the post as no longer shared once it settles', () => {
		const { result } = renderHook( () =>
			useBusinessProfileSyncStatus( {
				postId: POST_ID,
				syncOutcome: { outcome: 'unshare-queued' },
				shareStatus: shared,
			} )
		);

		expect( beat( statusResponse( null, notShared ) ) ).toEqual( {
			gla_gbp_sync_status: POST_ID,
		} );
		expect( result.current ).toEqual( {
			syncOutcome: null,
			isShared: false,
			searchUrl: '',
		} );
		expect( beat() ).toEqual( {} );
	} );

	it( "ignores Heartbeat responses without the post's status", () => {
		const { result } = renderHook( () =>
			useBusinessProfileSyncStatus( {
				postId: POST_ID,
				syncOutcome: queued,
				shareStatus: notShared,
			} )
		);

		beat( { 'wp-refresh-post-lock': {} } );

		expect( result.current.syncOutcome ).toBe( queued );
	} );

	it( "uses a later save's own outcome, not a status returned for an earlier one", () => {
		let props = {
			postId: POST_ID,
			syncOutcome: queued,
			shareStatus: notShared,
			savedAt: '2026-11-11T16:42:44',
		};
		const { result, rerender } = renderHook( () =>
			useBusinessProfileSyncStatus( props )
		);

		beat( statusResponse( { outcome: 'success' }, shared ) );
		props = {
			...props,
			syncOutcome: rejected,
			savedAt: '2026-11-11T16:43:47',
		};
		rerender();

		expect( result.current.syncOutcome ).toBe( rejected );
		expect( result.current.isShared ).toBe( false );
	} );

	it( 'ignores a reply to a request sent before a later save', () => {
		let props = {
			postId: POST_ID,
			syncOutcome: queued,
			shareStatus: notShared,
			savedAt: '2026-11-11T16:42:44',
		};
		const { result, rerender } = renderHook( () =>
			useBusinessProfileSyncStatus( props )
		);

		act( () => {
			doAction( 'heartbeat.send', {} );
		} );
		props = { ...props, savedAt: '2026-11-11T16:43:47' };
		rerender();
		act( () => {
			doAction( 'heartbeat.tick', statusResponse( rejected, notShared ) );
		} );

		expect( result.current.syncOutcome ).toBe( queued );
		expect( beat() ).toEqual( { gla_gbp_sync_status: POST_ID } );
	} );

	it( 'asks again after a save that queues another send', () => {
		let props = {
			postId: POST_ID,
			syncOutcome: queued,
			shareStatus: notShared,
			savedAt: '2026-11-11T16:42:44',
		};
		const { result, rerender } = renderHook( () =>
			useBusinessProfileSyncStatus( props )
		);

		beat( statusResponse( rejected, notShared ) );
		expect( result.current.syncOutcome ).toBe( rejected );

		props = { ...props, savedAt: '2026-11-11T16:45:05' };
		rerender();

		expect( result.current.syncOutcome ).toBe( queued );
		runFirstCheckTimer();
		expect( window.wp.heartbeat.connectNow ).toHaveBeenCalledTimes( 1 );
		expect( beat() ).toEqual( { gla_gbp_sync_status: POST_ID } );
	} );

	it( "doesn't send the early check once the send settles before it", () => {
		renderHook( () =>
			useBusinessProfileSyncStatus( {
				postId: POST_ID,
				syncOutcome: queued,
				shareStatus: notShared,
			} )
		);

		beat( statusResponse( { outcome: 'success' }, shared ) );
		runFirstCheckTimer();

		expect( window.wp.heartbeat.connectNow ).not.toHaveBeenCalled();
	} );

	it( 'stops asking, and drops the early check, when unmounted', () => {
		const { unmount } = renderHook( () =>
			useBusinessProfileSyncStatus( {
				postId: POST_ID,
				syncOutcome: queued,
				shareStatus: notShared,
			} )
		);

		unmount();
		runFirstCheckTimer();

		expect( beat() ).toEqual( {} );
		expect( window.wp.heartbeat.connectNow ).not.toHaveBeenCalled();
	} );

	it( "doesn't ask without the post's ID", () => {
		renderHook( () =>
			useBusinessProfileSyncStatus( {
				postId: 0,
				syncOutcome: queued,
				shareStatus: notShared,
			} )
		);

		runFirstCheckTimer();

		expect( window.wp.heartbeat.connectNow ).not.toHaveBeenCalled();
		expect( beat() ).toEqual( {} );
	} );

	it( "keeps the editor's queued outcome where Heartbeat isn't loaded", () => {
		delete window.wp;

		const { result } = renderHook( () =>
			useBusinessProfileSyncStatus( {
				postId: POST_ID,
				syncOutcome: queued,
				shareStatus: notShared,
			} )
		);

		expect( beat() ).toEqual( {} );
		expect( result.current.syncOutcome ).toBe( queued );
	} );
} );
