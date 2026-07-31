/**
 * External dependencies
 */
import { renderHook } from '@testing-library/react';
import apiFetch from '@wordpress/api-fetch';

/**
 * Internal dependencies
 */
import { useAppDispatch } from '~/data';
import { API_NAMESPACE } from '~/data/constants';

jest.mock( '@wordpress/api-fetch', () => {
	const impl = jest.fn().mockName( '@wordpress/api-fetch' );
	impl.use = jest.fn().mockName( 'apiFetch.use' );
	return impl;
} );

jest.mock( '~/utils/handleError', () => {
	const impl = jest.fn().mockName( '~/utils/handleError' );
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

	it( 'Throws the error when the request fails', async () => {
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
