/**
 * External dependencies
 */
import '@testing-library/jest-dom';
import { render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';

/**
 * Internal dependencies
 */
import GoogleSearchConsoleAccountCard from './index';
import { GOOGLE_SEARCH_CONSOLE_ACCOUNT_STATUS } from '~/constants';
import useGoogleSearchConsoleAccount from '~/hooks/useGoogleSearchConsoleAccount';
import IncompleteGoogleSearchConsoleAccountCard from './incomplete-google-search-console-account-card';

jest.mock( '~/hooks/useGoogleSearchConsoleAccount', () =>
	jest.fn().mockName( 'useGoogleSearchConsoleAccount' )
);
jest.mock( './incomplete-google-search-console-account-card', () =>
	jest
		.fn( () => <div>Incomplete Google Search Console account card</div> )
		.mockName( 'IncompleteGoogleSearchConsoleAccountCard' )
);

const { CONNECTED, DISCONNECTED, INCOMPLETE } =
	GOOGLE_SEARCH_CONSOLE_ACCOUNT_STATUS;

/**
 * Mocks `useGoogleSearchConsoleAccount`.
 *
 * @param {Object} account The account payload to mock.
 */
function mockAccount( account ) {
	useGoogleSearchConsoleAccount.mockReturnValue( {
		account,
		hasFinishedResolution: true,
	} );
}

describe( 'GoogleSearchConsoleAccountCard', () => {
	beforeEach( () => {
		jest.clearAllMocks();
	} );

	it( 'renders the Connect button when disconnected', () => {
		mockAccount( { status: DISCONNECTED } );

		render( <GoogleSearchConsoleAccountCard /> );

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

		render( <GoogleSearchConsoleAccountCard /> );

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

	it( 'delegates any incomplete status to IncompleteGoogleSearchConsoleAccountCard', () => {
		mockAccount( { status: INCOMPLETE, step: 'property_selection' } );

		render( <GoogleSearchConsoleAccountCard /> );

		expect( IncompleteGoogleSearchConsoleAccountCard ).toHaveBeenCalled();
		expect(
			screen.getByText( 'Incomplete Google Search Console account card' )
		).toBeInTheDocument();
	} );
} );
