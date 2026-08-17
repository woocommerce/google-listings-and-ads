/**
 * External dependencies
 */
import '@testing-library/jest-dom';
import { render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';

/**
 * Internal dependencies
 */
import SearchConsoleAccountCard from './index';
import { SEARCH_CONSOLE_ACCOUNT_STATUS } from '~/constants';
import useSearchConsoleAccount from '~/hooks/useSearchConsoleAccount';
import IncompleteSearchConsoleAccountCard from './incomplete-search-console-account-card';

jest.mock( '~/hooks/useSearchConsoleAccount', () =>
	jest.fn().mockName( 'useSearchConsoleAccount' )
);
jest.mock( './incomplete-search-console-account-card', () =>
	jest
		.fn( () => <div>Incomplete Search Console account card</div> )
		.mockName( 'IncompleteSearchConsoleAccountCard' )
);

const { CONNECTED, DISCONNECTED, INCOMPLETE } = SEARCH_CONSOLE_ACCOUNT_STATUS;

/**
 * Mocks `useSearchConsoleAccount`.
 *
 * @param {Object} account The account payload to mock.
 */
function mockAccount( account ) {
	useSearchConsoleAccount.mockReturnValue( {
		account,
		hasFinishedResolution: true,
	} );
}

describe( 'SearchConsoleAccountCard', () => {
	beforeEach( () => {
		jest.clearAllMocks();
	} );

	it( 'renders the Connect button when disconnected', () => {
		mockAccount( { status: DISCONNECTED } );

		render( <SearchConsoleAccountCard /> );

		expect(
			screen.getByRole( 'button', { name: 'Connect' } )
		).toBeInTheDocument();
	} );

	it( 'renders the connected badge, property link, and reports menu action', async () => {
		const user = userEvent.setup();

		mockAccount( {
			status: CONNECTED,
			property: { url: 'https://example.com/' },
		} );

		render( <SearchConsoleAccountCard /> );

		expect( screen.getByText( 'Connected' ) ).toBeInTheDocument();
		expect(
			screen.getByRole( 'link', { name: /https:\/\/example\.com\// } )
		).toHaveAttribute( 'href', 'https://example.com/' );

		await user.click(
			screen.getByRole( 'button', {
				name: 'Account actions for Google Search Console',
			} )
		);

		expect(
			screen.getByRole( 'menuitem', {
				name: 'View Organic Search report',
			} )
		).toHaveAttribute(
			'href',
			'admin.php?page=wc-admin&path=%2Fgoogle%2Freports'
		);
	} );

	it( 'delegates any incomplete status to IncompleteSearchConsoleAccountCard', () => {
		mockAccount( { status: INCOMPLETE, step: 'property_selection' } );

		render( <SearchConsoleAccountCard /> );

		expect( IncompleteSearchConsoleAccountCard ).toHaveBeenCalled();
		expect(
			screen.getByText( 'Incomplete Search Console account card' )
		).toBeInTheDocument();
	} );
} );
