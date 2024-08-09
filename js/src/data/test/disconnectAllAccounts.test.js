/**
 * External dependencies
 */
import { renderHook } from '@testing-library/react-hooks';
import apiFetch from '@wordpress/api-fetch';

/**
 * Internal dependencies
 */
import { useAppDispatch } from '.~/data';
import { API_NAMESPACE } from '.~/data/constants';

jest.mock( '@wordpress/api-fetch', () => {
	const impl = jest.fn().mockName( '@wordpress/api-fetch' );
	impl.use = jest.fn().mockName( 'apiFetch.use' );
	return impl;
} );

jest.mock( '.~/utils/handleError', () => {
	const impl = jest.fn().mockName( '.~/utils/handleError' );
	return {
		handleApiError: impl,
	};
} );

describe( 'Disconnect All Accounts', () => {
	const mockFetch = jest
		.fn()
		.mockResolvedValue( { targeted_locations: [ 'ES' ] } );

	beforeEach( () => {
		jest.clearAllMocks();
		apiFetch.mockImplementation( ( args ) => {
			return mockFetch( args );
		} );
	} );

	it( 'Ignore the error if its because missing WPCOM token', async () => {
		mockFetch.mockRejectedValue( {
			errors: {
				[ `${ API_NAMESPACE }/rest-api/authorize` ]: {
					message:
						'No token found associated with the client ID and user',
				},
			},
		} );

		const { result } = renderHook( () => useAppDispatch() );

		const response = await result.current.disconnectAllAccounts();

		expect( mockFetch ).toHaveBeenCalledTimes( 1 );
		expect( mockFetch ).toHaveBeenCalledWith( {
			path: `${ API_NAMESPACE }/connections`,
			method: 'DELETE',
		} );
		expect( response ).toEqual( { type: 'DISCONNECT_ACCOUNTS_ALL' } );
	} );

	it( 'Throw the error unless its related to a missing WPCOM token', async () => {
		mockFetch.mockRejectedValue( {
			errors: {
				[ `${ API_NAMESPACE }/ads/connection` ]: {
					message: 'Error disconnecting the account from Google Ads',
				},
			},
		} );

		const { result } = renderHook( () => useAppDispatch() );

		await expect( result.current.disconnectAllAccounts() ).rejects.toEqual(
			{
				errors: {
					[ `${ API_NAMESPACE }/ads/connection` ]: {
						message:
							'Error disconnecting the account from Google Ads',
					},
				},
			}
		);

		expect( mockFetch ).toHaveBeenCalledTimes( 1 );
		expect( mockFetch ).toHaveBeenCalledWith( {
			path: `${ API_NAMESPACE }/connections`,
			method: 'DELETE',
		} );
	} );
} );
