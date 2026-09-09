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
import useGoogleAccount from '~/hooks/useGoogleAccount';
import useAutoResolveSearchConsoleProperty from '~/hooks/useAutoResolveSearchConsoleProperty';
import IncompleteGoogleSearchConsoleAccountCard from './incomplete-google-search-console-account-card';

jest.mock( '~/hooks/useGoogleSearchConsoleAccount', () =>
	jest.fn().mockName( 'useGoogleSearchConsoleAccount' )
);
jest.mock( '~/hooks/useGoogleAccount', () =>
	jest
		.fn()
		.mockName( 'useGoogleAccount' )
		.mockReturnValue( { google: undefined } )
);
jest.mock( '~/hooks/useAutoResolveSearchConsoleProperty', () =>
	jest
		.fn()
		.mockName( 'useAutoResolveSearchConsoleProperty' )
		.mockReturnValue( {
			hasDetermined: true,
			isResolving: false,
			justResolved: false,
		} )
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
 * @param {boolean} [hasFinishedResolution] Whether resolution has finished. Defaults to `true`.
 */
function mockAccount( account, hasFinishedResolution = true ) {
	useGoogleSearchConsoleAccount.mockReturnValue( {
		account,
		hasFinishedResolution,
	} );
}

describe( 'GoogleSearchConsoleAccountCard', () => {
	beforeEach( () => {
		jest.clearAllMocks();
	} );

	it( 'renders nothing while the account is still resolving', () => {
		mockAccount( undefined, false );

		const { container } = render( <GoogleSearchConsoleAccountCard /> );

		expect( container ).toBeEmptyDOMElement();
	} );

	it( 'renders the Connect button when disconnected', () => {
		mockAccount( { status: DISCONNECTED } );

		render( <GoogleSearchConsoleAccountCard /> );

		expect(
			screen.getByRole( 'button', { name: 'Connect' } )
		).toBeInTheDocument();
	} );

	it( 'renders the connected badge and reports menu action, with no property link or success notice when the backend sends neither', async () => {
		const user = userEvent.setup();

		mockAccount( { status: CONNECTED } );

		render( <GoogleSearchConsoleAccountCard /> );

		expect( screen.getByText( 'Connected' ) ).toBeInTheDocument();
		expect( screen.queryByRole( 'link' ) ).not.toBeInTheDocument();

		await user.click(
			screen.getByRole( 'button', {
				name: 'Account actions for Google Search Console',
			} )
		);

		expect(
			screen.getByRole( 'menuitem', {
				name: 'View Organic Search report',
			} )
		).toBeInTheDocument();
	} );

	it( 'calls onDisconnect when the Disconnect menu item is clicked', async () => {
		const user = userEvent.setup();
		const onDisconnect = jest.fn().mockName( 'onDisconnect' );

		mockAccount( { status: CONNECTED } );

		render(
			<GoogleSearchConsoleAccountCard onDisconnect={ onDisconnect } />
		);

		await user.click(
			screen.getByRole( 'button', {
				name: 'Account actions for Google Search Console',
			} )
		);
		await user.click(
			screen.getByRole( 'menuitem', { name: 'Disconnect' } )
		);

		expect( onDisconnect ).toHaveBeenCalledTimes( 1 );
	} );

	it( 'renders a link to the connected property in Google Search Console, wrapped for the connected Google account, when the backend sends site_url', () => {
		useGoogleAccount.mockReturnValue( {
			google: { email: 'merchant@example.com' },
		} );
		mockAccount( { status: CONNECTED, site_url: 'https://example.com/' } );

		render( <GoogleSearchConsoleAccountCard /> );

		expect(
			screen.getByRole( 'link', { name: /https:\/\/example\.com\// } )
		).toHaveAttribute(
			'href',
			'https://accounts.google.com/accountchooser?continue=https%3A%2F%2Fsearch.google.com%2Fsearch-console%3Fresource_id%3Dhttps%253A%252F%252Fexample.com%252F&Email=merchant%40example.com'
		);
	} );

	it( 'falls back to the plain Search Console link when the connected Google account email is not yet available', () => {
		useGoogleAccount.mockReturnValue( { google: undefined } );
		mockAccount( { status: CONNECTED, site_url: 'https://example.com/' } );

		render( <GoogleSearchConsoleAccountCard /> );

		expect(
			screen.getByRole( 'link', { name: /https:\/\/example\.com\// } )
		).toHaveAttribute(
			'href',
			'https://search.google.com/search-console?resource_id=https%3A%2F%2Fexample.com%2F'
		);
	} );

	it( 'renders the one-time success notice when a property was just auto-resolved', () => {
		mockAccount( { status: CONNECTED } );
		useAutoResolveSearchConsoleProperty.mockReturnValue( {
			hasDetermined: true,
			isResolving: false,
			justResolved: true,
		} );

		render( <GoogleSearchConsoleAccountCard /> );

		expect(
			screen.getByText(
				'We connected and verified a property for you. Your search data will start to appear over the next few days.'
			)
		).toBeInTheDocument();
	} );

	it( 'delegates any incomplete status to IncompleteGoogleSearchConsoleAccountCard', () => {
		mockAccount( { status: INCOMPLETE } );

		render( <GoogleSearchConsoleAccountCard /> );

		expect( IncompleteGoogleSearchConsoleAccountCard ).toHaveBeenCalled();
		expect(
			screen.getByText( 'Incomplete Google Search Console account card' )
		).toBeInTheDocument();
	} );
} );
