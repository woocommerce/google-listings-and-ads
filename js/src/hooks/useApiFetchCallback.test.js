/**
 * External dependencies
 */
import { renderHook, act } from '@testing-library/react';
import apiFetch from '@wordpress/api-fetch';

/**
 * Internal dependencies
 */
import useApiFetchCallback from './useApiFetchCallback';

jest.mock( '@wordpress/api-fetch' );

/**
 * Creates a minimal `Response`-like object, as returned by `apiFetch` with `parse: false`.
 *
 * @param {Object} body The JSON body of the response.
 * @param {number} [status=200] The HTTP status code.
 * @return {Object} The mocked response.
 */
const mockResponse = ( body, status = 200 ) => {
	const response = {
		status,
		json: () => Promise.resolve( body ),
	};
	response.clone = () => response;
	return response;
};

describe( 'useApiFetchCallback', () => {
	const options = {
		path: '/wc/gla/mc/accounts',
		method: 'POST',
		data: { id: 1 },
	};

	beforeEach( () => {
		jest.clearAllMocks();
		apiFetch.mockResolvedValue( mockResponse( { id: 123 } ) );
	} );

	it( 'calls apiFetch with the hook options and returns the response body', async () => {
		const { result } = renderHook( () => useApiFetchCallback( options ) );
		const [ fetchCallback ] = result.current;

		let body;
		await act( async () => {
			body = await fetchCallback();
		} );

		expect( apiFetch ).toHaveBeenCalledWith( {
			...options,
			parse: false,
		} );
		expect( body ).toEqual( { id: 123 } );
		expect( result.current[ 1 ] ).toMatchObject( {
			loading: false,
			data: { id: 123 },
		} );
	} );

	it( 'returns the response instead of the body when `parse: false` is set on the hook', async () => {
		const response = mockResponse( { id: 123 } );
		apiFetch.mockResolvedValue( response );

		const { result } = renderHook( () =>
			useApiFetchCallback( { ...options, parse: false } )
		);

		let returned;
		await act( async () => {
			returned = await result.current[ 0 ]();
		} );

		expect( returned ).toBe( response );
	} );

	it( 'overwrites `data` for a single call', async () => {
		const { result } = renderHook( () => useApiFetchCallback( options ) );

		await act( async () => {
			await result.current[ 0 ]( { data: { id: 2 } } );
		} );

		expect( apiFetch ).toHaveBeenCalledWith(
			expect.objectContaining( { data: { id: 2 } } )
		);
	} );

	it( 'adds `query` to the path as query args, replacing args with the same keys', async () => {
		const { result } = renderHook( () =>
			useApiFetchCallback( {
				path: '/wc/gla/mc/accounts?page=1&per_page=10',
			} )
		);

		await act( async () => {
			await result.current[ 0 ]( { query: { page: 2 } } );
		} );

		const { path, query } = apiFetch.mock.calls[ 0 ][ 0 ];
		expect( path ).toBe( '/wc/gla/mc/accounts?page=2&per_page=10' );
		expect( query ).toBeUndefined();
	} );

	it( 'adds `query` to the url when the hook uses `url` instead of `path`', async () => {
		const { result } = renderHook( () =>
			useApiFetchCallback( { url: 'https://example.com/wp-json/wc/gla' } )
		);

		await act( async () => {
			await result.current[ 0 ]( { query: { page: 2 } } );
		} );

		expect( apiFetch.mock.calls[ 0 ][ 0 ].url ).toBe(
			'https://example.com/wp-json/wc/gla?page=2'
		);
	} );

	it( 'ignores other options passed at call time', async () => {
		const { result } = renderHook( () => useApiFetchCallback( options ) );

		let returned;
		await act( async () => {
			returned = await result.current[ 0 ]( {
				path: '/wc/gla/other',
				method: 'DELETE',
				parse: false,
			} );
		} );

		expect( apiFetch ).toHaveBeenCalledWith( {
			...options,
			parse: false,
		} );
		// `parse: false` passed at call time is ignored, so the body is still returned.
		expect( returned ).toEqual( { id: 123 } );
	} );

	it( 'stores the error body and rethrows it when the request fails', async () => {
		apiFetch.mockRejectedValue(
			mockResponse( { message: 'Invalid account' }, 400 )
		);

		const { result } = renderHook( () => useApiFetchCallback( options ) );

		await act( async () => {
			await expect( result.current[ 0 ]() ).rejects.toEqual( {
				message: 'Invalid account',
			} );
		} );

		expect( result.current[ 1 ] ).toMatchObject( {
			loading: false,
			error: { message: 'Invalid account' },
		} );
		expect( result.current[ 1 ].response.status ).toBe( 400 );
	} );

	it( 'keeps `reset` and the callback stable across renders with equal options and initial state', () => {
		const { result, rerender } = renderHook( () =>
			// New but deep-equal objects on every render, as `useApiFetchEffect` passes.
			useApiFetchCallback( { ...options }, { loading: true } )
		);
		const [ firstCallback, { reset: firstReset } ] = result.current;

		rerender();

		expect( result.current[ 0 ] ).toBe( firstCallback );
		expect( result.current[ 1 ].reset ).toBe( firstReset );
	} );

	it( 'resets to the initial state merged with the given reset state', async () => {
		const { result } = renderHook( () =>
			useApiFetchCallback( options, { loading: true } )
		);

		await act( async () => {
			await result.current[ 0 ]();
		} );
		expect( result.current[ 1 ].data ).toEqual( { id: 123 } );

		act( () => {
			result.current[ 1 ].reset( { data: 'reset' } );
		} );

		expect( result.current[ 1 ] ).toMatchObject( {
			loading: true,
			error: undefined,
			data: 'reset',
			response: undefined,
		} );
	} );
} );
