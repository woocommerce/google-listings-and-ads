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

describe( 'updateGoogleTagManagerSnippetInjection', () => {
	beforeEach( () => {
		jest.clearAllMocks();
	} );

	it( 'posts the new value and receives the stored value from the response', async () => {
		apiFetch.mockResolvedValue( { snippetInjectionEnabled: false } );

		const { result } = renderHook( () => useAppDispatch() );

		const response =
			await result.current.updateGoogleTagManagerSnippetInjection(
				false
			);

		expect( apiFetch ).toHaveBeenCalledWith( {
			path: `${ API_NAMESPACE }/tag-manager/settings`,
			method: 'POST',
			data: { snippet_injection_enabled: false },
		} );
		expect( response ).toEqual( {
			type: TYPES.UPDATE_GOOGLE_TAG_MANAGER_SNIPPET_INJECTION,
			enabled: false,
		} );
	} );

	it( 'reports and rethrows the error when the request fails', async () => {
		const error = new Error( 'Request failed' );
		apiFetch.mockRejectedValue( error );

		const { result } = renderHook( () => useAppDispatch() );

		await expect(
			result.current.updateGoogleTagManagerSnippetInjection( true )
		).rejects.toBe( error );

		expect( handleApiError ).toHaveBeenCalledWith(
			error,
			'Unable to update the Google Tag Manager snippet setting.'
		);
	} );
} );
