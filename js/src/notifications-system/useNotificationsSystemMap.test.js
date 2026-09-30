/**
 * External dependencies
 */
import { renderHook } from '@testing-library/react';

/**
 * Internal dependencies
 */
import useGoogleMCAccount from '~/hooks/useGoogleMCAccount';
import { getAccountsSettingsUrl } from '~/utils/urls';
import useNotificationsSystemMap from './useNotificationsSystemMap';

jest.mock( '~/hooks/useGoogleMCAccount', () =>
	jest.fn().mockName( 'useGoogleMCAccount' )
);

describe( 'useNotificationsSystemMap', () => {
	beforeEach( () => {
		useGoogleMCAccount.mockReturnValue( {
			hasGoogleMCConnection: true,
			hasFinishedResolution: true,
		} );
	} );

	it( 'defines the Search Console notification content and Accounts CTA', () => {
		const { result } = renderHook( () => useNotificationsSystemMap() );
		const config = result.current[ 'search-console-not-connected' ];

		expect( config.title ).toBe(
			'Discover how shoppers find you on Google'
		);
		expect( config.description ).toBe(
			'Connect your Search Console profile to see how shoppers find your store in organic Google Search, including clicks, impressions, and top queries.'
		);
		expect( config.actions ).toEqual( [
			{
				id: 'connect-search-console',
				href: getAccountsSettingsUrl(),
				children: 'Connect now',
			},
		] );
	} );
} );
