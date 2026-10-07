/**
 * External dependencies
 */
import { renderHook } from '@testing-library/react';

/**
 * Internal dependencies
 */
import useNotificationsSystemMap from './useNotificationsSystemMap';
import useGoogleMCAccount from '~/hooks/useGoogleMCAccount';
import { getAccountsSettingsUrl } from '~/utils/urls';

jest.mock( '~/hooks/useGoogleMCAccount', () => jest.fn() );

describe( 'useNotificationsSystemMap', () => {
	beforeEach( () => {
		useGoogleMCAccount.mockReturnValue( {
			hasGoogleMCConnection: true,
			hasFinishedResolution: true,
		} );
	} );

	describe( 'tag-manager-available', () => {
		it( 'returns the Tag Manager availability copy', () => {
			const { result } = renderHook( () => useNotificationsSystemMap() );
			const config = result.current[ 'tag-manager-available' ];

			expect( config.title ).toBe(
				'Manage all your Google tags in one place'
			);
			expect( config.description ).toBe(
				'Connect Google Tag Manager to add and update tracking and marketing tags across your store without editing code.'
			);
		} );

		it( 'links the CTA to the accounts settings page', () => {
			const { result } = renderHook( () => useNotificationsSystemMap() );
			const { actions } = result.current[ 'tag-manager-available' ];

			expect( actions ).toHaveLength( 1 );
			expect( actions[ 0 ] ).toMatchObject( {
				id: 'connect-tag-manager',
				href: getAccountsSettingsUrl(),
				children: 'Connect now',
			} );
		} );
	} );
} );
