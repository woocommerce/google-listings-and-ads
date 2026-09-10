/**
 * External dependencies
 */
import '@testing-library/jest-dom';
import { render, screen, waitFor } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { getQuery, getHistory, getNewPath } from '@woocommerce/navigation';

/**
 * Internal dependencies
 */
import GoogleSearchConsoleAccountCard from './index';
import { GOOGLE_SEARCH_CONSOLE_ACCOUNT_STATUS } from '~/constants';
import useGoogleSearchConsoleAccount from '~/hooks/useGoogleSearchConsoleAccount';
import useSearchConsoleSetupCompleteCallback from './hooks/useSearchConsoleSetupCompleteCallback';
import IncompleteGoogleSearchConsoleAccountCard from './incomplete-google-search-console-account-card';

jest.mock( '~/hooks/useGoogleSearchConsoleAccount', () =>
	jest.fn().mockName( 'useGoogleSearchConsoleAccount' )
);
jest.mock( './hooks/useSearchConsoleSetupCompleteCallback', () =>
	jest.fn().mockName( 'useSearchConsoleSetupCompleteCallback' )
);
jest.mock( './incomplete-google-search-console-account-card', () =>
	jest
		.fn( () => <div>Incomplete Google Search Console account card</div> )
		.mockName( 'IncompleteGoogleSearchConsoleAccountCard' )
);
jest.mock( '@woocommerce/navigation', () => ( {
	getQuery: jest.fn().mockName( 'getQuery' ),
	getNewPath: jest.fn().mockName( 'getNewPath' ),
	getHistory: jest.fn().mockName( 'getHistory' ),
} ) );

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
	let handleCompleteSetup;
	let historyReplace;

	beforeEach( () => {
		jest.clearAllMocks();

		getQuery.mockReturnValue( {} );
		getNewPath.mockReturnValue( '/new-path' );
		historyReplace = jest.fn().mockName( 'getHistory().replace' );
		getHistory.mockReturnValue( { replace: historyReplace } );

		handleCompleteSetup = jest
			.fn()
			.mockName( 'handleCompleteSetup' )
			.mockResolvedValue( undefined );
		useSearchConsoleSetupCompleteCallback.mockReturnValue( [
			handleCompleteSetup,
		] );
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

	it( 'renders the connected badge, with no reports menu action, property link, or success notice when the backend sends no site_url', async () => {
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
			screen.queryByRole( 'menuitem', {
				name: 'View Organic Search report',
			} )
		).not.toBeInTheDocument();
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

	it( 'renders a plain, unwrapped link to the connected property in Google Search Console when the backend sends site_url', () => {
		mockAccount( { status: CONNECTED, site_url: 'https://example.com/' } );

		render( <GoogleSearchConsoleAccountCard /> );

		expect(
			screen.getByRole( 'link', { name: /https:\/\/example\.com\// } )
		).toHaveAttribute(
			'href',
			'https://search.google.com/search-console?resource_id=https%3A%2F%2Fexample.com%2F'
		);
	} );

	it( 'renders a plain, unwrapped reports menu action when the backend sends site_url', async () => {
		const user = userEvent.setup();

		mockAccount( { status: CONNECTED, site_url: 'https://example.com/' } );

		render( <GoogleSearchConsoleAccountCard /> );

		await user.click(
			screen.getByRole( 'button', {
				name: 'Account actions for Google Search Console',
			} )
		);

		expect(
			screen.getByRole( 'menuitem', {
				name: /View Organic Search report/,
			} )
		).toHaveAttribute(
			'href',
			'https://search.google.com/search-console/performance/search-analytics?resource_id=https%3A%2F%2Fexample.com%2F'
		);
	} );

	it( 'renders the one-time success notice when the backend reports just_resolved', () => {
		mockAccount( { status: CONNECTED, just_resolved: true } );

		render( <GoogleSearchConsoleAccountCard /> );

		expect(
			screen.getByText(
				'We connected and verified a property for you. Your search data will start to appear over the next few days.',
				{ selector: 'p' }
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

	it( 'completes setup and cleans up the URL when returning from a confirmed OAuth redirect while disconnected', async () => {
		getQuery.mockReturnValue( { 'google-mc': 'connected' } );
		mockAccount( { status: DISCONNECTED } );

		render( <GoogleSearchConsoleAccountCard /> );

		expect( handleCompleteSetup ).toHaveBeenCalledTimes( 1 );
		await waitFor( () => {
			expect( historyReplace ).toHaveBeenCalledWith( '/new-path' );
		} );
		expect( getNewPath ).toHaveBeenCalledWith( { 'google-mc': undefined } );
	} );

	it( 'does not complete setup on a plain page load', () => {
		mockAccount( { status: DISCONNECTED } );

		render( <GoogleSearchConsoleAccountCard /> );

		expect( handleCompleteSetup ).not.toHaveBeenCalled();
	} );

	it( 'does not complete setup when already connected, even if the URL still reports a completed OAuth return', () => {
		getQuery.mockReturnValue( { 'google-mc': 'connected' } );
		mockAccount( { status: CONNECTED } );

		render( <GoogleSearchConsoleAccountCard /> );

		expect( handleCompleteSetup ).not.toHaveBeenCalled();
	} );
} );
