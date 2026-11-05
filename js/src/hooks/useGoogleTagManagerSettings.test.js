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
import useGoogleTagManagerAccount from './useGoogleTagManagerAccount';
import { API_NAMESPACE } from '~/data/constants';
import { GOOGLE_TAG_MANAGER_ACCOUNT_STATUS } from '~/constants';

jest.mock( '@wordpress/api-fetch', () => {
	const impl = jest.fn().mockName( '@wordpress/api-fetch' );
	impl.use = jest.fn().mockName( 'apiFetch.use' );
	return impl;
} );

jest.mock( './useGoogleTagManagerAccount', () =>
	jest.fn().mockName( 'useGoogleTagManagerAccount' )
);

/**
 * Mocks `useGoogleTagManagerAccount`.
 *
 * @param {string} status The connection status to mock.
 */
function mockAccountStatus( status ) {
	useGoogleTagManagerAccount.mockReturnValue( {
		account: { status },
		hasFinishedResolution: true,
	} );
}

// The store caches resolutions across tests, so the case that never requests the settings runs first.
describe( 'useGoogleTagManagerSettings', () => {
	beforeEach( () => {
		jest.clearAllMocks();
		apiFetch.mockResolvedValue( { snippetInjectionEnabled: false } );
	} );

	it.each( [
		GOOGLE_TAG_MANAGER_ACCOUNT_STATUS.DISCONNECTED,
		GOOGLE_TAG_MANAGER_ACCOUNT_STATUS.INCOMPLETE,
	] )(
		'does not request the settings while the connection is %s',
		( status ) => {
			mockAccountStatus( status );

			const { result } = renderHook( () =>
				useGoogleTagManagerSettings()
			);

			expect( result.current ).toEqual( {
				settings: null,
				hasFinishedResolution: true,
			} );
			expect( apiFetch ).not.toHaveBeenCalled();
		}
	);

	it( 'loads the settings once a container is connected', async () => {
		mockAccountStatus( GOOGLE_TAG_MANAGER_ACCOUNT_STATUS.CONNECTED );

		const { result } = renderHook( () => useGoogleTagManagerSettings() );

		await waitFor( () => {
			expect( result.current.settings ).toEqual( {
				snippetInjectionEnabled: false,
			} );
		} );
		expect( result.current.hasFinishedResolution ).toBe( true );
		expect( apiFetch ).toHaveBeenCalledWith( {
			path: `${ API_NAMESPACE }/tag-manager/settings`,
		} );
	} );
} );
