/**
 * External dependencies
 */
import { act, renderHook } from '@testing-library/react';

/**
 * Internal dependencies
 */
import useConnectGoogleTagManagerContainer from './useConnectGoogleTagManagerContainer';
import useApiFetchCallback from '~/hooks/useApiFetchCallback';
import { handleApiError } from '~/utils/handleError';
import useRefreshGoogleTagManagerConnection from './useRefreshGoogleTagManagerConnection';

jest.mock( '~/hooks/useApiFetchCallback' );
jest.mock( '~/utils/handleError', () => ( {
	...jest.requireActual( '~/utils/handleError' ),
	handleApiError: jest.fn().mockName( 'handleApiError' ),
} ) );
jest.mock( './useRefreshGoogleTagManagerConnection', () =>
	jest.fn().mockName( 'useRefreshGoogleTagManagerConnection' )
);

/**
 * Calls `connectContainer` inside `act` so its state updates are flushed.
 *
 * @param {Object} result The `result` returned by `renderHook`.
 * @param {string} [id] The container ID.
 */
async function connect( result, id = '98765432' ) {
	await act( async () => {
		await result.current[ 0 ]( id );
	} );
}

describe( 'useConnectGoogleTagManagerContainer', () => {
	let fetchConnectContainer;
	let refreshConnection;

	beforeEach( () => {
		jest.clearAllMocks();

		fetchConnectContainer = jest
			.fn()
			.mockName( 'fetchConnectContainer' )
			.mockResolvedValue( { status: 'success' } );
		useApiFetchCallback.mockReturnValue( [
			fetchConnectContainer,
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

	it( 'connects the container with the given ID, then refreshes the connection', async () => {
		const { result } = renderHook( () =>
			useConnectGoogleTagManagerContainer()
		);

		await connect( result, '98765432' );

		expect( useApiFetchCallback ).toHaveBeenCalledWith( {
			path: '/wc/gla/tag-manager/containers',
			method: 'POST',
		} );
		expect( fetchConnectContainer ).toHaveBeenCalledWith( {
			data: { id: '98765432' },
		} );
		expect( refreshConnection ).toHaveBeenCalledTimes( 1 );
		expect( handleApiError ).not.toHaveBeenCalled();
	} );

	it( 'reports the error and does not refresh when the request fails', async () => {
		const error = new Error( 'Request failed' );
		fetchConnectContainer.mockRejectedValueOnce( error );
		const { result } = renderHook( () =>
			useConnectGoogleTagManagerContainer()
		);

		await connect( result );

		expect( handleApiError ).toHaveBeenCalledWith(
			error,
			'There was an error connecting your Google Tag Manager container.'
		);
		expect( refreshConnection ).not.toHaveBeenCalled();
	} );

	it.each( [
		[ 'the request', true, false ],
		[ 'the refresh', false, true ],
		[ 'neither', false, false ],
	] )(
		'reports loading while %s is in progress',
		( _, isConnecting, isRefreshing ) => {
			useApiFetchCallback.mockReturnValue( [
				fetchConnectContainer,
				{ loading: isConnecting },
			] );
			useRefreshGoogleTagManagerConnection.mockReturnValue( {
				refreshConnection,
				loading: isRefreshing,
			} );

			const { result } = renderHook( () =>
				useConnectGoogleTagManagerContainer()
			);

			expect( result.current[ 1 ].loading ).toBe(
				isConnecting || isRefreshing
			);
		}
	);
} );
