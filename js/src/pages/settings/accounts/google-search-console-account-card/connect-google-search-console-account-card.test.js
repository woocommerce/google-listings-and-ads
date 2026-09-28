/**
 * External dependencies
 */
import '@testing-library/jest-dom';
import { render, screen } from '@testing-library/react';

/**
 * Internal dependencies
 */
import ConnectGoogleSearchConsoleAccountCard from './connect-google-search-console-account-card';
import useGoogleSearchConsoleConnectRedirect from './hooks/useGoogleSearchConsoleConnectRedirect';

jest.mock( './hooks/useGoogleSearchConsoleConnectRedirect', () =>
	jest.fn().mockName( 'useGoogleSearchConsoleConnectRedirect' )
);

describe( 'ConnectGoogleSearchConsoleAccountCard', () => {
	beforeEach( () => {
		jest.clearAllMocks();

		useGoogleSearchConsoleConnectRedirect.mockReturnValue( {
			connect: jest.fn().mockName( 'handleConnectClick' ),
			loading: false,
		} );
	} );

	it( 'renders the Connect button by default', () => {
		render( <ConnectGoogleSearchConsoleAccountCard /> );

		expect(
			screen.getByRole( 'button', { name: 'Connect' } )
		).toBeInTheDocument();
		expect( screen.queryByText( 'Connecting…' ) ).not.toBeInTheDocument();
	} );

	it( 'renders a "Connecting…" indicator instead of the Connect button while completing setup', () => {
		render( <ConnectGoogleSearchConsoleAccountCard isCompletingSetup /> );

		expect(
			screen.queryByRole( 'button', { name: 'Connect' } )
		).not.toBeInTheDocument();
		expect( screen.getByText( 'Connecting…' ) ).toBeInTheDocument();
	} );
} );
