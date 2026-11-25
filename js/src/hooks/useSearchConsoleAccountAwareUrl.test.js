/**
 * External dependencies
 */
import '@testing-library/jest-dom';
import { renderHook } from '@testing-library/react';

/**
 * Internal dependencies
 */
import useSearchConsoleAccountAwareUrl from './useSearchConsoleAccountAwareUrl';
import useGoogleAccount from '~/hooks/useGoogleAccount';

jest.mock( '~/hooks/useGoogleAccount', () =>
	jest.fn().mockName( 'useGoogleAccount' )
);

const getUrl = ( siteUrl ) => `https://example.com/report?site=${ siteUrl }`;

describe( 'useSearchConsoleAccountAwareUrl', () => {
	it( 'returns null when siteUrl is not set', () => {
		useGoogleAccount.mockReturnValue( { google: undefined } );

		const { result } = renderHook( () =>
			useSearchConsoleAccountAwareUrl( undefined, getUrl )
		);

		expect( result.current ).toBeNull();
	} );

	it( 'returns the plain URL when the connected account email is not yet known', () => {
		useGoogleAccount.mockReturnValue( { google: undefined } );

		const { result } = renderHook( () =>
			useSearchConsoleAccountAwareUrl( 'https://example.com/', getUrl )
		);

		expect( result.current ).toBe(
			'https://example.com/report?site=https://example.com/'
		);
	} );

	it( 'wraps the URL for the connected Google account when its email is known', () => {
		useGoogleAccount.mockReturnValue( {
			google: { email: 'merchant@example.com' },
		} );

		const { result } = renderHook( () =>
			useSearchConsoleAccountAwareUrl( 'https://example.com/', getUrl )
		);

		expect( result.current ).toBe(
			'https://accounts.google.com/accountchooser?continue=https%3A%2F%2Fexample.com%2Freport%3Fsite%3Dhttps%3A%2F%2Fexample.com%2F&Email=merchant%40example.com'
		);
	} );
} );
