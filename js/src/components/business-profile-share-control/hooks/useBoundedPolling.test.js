/**
 * External dependencies
 */
import { renderHook, act } from '@testing-library/react';
import apiFetch from '@wordpress/api-fetch';

/**
 * Internal dependencies
 */
import useBoundedPolling from './useBoundedPolling';

jest.mock( '@wordpress/api-fetch', () => jest.fn() );

const isDone = ( data ) => data.status === 'done';

/**
 * Renders the hook checking `/x` every 5 seconds, at most the given number of times.
 *
 * @param {number} [maxAttempts] The most checks per round.
 * @return {Object} The rendered hook.
 */
const renderPolling = ( maxAttempts = 10 ) => {
	return renderHook( () =>
		useBoundedPolling( {
			path: '/x',
			intervalSeconds: 5,
			maxAttempts,
			isDone,
		} )
	);
};

/**
 * Lets pending checks resolve, then moves time on by the given seconds.
 *
 * @param {number} seconds Seconds to advance.
 */
const advance = async ( seconds ) => {
	await act( async () => {
		await jest.advanceTimersByTimeAsync( seconds * 1000 );
	} );
};

describe( 'useBoundedPolling', () => {
	beforeEach( () => {
		jest.useFakeTimers();
		apiFetch.mockReset();
	} );

	afterEach( () => {
		jest.useRealTimers();
	} );

	it( 'does nothing until started', async () => {
		renderPolling();

		await advance( 30 );

		expect( apiFetch ).not.toHaveBeenCalled();
	} );

	it( 'checks right away, then on its interval, until the response is done', async () => {
		apiFetch
			.mockResolvedValueOnce( { status: 'queued' } )
			.mockResolvedValueOnce( { status: 'queued' } )
			.mockResolvedValue( { status: 'done' } );
		const { result } = renderPolling();

		act( () => {
			result.current.start();
		} );
		await advance( 0 );
		expect( apiFetch ).toHaveBeenCalledTimes( 1 );
		expect( apiFetch ).toHaveBeenCalledWith( { path: '/x' } );

		await advance( 5 );
		await advance( 5 );
		await advance( 5 );

		expect( apiFetch ).toHaveBeenCalledTimes( 3 );
		expect( result.current.data ).toEqual( { status: 'done' } );
		expect( result.current.hasReachedMaxAttempts ).toBe( false );
	} );

	it( 'stops after its maximum checks and reports it', async () => {
		apiFetch.mockResolvedValue( { status: 'queued' } );
		const { result } = renderPolling( 3 );

		act( () => {
			result.current.start();
		} );
		await advance( 0 );
		await advance( 5 );
		await advance( 5 );
		await advance( 5 );

		expect( apiFetch ).toHaveBeenCalledTimes( 3 );
		expect( result.current.data ).toEqual( { status: 'queued' } );
		expect( result.current.hasReachedMaxAttempts ).toBe( true );
	} );

	it( 'keeps checking after a failed check, counting it as an attempt', async () => {
		apiFetch
			.mockRejectedValueOnce( new Error( 'Network error' ) )
			.mockResolvedValue( { status: 'done' } );
		const { result } = renderPolling();

		act( () => {
			result.current.start();
		} );
		await advance( 0 );
		expect( result.current.data ).toBeUndefined();

		await advance( 5 );

		expect( apiFetch ).toHaveBeenCalledTimes( 2 );
		expect( result.current.data ).toEqual( { status: 'done' } );
	} );

	it( 'starts a fresh round, dropping the previous data and count, when started again', async () => {
		apiFetch.mockResolvedValue( { status: 'queued' } );
		const { result } = renderPolling( 1 );

		act( () => {
			result.current.start();
		} );
		await advance( 0 );
		expect( result.current.hasReachedMaxAttempts ).toBe( true );

		apiFetch.mockResolvedValue( { status: 'done' } );
		act( () => {
			result.current.start();
		} );
		expect( result.current.data ).toBeUndefined();
		expect( result.current.hasReachedMaxAttempts ).toBe( false );

		await advance( 0 );

		expect( result.current.data ).toEqual( { status: 'done' } );
	} );

	it( 'ignores a response from an earlier round', async () => {
		let resolveFirst;
		apiFetch
			.mockImplementationOnce( () => {
				return new Promise( ( resolve ) => {
					resolveFirst = resolve;
				} );
			} )
			.mockResolvedValue( { status: 'done' } );
		const { result } = renderPolling();

		act( () => {
			result.current.start();
		} );
		act( () => {
			result.current.start();
		} );
		await advance( 0 );

		await act( async () => {
			resolveFirst( { status: 'stale' } );
		} );

		expect( result.current.data ).toEqual( { status: 'done' } );
	} );

	it( 'stops checking once unmounted', async () => {
		apiFetch.mockResolvedValue( { status: 'queued' } );
		const { result, unmount } = renderPolling();

		act( () => {
			result.current.start();
		} );
		await advance( 0 );
		unmount();
		await advance( 30 );

		expect( apiFetch ).toHaveBeenCalledTimes( 1 );
	} );
} );
