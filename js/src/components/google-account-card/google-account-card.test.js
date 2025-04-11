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

jest.mock( '~/hooks/useGoogleAccount', () =>
	jest.fn().mockName( 'useGoogleAccount' )
);

jest.mock( './connected-google-account-card', () =>
	jest
		.fn()
		.mockName( 'ConnectedGoogleAccountCard' )
		.mockImplementation( () => (
			<div>--Test--ConnectedGoogleAccountCard</div>
		) )
);

describe( 'GoogleAccountCard', () => {
	it( 'Should render a spinner when the Google account connection data is loading', () => {
		useGoogleAccount.mockReturnValue( { hasFinishedResolution: false } );
		const { rerender } = render( <GoogleAccountCard /> );

		expect(
			screen.getByRole( 'status', { name: /Loading/ } )
		).toBeInTheDocument();

		useGoogleAccount.mockReturnValue( { hasFinishedResolution: true } );
		rerender( <GoogleAccountCard /> );

		expect(
			screen.queryByRole( 'status', { name: /Loading/ } )
		).not.toBeInTheDocument();
	} );

	it( 'Should render a card for connecting to Google account', () => {
		useGoogleAccount.mockReturnValue( {
			hasFinishedResolution: true,
			google: { active: 'no' },
		} );

		render( <GoogleAccountCard /> );

		expect(
			screen.getByRole( 'button', { name: 'Connect' } )
		).toBeInTheDocument();
		expect(
			screen.getByText(
				/You will be prompted to give WooCommerce access to your Google account/
			)
		).toBeInTheDocument();
	} );

	it( 'Should render a card for requesting all required access to Google account', () => {
		useGoogleAccount.mockReturnValue( {
			hasFinishedResolution: true,
			google: { active: 'yes' },
			scope: { reconnectionRequired: false },
		} );
		render( <GoogleAccountCard /> );

		expect(
			screen.getByRole( 'button', { name: 'Allow full access' } )
		).toBeInTheDocument();
		expect(
			screen.getByText(
				/You did not allow WooCommerce sufficient access to your Google account/
			)
		).toBeInTheDocument();
	} );

	it( 'Should render a card for the connected Google account', () => {
		useGoogleAccount.mockReturnValue( {
			hasFinishedResolution: true,
			google: { active: 'yes' },
			scope: { reconnectionRequired: true },
		} );
		render( <GoogleAccountCard /> );
		expect(
			screen.getByText( '--Test--ConnectedGoogleAccountCard' )
		).toBeInTheDocument();
	} );
} );
