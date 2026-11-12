/**
 * External dependencies
 */
import { renderHook } from '@testing-library/react';

/**
 * Internal dependencies
 */
import useBusinessProfileSyncStatus from './useBusinessProfileSyncStatus';
import useBoundedPolling from './useBoundedPolling';

jest.mock( './useBoundedPolling', () =>
	jest.fn().mockName( 'useBoundedPolling' )
);

const REST_PATH = '/wp/v2/posts/123';

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

describe( 'useBusinessProfileSyncStatus', () => {
	let start;

	/**
	 * Sets what the mocked `useBoundedPolling` reports.
	 *
	 * @param {Object} [state] The polling state.
	 */
	const mockPolling = ( state = {} ) => {
		useBoundedPolling.mockReturnValue( {
			start,
			data: undefined,
			hasReachedMaxAttempts: false,
			...state,
		} );
	};

	beforeEach( () => {
		jest.clearAllMocks();
		start = jest.fn().mockName( 'start' );
		mockPolling();
	} );

	it( "returns the editor's status and doesn't check when the send isn't queued", () => {
		const { result } = renderHook( () =>
			useBusinessProfileSyncStatus( {
				restPath: REST_PATH,
				syncOutcome: rejected,
				shareStatus: shared,
			} )
		);

		expect( start ).not.toHaveBeenCalled();
		expect( result.current ).toEqual( {
			syncOutcome: rejected,
			isShared: true,
			searchUrl: shared.search_url,
			isChecking: false,
		} );
	} );

	it( 'checks the share fields in the edit context while the send is queued', () => {
		const { result } = renderHook( () =>
			useBusinessProfileSyncStatus( {
				restPath: REST_PATH,
				syncOutcome: queued,
				shareStatus: notShared,
			} )
		);

		expect( start ).toHaveBeenCalledTimes( 1 );
		expect( useBoundedPolling ).toHaveBeenCalledWith( {
			path: '/wp/v2/posts/123?context=edit&_fields=gla_gbp_sync_outcome%2Cgla_gbp_share_status',
			intervalSeconds: 5,
			maxAttempts: 18,
			isDone: expect.any( Function ),
		} );
		expect( result.current.syncOutcome ).toBe( queued );
		expect( result.current.isChecking ).toBe( true );
	} );

	it( 'stops checking once the send settles', () => {
		renderHook( () =>
			useBusinessProfileSyncStatus( {
				restPath: REST_PATH,
				syncOutcome: queued,
				shareStatus: notShared,
			} )
		);

		const { isDone } = useBoundedPolling.mock.calls[ 0 ][ 0 ];

		expect( isDone( { gla_gbp_sync_outcome: queued } ) ).toBe( false );
		expect( isDone( { gla_gbp_sync_outcome: rejected } ) ).toBe( true );
		expect( isDone( { gla_gbp_sync_outcome: null } ) ).toBe( true );
	} );

	it( 'returns the checked outcome and link once the send settles', () => {
		const { result, rerender } = renderHook( () =>
			useBusinessProfileSyncStatus( {
				restPath: REST_PATH,
				syncOutcome: queued,
				shareStatus: notShared,
			} )
		);

		mockPolling( {
			data: {
				gla_gbp_sync_outcome: { outcome: 'success' },
				gla_gbp_share_status: shared,
			},
		} );
		rerender();

		expect( result.current ).toEqual( {
			syncOutcome: { outcome: 'success' },
			isShared: true,
			searchUrl: shared.search_url,
			isChecking: false,
		} );
	} );

	it( 'keeps the queued outcome, without checking, once the checks run out', () => {
		const { result, rerender } = renderHook( () =>
			useBusinessProfileSyncStatus( {
				restPath: REST_PATH,
				syncOutcome: queued,
				shareStatus: notShared,
			} )
		);

		mockPolling( {
			data: {
				gla_gbp_sync_outcome: queued,
				gla_gbp_share_status: notShared,
			},
			hasReachedMaxAttempts: true,
		} );
		rerender();

		expect( result.current.syncOutcome ).toEqual( queued );
		expect( result.current.isChecking ).toBe( false );
	} );

	it( "uses a later save's own outcome, not data checked for an earlier one", () => {
		let props = {
			restPath: REST_PATH,
			syncOutcome: queued,
			shareStatus: notShared,
			savedAt: '2026-11-11T16:42:44',
		};
		const { result, rerender } = renderHook( () =>
			useBusinessProfileSyncStatus( props )
		);

		mockPolling( {
			data: {
				gla_gbp_sync_outcome: { outcome: 'success' },
				gla_gbp_share_status: shared,
			},
		} );
		props = {
			...props,
			syncOutcome: rejected,
			savedAt: '2026-11-11T16:43:47',
		};
		rerender();

		expect( result.current.syncOutcome ).toBe( rejected );
		expect( result.current.isShared ).toBe( false );
	} );

	it( 'checks again after a save that queues another send, even with the same queued outcome', () => {
		// The editor's data store keeps the same object for a value that didn't change, so a
		// post queued both before and after a save hands back the very same outcome object.
		let props = {
			restPath: REST_PATH,
			syncOutcome: queued,
			shareStatus: notShared,
			savedAt: '2026-11-11T16:42:44',
		};
		const { result, rerender } = renderHook( () =>
			useBusinessProfileSyncStatus( props )
		);

		mockPolling( {
			data: {
				gla_gbp_sync_outcome: rejected,
				gla_gbp_share_status: notShared,
			},
		} );
		rerender();
		expect( result.current.syncOutcome ).toBe( rejected );

		// Like the real `useBoundedPolling`, starting a new round clears the previous round's data.
		start.mockImplementation( () => {
			mockPolling();
		} );
		props = { ...props, savedAt: '2026-11-11T16:45:05' };
		rerender();

		expect( start ).toHaveBeenCalledTimes( 2 );
		expect( result.current.syncOutcome ).toBe( queued );
		expect( result.current.isChecking ).toBe( true );
	} );

	it( "doesn't check without the post's REST route", () => {
		const { result } = renderHook( () =>
			useBusinessProfileSyncStatus( {
				restPath: null,
				syncOutcome: queued,
				shareStatus: notShared,
			} )
		);

		expect( start ).not.toHaveBeenCalled();
		expect( result.current.isChecking ).toBe( false );
	} );
} );
