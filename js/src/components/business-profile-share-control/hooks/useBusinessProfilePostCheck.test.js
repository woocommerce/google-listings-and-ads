/**
 * External dependencies
 */
import { renderHook, waitFor } from '@testing-library/react';
import apiFetch from '@wordpress/api-fetch';

/**
 * Internal dependencies
 */
import useBusinessProfilePostCheck from './useBusinessProfilePostCheck';

jest.mock( '@wordpress/api-fetch', () => jest.fn() );

const POST_ID = 123;

const shared = {
	is_shared: true,
	search_url: 'https://local.google.com/place?id=1&use=posts',
};

const notShared = { is_shared: false, search_url: '' };

const notFound = {
	outcome: 'not-found',
	message: '',
	image_omitted: false,
	display_message: 'The post no longer exists on Google Business Profile.',
	display_status: 'error',
};

const unlinkedPost = {
	checked: true,
	check_failure: null,
	is_share_enabled: false,
	share_status: notShared,
	sync_outcome: notFound,
};

describe( 'useBusinessProfilePostCheck', () => {
	beforeEach( () => {
		apiFetch.mockReset();
	} );

	it( 'checks a shared post once, and returns its state once Google answers', async () => {
		apiFetch.mockResolvedValue( unlinkedPost );

		const { result, rerender } = renderHook(
			( props ) => useBusinessProfilePostCheck( props ),
			{
				initialProps: {
					postId: POST_ID,
					shareStatus: shared,
					syncOutcome: null,
				},
			}
		);

		expect( result.current.isChecking ).toBe( true );
		expect( apiFetch ).toHaveBeenCalledWith( {
			path: '/wc/gla/business-profile/posts/123/check',
			method: 'POST',
		} );

		await waitFor( () => {
			expect( result.current.isChecking ).toBe( false );
		} );
		expect( result.current.checkedPost ).toEqual( unlinkedPost );
		expect( result.current.checkFailure ).toBeNull();

		rerender( {
			postId: POST_ID,
			shareStatus: notShared,
			syncOutcome: notFound,
		} );

		expect( apiFetch ).toHaveBeenCalledTimes( 1 );
	} );

	it( 'does not check a post that has not been shared', () => {
		const { result } = renderHook( () =>
			useBusinessProfilePostCheck( {
				postId: POST_ID,
				shareStatus: notShared,
				syncOutcome: null,
			} )
		);

		expect( apiFetch ).not.toHaveBeenCalled();
		expect( result.current ).toEqual( {
			isChecking: false,
			checkedPost: null,
			checkFailure: null,
		} );
	} );

	it.each( [ 'queued', 'unshare-queued' ] )(
		'does not check a post while its %s job is waiting to run',
		( outcome ) => {
			const { result } = renderHook( () =>
				useBusinessProfilePostCheck( {
					postId: POST_ID,
					shareStatus: shared,
					syncOutcome: { outcome },
				} )
			);

			expect( apiFetch ).not.toHaveBeenCalled();
			expect( result.current.isChecking ).toBe( false );
		}
	);

	it( 'does not check a post that is shared later in the same session', () => {
		const { rerender } = renderHook(
			( props ) => useBusinessProfilePostCheck( props ),
			{
				initialProps: {
					postId: POST_ID,
					shareStatus: notShared,
					syncOutcome: null,
				},
			}
		);

		rerender( {
			postId: POST_ID,
			shareStatus: shared,
			syncOutcome: { outcome: 'success' },
		} );

		expect( apiFetch ).not.toHaveBeenCalled();
	} );

	it( 'returns the reason when the server says the check failed', async () => {
		apiFetch.mockResolvedValue( {
			checked: false,
			check_failure: 'reconnect',
			is_share_enabled: true,
			share_status: shared,
			sync_outcome: null,
		} );

		const { result } = renderHook( () =>
			useBusinessProfilePostCheck( {
				postId: POST_ID,
				shareStatus: shared,
				syncOutcome: null,
			} )
		);

		await waitFor( () => {
			expect( result.current.isChecking ).toBe( false );
		} );
		expect( result.current.checkFailure ).toBe( 'reconnect' );
		expect( result.current.checkedPost ).toBeNull();
	} );

	it( 'treats a failed request as a failed check', async () => {
		apiFetch.mockRejectedValue( new Error( 'Network error' ) );

		const { result } = renderHook( () =>
			useBusinessProfilePostCheck( {
				postId: POST_ID,
				shareStatus: shared,
				syncOutcome: null,
			} )
		);

		await waitFor( () => {
			expect( result.current.isChecking ).toBe( false );
		} );
		expect( result.current.checkFailure ).toBe( 'error' );
		expect( result.current.checkedPost ).toBeNull();
	} );
} );
