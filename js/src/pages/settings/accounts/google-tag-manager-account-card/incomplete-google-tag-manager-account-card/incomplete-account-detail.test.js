/**
 * External dependencies
 */
import '@testing-library/jest-dom';
import { render, screen } from '@testing-library/react';

/**
 * Internal dependencies
 */
import IncompleteAccountDetail from './incomplete-account-detail';
import useGoogleAccount from '~/hooks/useGoogleAccount';
import useGoogleTagManagerAccount from '~/hooks/useGoogleTagManagerAccount';

jest.mock( '~/hooks/useGoogleAccount', () =>
	jest.fn().mockName( 'useGoogleAccount' )
);
jest.mock( '~/hooks/useGoogleTagManagerAccount', () =>
	jest.fn().mockName( 'useGoogleTagManagerAccount' )
);
jest.mock( './connect-container', () => () => <p>Mock connect container</p> );
jest.mock( './pending-container-notice', () => ( { containerId } ) => (
	<p>Mock pending container notice for { containerId }</p>
) );

function mockAccount( accountFields ) {
	useGoogleTagManagerAccount.mockReturnValue( {
		account: {
			status: 'incomplete',
			id: '6002847391',
			name: 'Enjoy Mommyhood',
			...accountFields,
		},
	} );
}

describe( 'IncompleteAccountDetail', () => {
	beforeEach( () => {
		jest.clearAllMocks();

		useGoogleAccount.mockReturnValue( { google: undefined } );
		mockAccount( {} );
	} );

	it( 'shows the account name and a link to the account', () => {
		render( <IncompleteAccountDetail /> );

		expect( screen.getByText( /Enjoy Mommyhood/ ) ).toBeInTheDocument();
		expect(
			screen.getByRole( 'link', {
				name: '6002847391 (opens in a new tab)',
			} )
		).toBeInTheDocument();
	} );

	it( 'resolves the account link to the connected Google account when its email is known', () => {
		useGoogleAccount.mockReturnValue( {
			google: { email: 'merchant@example.com' },
		} );

		render( <IncompleteAccountDetail /> );

		expect(
			screen.getByRole( 'link', {
				name: '6002847391 (opens in a new tab)',
			} )
		).toHaveAttribute(
			'href',
			'https://accounts.google.com/accountchooser?continue=https%3A%2F%2Ftagmanager.google.com%2F%23%2Faccounts%2F6002847391&Email=merchant%40example.com'
		);
	} );

	it( 'shows the way to connect a container when none is waiting to be connected', () => {
		render( <IncompleteAccountDetail /> );

		expect(
			screen.getByText( 'Mock connect container' )
		).toBeInTheDocument();
		expect(
			screen.queryByText( /Mock pending container notice/ )
		).not.toBeInTheDocument();
	} );

	it( 'shows the notice for a created container that is not connected, instead of the way to connect one', () => {
		mockAccount( { pendingContainerId: '98765433' } );

		render( <IncompleteAccountDetail /> );

		expect(
			screen.getByText( 'Mock pending container notice for 98765433' )
		).toBeInTheDocument();
		expect(
			screen.queryByText( 'Mock connect container' )
		).not.toBeInTheDocument();
		expect( screen.getByText( /Enjoy Mommyhood/ ) ).toBeInTheDocument();
	} );
} );
