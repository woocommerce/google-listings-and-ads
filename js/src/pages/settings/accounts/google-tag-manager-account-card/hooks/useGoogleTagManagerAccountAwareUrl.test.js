/**
 * External dependencies
 */
import '@testing-library/jest-dom';
import { renderHook } from '@testing-library/react';

/**
 * Internal dependencies
 */
import useGoogleTagManagerAccountAwareUrl from './useGoogleTagManagerAccountAwareUrl';
import useGoogleAccount from '~/hooks/useGoogleAccount';

jest.mock( '~/hooks/useGoogleAccount', () =>
	jest.fn().mockName( 'useGoogleAccount' )
);

const URL = 'https://tagmanager.google.com/#/accounts/6002847391';

describe( 'useGoogleTagManagerAccountAwareUrl', () => {
	it( 'returns the plain URL when the connected account email is not yet known', () => {
		useGoogleAccount.mockReturnValue( { google: undefined } );

		const { result } = renderHook( () =>
			useGoogleTagManagerAccountAwareUrl( URL )
		);

		expect( result.current ).toBe( URL );
	} );

	it( 'wraps the URL for the connected Google account when its email is known', () => {
		useGoogleAccount.mockReturnValue( {
			google: { email: 'merchant@example.com' },
		} );

		const { result } = renderHook( () =>
			useGoogleTagManagerAccountAwareUrl( URL )
		);

		expect( result.current ).toBe(
			'https://accounts.google.com/accountchooser?continue=https%3A%2F%2Ftagmanager.google.com%2F%23%2Faccounts%2F6002847391&Email=merchant%40example.com'
		);
	} );
} );
