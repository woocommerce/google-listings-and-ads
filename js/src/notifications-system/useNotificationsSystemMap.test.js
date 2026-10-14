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

	describe( 'business-profile-available', () => {
		it( 'returns the Business Profile availability copy', () => {
			const { result } = renderHook( () => useNotificationsSystemMap() );
			const config = result.current[ 'business-profile-available' ];

			expect( config.title ).toBe(
				'Share your WordPress posts on Google'
			);
			expect( config.description ).toBe(
				'Connect your Google Business Profile to publish WordPress posts to your selected business location on Google.'
			);
		} );

		it( 'links the CTA to the Business Profile card on the accounts settings page', () => {
			const { result } = renderHook( () => useNotificationsSystemMap() );
			const { actions } = result.current[ 'business-profile-available' ];

			expect( actions ).toHaveLength( 1 );
			expect( actions[ 0 ] ).toMatchObject( {
				id: 'connect-business-profile',
				href: getAccountsSettingsUrl( {
					'focus-account-card': 'business-profile',
				} ),
				children: 'Connect now',
			} );
		} );
	} );
} );
