/**
 * External dependencies
 */
import '@testing-library/jest-dom';
import { render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';

/**
 * Internal dependencies
 */
import GoogleComboAccountCard from './';
import useGoogleAccount from '~/hooks/useGoogleAccount';
import useGoogleMCAccount from '~/hooks/useGoogleMCAccount';

jest.mock( '~/hooks/useGoogleAccount', () =>
	jest.fn().mockName( 'useGoogleAccount' )
);

jest.mock( '~/hooks/useGoogleMCAccount', () =>
	jest.fn().mockName( 'useGoogleMCAccount' )
);

jest.mock( './connected-google-combo-account-card', () =>
	jest
		.fn()
		.mockName( 'ConnectedGoogleComboAccountCard' )
		.mockImplementation( () => (
			<div>--Test--ConnectedGoogleComboAccountCard</div>
		) )
);

describe( 'GoogleComboAccountCard', () => {
	it( 'Should render a spinner when the Google or GMC account connection is loading', () => {
		useGoogleAccount.mockReturnValue( { hasFinishedResolution: false } );
		useGoogleMCAccount.mockReturnValue( { hasFinishedResolution: false } );
		const { rerender } = render( <GoogleComboAccountCard /> );

		expect(
			screen.getByRole( 'status', { name: /Loading/ } )
		).toBeInTheDocument();

		useGoogleAccount.mockReturnValue( { hasFinishedResolution: true } );
		rerender( <GoogleComboAccountCard /> );

		expect(
			screen.getByRole( 'status', { name: /Loading/ } )
		).toBeInTheDocument();

		useGoogleMCAccount.mockReturnValue( { hasFinishedResolution: true } );
		rerender( <GoogleComboAccountCard /> );

		expect(
			screen.queryByRole( 'status', { name: /Loading/ } )
		).not.toBeInTheDocument();
	} );

	it( 'Should render a card for connecting to Google account', async () => {
		useGoogleAccount.mockReturnValue( {
			hasFinishedResolution: true,
			google: { active: 'no' },
		} );
		useGoogleMCAccount.mockReturnValue( { hasFinishedResolution: true } );

		const user = userEvent.setup();
		const { rerender } = render( <GoogleComboAccountCard disabled /> );
		const checkbox = screen.getByRole( 'checkbox', { name: /I accept/ } );
		const button = screen.getByRole( 'button', { name: 'Connect' } );
		const text = screen.getByText(
			/You will be prompted to give WooCommerce access to your Google account/
		);

		expect( text ).toBeInTheDocument();
		expect( checkbox ).toBeDisabled();
		expect( button ).toBeInTheDocument();

		rerender( <GoogleComboAccountCard /> );

		expect( text ).toBeInTheDocument();
		expect( checkbox ).toBeEnabled();
		expect( button ).toBeInTheDocument();

		await user.click( checkbox );

		expect( button ).toBeEnabled();
	} );

	it( 'Should render a card for requesting all required access to Google account', () => {
		useGoogleAccount.mockReturnValue( {
			hasFinishedResolution: true,
			google: { active: 'yes' },
			scope: { onboardingRequired: false },
		} );
		useGoogleMCAccount.mockReturnValue( { hasFinishedResolution: true } );
		render( <GoogleComboAccountCard /> );

		expect(
			screen.getByRole( 'button', { name: 'Allow full access' } )
		).toBeInTheDocument();
		expect(
			screen.getByText(
				/You did not allow WooCommerce sufficient access to your Google account/
			)
		).toBeInTheDocument();
	} );

	it( 'Should render a card for re-asking to connect to WPComApp', () => {
		useGoogleAccount.mockReturnValue( {
			hasFinishedResolution: true,
			google: { active: 'yes' },
			scope: { onboardingRequired: true },
		} );
		useGoogleMCAccount.mockReturnValue( {
			hasFinishedResolution: true,
			googleMCAccount: {},
			isWPComAppGranted: false,
		} );
		render( <GoogleComboAccountCard /> );

		expect(
			screen.getByRole( 'button', { name: 'Grant access' } )
		).toBeInTheDocument();
		expect(
			screen.getByText(
				/Granting Google access to your WooCommerce store is required/
			)
		).toBeInTheDocument();
	} );

	it( 'Should render a card for the connected Google account', () => {
		useGoogleAccount.mockReturnValue( {
			hasFinishedResolution: true,
			google: { active: 'yes' },
			scope: { onboardingRequired: true },
		} );
		useGoogleMCAccount.mockReturnValue( {
			hasFinishedResolution: true,
			isWPComAppGranted: true,
		} );
		render( <GoogleComboAccountCard /> );

		expect(
			screen.getByText( '--Test--ConnectedGoogleComboAccountCard' )
		).toBeInTheDocument();
	} );
} );
