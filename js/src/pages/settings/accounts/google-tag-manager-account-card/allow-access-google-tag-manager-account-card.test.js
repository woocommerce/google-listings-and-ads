/**
 * External dependencies
 */
import '@testing-library/jest-dom';
import { render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';

/**
 * Internal dependencies
 */
import AllowAccessGoogleTagManagerAccountCard from './allow-access-google-tag-manager-account-card';
import useGoogleTagManagerConnectRedirect from './hooks/useGoogleTagManagerConnectRedirect';

jest.mock( './hooks/useGoogleTagManagerConnectRedirect', () =>
	jest.fn().mockName( 'useGoogleTagManagerConnectRedirect' )
);

describe( 'AllowAccessGoogleTagManagerAccountCard', () => {
	let connect;

	beforeEach( () => {
		connect = jest.fn().mockName( 'connect' );
		useGoogleTagManagerConnectRedirect.mockReturnValue( {
			connect,
			loading: false,
		} );
	} );

	it( 'shows the scope-grant message and an "Allow access" button', async () => {
		const user = userEvent.setup();

		render( <AllowAccessGoogleTagManagerAccountCard /> );

		expect(
			screen.getByText(
				'Google needs your permission before this store can connect to your Google Tag Manager account.'
			)
		).toBeInTheDocument();

		const button = screen.getByRole( 'button', { name: 'Allow access' } );
		expect( button ).toBeEnabled();

		await user.click( button );
		expect( connect ).toHaveBeenCalledTimes( 1 );
	} );

	it( 'shows a loading state while the request is in flight', () => {
		useGoogleTagManagerConnectRedirect.mockReturnValue( {
			connect,
			loading: true,
		} );

		render( <AllowAccessGoogleTagManagerAccountCard /> );

		expect(
			screen.getByRole( 'button', { name: 'Allow access' } )
		).toBeDisabled();
	} );
} );
