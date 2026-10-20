/**
 * External dependencies
 */
import { act, renderHook } from '@testing-library/react';

/**
 * Internal dependencies
 */
import useCreateGoogleTagManagerContainer from './useCreateGoogleTagManagerContainer';
import useApiFetchCallback from '~/hooks/useApiFetchCallback';
import useRefreshGoogleTagManagerConnection from './useRefreshGoogleTagManagerConnection';

jest.mock( '~/hooks/useApiFetchCallback' );
jest.mock( './useRefreshGoogleTagManagerConnection', () =>
	jest.fn().mockName( 'useRefreshGoogleTagManagerConnection' )
);

/**
 * Calls `createContainer` inside `act` so its state updates are flushed.
 *
 * @param {Object} result The `result` returned by `renderHook`.
 * @param {string} [name] The container name.
 */
async function create( result, name = 'My store' ) {
	await act( async () => {
		await result.current[ 0 ]( name );
	} );
}

describe( 'useCreateGoogleTagManagerContainer', () => {
	let fetchCreateContainer;
	let refreshConnection;

	/**
	 * Makes the create request fail with the response body the backend sends.
	 *
	 * @param {string} reason The failure reason.
	 */
	function mockCreateFailure( reason ) {
		fetchCreateContainer.mockRejectedValueOnce( {
			code: 'API_ERROR',
			message: 'Raw API message.',
			data: { message: 'Raw API message.', reason },
		} );
	}

	beforeEach( () => {
		jest.clearAllMocks();

		fetchCreateContainer = jest
			.fn()
			.mockName( 'fetchCreateContainer' )
			.mockResolvedValue( { status: 'success' } );
		useApiFetchCallback.mockReturnValue( [
			fetchCreateContainer,
			{ loading: false },
		] );

		refreshConnection = jest
			.fn()
			.mockName( 'refreshConnection' )
			.mockResolvedValue( { status: 'connected' } );
		useRefreshGoogleTagManagerConnection.mockReturnValue( {
			refreshConnection,
			loading: false,
		} );
	} );

	it( 'creates the container with the given name, then refreshes the connection', async () => {
		const { result } = renderHook( () =>
			useCreateGoogleTagManagerContainer()
		);

		await create( result, 'My store' );

		expect( fetchCreateContainer ).toHaveBeenCalledWith( {
			data: { name: 'My store' },
		} );
		expect( refreshConnection ).toHaveBeenCalledTimes( 1 );
		expect( result.current[ 1 ].error ).toBeNull();
	} );

	it( 'only refreshes the connection when the backend answers that a container is already connected', async () => {
		mockCreateFailure( 'already_connected' );
		const { result } = renderHook( () =>
			useCreateGoogleTagManagerContainer()
		);

		await create( result );

		expect( refreshConnection ).toHaveBeenCalledTimes( 1 );
		expect( result.current[ 1 ].error ).toBeNull();
	} );

	it( 'reports the error and does not refresh when the create request fails', async () => {
		mockCreateFailure( 'quota_exceeded' );
		const { result } = renderHook( () =>
			useCreateGoogleTagManagerContainer()
		);

		await create( result );

		expect( refreshConnection ).not.toHaveBeenCalled();
		expect( result.current[ 1 ].error ).not.toBeNull();
	} );

	it.each( [
		[
			'insufficient_scope',
			"Your Google connection doesn't allow creating Google Tag Manager containers.",
			false,
		],
		[
			'permission_denied',
			"Your Google account doesn't have permission to create containers in this Google Tag Manager account.",
			false,
		],
		[
			'quota_exceeded',
			'Google Tag Manager has reached its request limit. Please try again later.',
			true,
		],
		[ 'api_error', 'Raw API message.', true ],
		[ undefined, 'Raw API message.', true ],
	] )(
		'names the cause and whether a retry can help when the create fails with reason %s',
		async ( reason, message, canRetry ) => {
			mockCreateFailure( reason );
			const { result } = renderHook( () =>
				useCreateGoogleTagManagerContainer()
			);

			await create( result );

			expect( result.current[ 1 ].error ).toEqual( {
				message,
				canRetry,
			} );
		}
	);

	it( 'falls back to a generic message when the API gives none', async () => {
		fetchCreateContainer.mockRejectedValueOnce( {} );
		const { result } = renderHook( () =>
			useCreateGoogleTagManagerContainer()
		);

		await create( result );

		expect( result.current[ 1 ].error ).toEqual( {
			message:
				'Unable to create the Google Tag Manager container. Please try again.',
			canRetry: true,
		} );
	} );

	it( 'reports that the connection could not be confirmed when the refresh fails', async () => {
		refreshConnection.mockResolvedValue( undefined );
		const { result } = renderHook( () =>
			useCreateGoogleTagManagerContainer()
		);

		await create( result );

		expect( result.current[ 1 ].error ).toEqual( {
			message:
				"The container was created, but its connection couldn't be confirmed. Please try again.",
			canRetry: true,
		} );
	} );

	it( 'clears the previous error when it tries again', async () => {
		mockCreateFailure( 'api_error' );
		const { result } = renderHook( () =>
			useCreateGoogleTagManagerContainer()
		);

		await create( result );
		expect( result.current[ 1 ].error ).not.toBeNull();

		await create( result );
		expect( result.current[ 1 ].error ).toBeNull();
		expect( fetchCreateContainer ).toHaveBeenCalledTimes( 2 );
	} );

	it.each( [
		[ 'the container is being created', true, false ],
		[ 'the connection is being refreshed', false, true ],
		[ 'nothing is in progress', false, false ],
	] )(
		'reports loading correctly while %s',
		( _, isCreating, isRefreshing ) => {
			useApiFetchCallback.mockReturnValue( [
				fetchCreateContainer,
				{ loading: isCreating },
			] );
			useRefreshGoogleTagManagerConnection.mockReturnValue( {
				refreshConnection,
				loading: isRefreshing,
			} );

			const { result } = renderHook( () =>
				useCreateGoogleTagManagerContainer()
			);

			expect( result.current[ 1 ].loading ).toBe(
				isCreating || isRefreshing
			);
		}
	);
} );
