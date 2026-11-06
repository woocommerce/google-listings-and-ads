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
import TYPES from '~/data/action-types';
import { handleApiError } from '~/utils/handleError';

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

describe( 'disconnectGoogleBusinessProfileAccount', () => {
	beforeEach( () => {
		jest.clearAllMocks();
		apiFetch.mockResolvedValue( {} );
	} );

	it( 'requests the disconnect endpoint and flags related resolvers for invalidation', async () => {
		const { result } = renderHook( () => useAppDispatch() );

		const response =
			await result.current.disconnectGoogleBusinessProfileAccount();

		expect( apiFetch ).toHaveBeenCalledWith( {
			path: `${ API_NAMESPACE }/business-profile/connection`,
			method: 'DELETE',
		} );
		expect( response ).toEqual( {
			type: TYPES.DISCONNECT_ACCOUNTS_GOOGLE_BUSINESS_PROFILE,
			invalidateRelatedState: true,
		} );
	} );

	it( 'reports and rethrows the error when the disconnect request fails', async () => {
		const error = new Error( 'Request failed' );
		apiFetch.mockRejectedValue( error );

		const { result } = renderHook( () => useAppDispatch() );

		await expect(
			result.current.disconnectGoogleBusinessProfileAccount()
		).rejects.toBe( error );

		expect( handleApiError ).toHaveBeenCalledWith(
			error,
			'Unable to disconnect your Google Business Profile account.'
		);
	} );
} );
