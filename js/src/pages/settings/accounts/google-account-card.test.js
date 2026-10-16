/**
 * External dependencies
 */
import '@testing-library/jest-dom';
import { render, screen } from '@testing-library/react';

/**
 * Internal dependencies
 */
import GoogleAccountCard from './google-account-card';
import useGoogleAccount from '~/hooks/useGoogleAccount';

jest.mock( '~/hooks/useGoogleAccount' );

describe( 'GoogleAccountCard', () => {
	it( 'renders without crashing when the Google account is not connected', () => {
		// `google` is `undefined` whenever WordPress.com/Jetpack isn't
		// connected yet — the default state for a partially-onboarded store.
		useGoogleAccount.mockReturnValue( { google: undefined } );

		expect( () => render( <GoogleAccountCard /> ) ).not.toThrow();
		expect( screen.queryByText( 'Connected' ) ).not.toBeInTheDocument();
	} );

	it( 'shows the connected email and badge when connected', () => {
		useGoogleAccount.mockReturnValue( {
			google: { email: 'merchant@example.com' },
		} );

		render( <GoogleAccountCard /> );

		expect( screen.getByText( 'merchant@example.com' ) ).toBeVisible();
		expect( screen.getByText( 'Connected' ) ).toBeVisible();
	} );
} );
