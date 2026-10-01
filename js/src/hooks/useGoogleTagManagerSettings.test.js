/**
 * External dependencies
 */
import { renderHook, waitFor } from '@testing-library/react';
import apiFetch from '@wordpress/api-fetch';

/**
 * Internal dependencies
 */
import '~/data';
import useGoogleTagManagerSettings from './useGoogleTagManagerSettings';
import { API_NAMESPACE } from '~/data/constants';

jest.mock( '@wordpress/api-fetch', () => {
	const impl = jest.fn().mockName( '@wordpress/api-fetch' );
	impl.use = jest.fn().mockName( 'apiFetch.use' );
	return impl;
} );

describe( 'useGoogleTagManagerSettings', () => {
	it( 'loads the settings from the settings endpoint', async () => {
		apiFetch.mockResolvedValue( { snippetInjectionEnabled: false } );

		const { result } = renderHook( () => useGoogleTagManagerSettings() );

		expect( result.current.hasFinishedResolution ).toBe( false );

		await waitFor( () => {
			expect( result.current.hasFinishedResolution ).toBe( true );
		} );
		expect( result.current.settings ).toEqual( {
			snippetInjectionEnabled: false,
		} );
		expect( apiFetch ).toHaveBeenCalledWith( {
			path: `${ API_NAMESPACE }/tag-manager/settings`,
		} );
	} );
} );
