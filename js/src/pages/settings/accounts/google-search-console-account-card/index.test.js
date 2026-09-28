/**
 * External dependencies
 */
import '@testing-library/jest-dom';
import { render, screen, waitFor } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { getQuery, getNewPath, getHistory } from '@woocommerce/navigation';

/**
 * Internal dependencies
 */
import GoogleSearchConsoleAccountCard from './index';
import { GOOGLE_SEARCH_CONSOLE_ACCOUNT_STATUS } from '~/constants';
import useGoogleSearchConsoleAccount from '~/hooks/useGoogleSearchConsoleAccount';
import useGoogleAccount from '~/hooks/useGoogleAccount';
import useScrollIntoView from '~/hooks/useScrollIntoView';
import useSearchConsoleSetupCompleteCallback from './hooks/useSearchConsoleSetupCompleteCallback';
import ConnectGoogleSearchConsoleAccountCard from './connect-google-search-console-account-card';
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
jest.mock( '~/hooks/useScrollIntoView', () =>
	jest.fn().mockName( 'useScrollIntoView' )
);
jest.mock( './hooks/useSearchConsoleSetupCompleteCallback', () =>
	jest.fn().mockName( 'useSearchConsoleSetupCompleteCallback' )
);
jest.mock( '@woocommerce/navigation', () => ( {
	...jest.requireActual( '@woocommerce/navigation' ),
	getQuery: jest.fn().mockName( 'getQuery' ),
	getNewPath: jest.fn().mockName( 'getNewPath' ),
	getHistory: jest.fn().mockName( 'getHistory' ),
} ) );
jest.mock( './connect-google-search-console-account-card', () =>
	jest
		.fn( () => <div>Connect Google Search Console account card</div> )
		.mockName( 'ConnectGoogleSearchConsoleAccountCard' )
);
jest.mock( './incomplete-google-search-console-account-card', () =>
	jest
		.fn( () => <div>Incomplete Google Search Console account card</div> )
		.mockName( 'IncompleteGoogleSearchConsoleAccountCard' )
);
jest.mock( './connected-success-notice', () =>
	jest
		.fn( () => <div>Connected success notice</div> )
		.mockName( 'ConnectedSuccessNotice' )
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
	let scrollIntoView;
	let handleCompleteSetup;
	let replace;

	beforeEach( () => {
		jest.clearAllMocks();
		useGoogleAccount.mockReturnValue( { google: undefined } );

		scrollIntoView = jest.fn().mockName( 'scrollIntoView' );
		useScrollIntoView.mockReturnValue( {
			containerRef: { current: null },
			scrollIntoView,
		} );

		handleCompleteSetup = jest
			.fn()
			.mockName( 'handleCompleteSetup' )
			.mockResolvedValue( undefined );
		useSearchConsoleSetupCompleteCallback.mockReturnValue( [
			handleCompleteSetup,
		] );

		replace = jest.fn().mockName( 'replace' );
		getHistory.mockReturnValue( { replace } );
		getQuery.mockReturnValue( {} );
		getNewPath.mockReturnValue( 'cleaned-path' );
	} );

	it( 'renders nothing while the account is still resolving', () => {
		mockAccount( undefined, false );

		const { container } = render( <GoogleSearchConsoleAccountCard /> );

		expect( container ).toBeEmptyDOMElement();
	} );

	it( 'delegates disconnected status to ConnectGoogleSearchConsoleAccountCard', () => {
		mockAccount( { status: DISCONNECTED } );

		render( <GoogleSearchConsoleAccountCard /> );

		expect( ConnectGoogleSearchConsoleAccountCard ).toHaveBeenCalled();
		expect(
			screen.getByText( 'Connect Google Search Console account card' )
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

	it( 'renders a plain, unwrapped link to the connected property when the connected Google account email is not yet known', () => {
		mockAccount( { status: CONNECTED, site_url: 'https://example.com/' } );

		render( <GoogleSearchConsoleAccountCard /> );

		expect(
			screen.getByRole( 'link', { name: /https:\/\/example\.com\// } )
		).toHaveAttribute(
			'href',
			'https://search.google.com/search-console?resource_id=https%3A%2F%2Fexample.com%2F'
		);
	} );

	it( 'renders a link to the connected property, wrapped for the connected Google account, when its email is known', () => {
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

	it( 'renders a plain, unwrapped reports menu action when the connected Google account email is not yet known', async () => {
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

	it( 'renders a reports menu action, wrapped for the connected Google account, when its email is known', async () => {
		const user = userEvent.setup();

		useGoogleAccount.mockReturnValue( {
			google: { email: 'merchant@example.com' },
		} );
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
			'https://accounts.google.com/accountchooser?continue=https%3A%2F%2Fsearch.google.com%2Fsearch-console%2Fperformance%2Fsearch-analytics%3Fresource_id%3Dhttps%253A%252F%252Fexample.com%252F&Email=merchant%40example.com'
		);
	} );

	it( 'renders the one-time success notice when the backend reports just_resolved', () => {
		mockAccount( { status: CONNECTED, just_resolved: true } );

		render( <GoogleSearchConsoleAccountCard /> );

		expect(
			screen.getByText( 'Connected success notice' )
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

	/**
	 * Asserts the Search Console flow params were removed from the URL.
	 */
	async function expectFlowParamsToBeCleared() {
		await waitFor( () => {
			expect( replace ).toHaveBeenCalledWith( 'cleaned-path' );
		} );
		expect( replace ).toHaveBeenCalledTimes( 1 );
		expect( getNewPath ).toHaveBeenCalledWith( {
			'google-mc': undefined,
			'google-service': undefined,
		} );
	}

	it( 'does not scroll, complete setup, or touch the URL on a plain page load', () => {
		mockAccount( { status: DISCONNECTED } );

		render( <GoogleSearchConsoleAccountCard /> );

		expect( scrollIntoView ).not.toHaveBeenCalled();
		expect( handleCompleteSetup ).not.toHaveBeenCalled();
		expect( replace ).not.toHaveBeenCalled();
	} );

	describe( 'on return from the Search Console OAuth flow', () => {
		beforeEach( () => {
			getQuery.mockReturnValue( {
				'google-mc': 'connected',
				'google-service': 'search-console',
			} );
		} );

		it( 'scrolls the card into view, completes setup, and clears the flow params when the status is disconnected', async () => {
			mockAccount( { status: DISCONNECTED } );

			render( <GoogleSearchConsoleAccountCard /> );

			expect( scrollIntoView ).toHaveBeenCalledTimes( 1 );
			expect( handleCompleteSetup ).toHaveBeenCalledTimes( 1 );
			await expectFlowParamsToBeCleared();
		} );

		it.each( [
			[ 'incomplete', { status: INCOMPLETE } ],
			[ 'connected', { status: CONNECTED, just_resolved: true } ],
		] )(
			'scrolls the card into view and clears the flow params, without completing setup, when the status is %s',
			async ( _, account ) => {
				mockAccount( account );

				render( <GoogleSearchConsoleAccountCard /> );

				expect( scrollIntoView ).toHaveBeenCalledTimes( 1 );
				expect( handleCompleteSetup ).not.toHaveBeenCalled();
				await expectFlowParamsToBeCleared();
			}
		);

		it( 'handles the flow only once when completing setup changes the account status', async () => {
			let resolveCompleteSetup;
			handleCompleteSetup.mockReturnValue(
				new Promise( ( resolve ) => {
					resolveCompleteSetup = resolve;
				} )
			);
			mockAccount( { status: DISCONNECTED } );

			const { rerender } = render( <GoogleSearchConsoleAccountCard /> );

			mockAccount( { status: INCOMPLETE } );
			rerender( <GoogleSearchConsoleAccountCard /> );
			resolveCompleteSetup();

			await expectFlowParamsToBeCleared();
			expect( scrollIntoView ).toHaveBeenCalledTimes( 1 );
			expect( handleCompleteSetup ).toHaveBeenCalledTimes( 1 );
		} );

		it( 'tells the Connect card it is completing setup', () => {
			mockAccount( { status: DISCONNECTED } );

			render( <GoogleSearchConsoleAccountCard /> );

			expect(
				ConnectGoogleSearchConsoleAccountCard
			).toHaveBeenCalledWith(
				expect.objectContaining( { isCompletingSetup: true } ),
				expect.anything()
			);
		} );

		it( 'waits for the account to resolve before scrolling', async () => {
			mockAccount( undefined, false );

			const { rerender } = render( <GoogleSearchConsoleAccountCard /> );

			expect( scrollIntoView ).not.toHaveBeenCalled();
			expect( handleCompleteSetup ).not.toHaveBeenCalled();

			mockAccount( { status: CONNECTED } );
			rerender( <GoogleSearchConsoleAccountCard /> );

			expect( scrollIntoView ).toHaveBeenCalledTimes( 1 );
			await expectFlowParamsToBeCleared();
		} );
	} );

	it( 'scrolls the card into view and clears the flow params, without completing setup, when arriving from a Search Console CTA', async () => {
		getQuery.mockReturnValue( { 'google-service': 'search-console' } );
		mockAccount( { status: DISCONNECTED } );

		render( <GoogleSearchConsoleAccountCard /> );

		expect( scrollIntoView ).toHaveBeenCalledTimes( 1 );
		expect( handleCompleteSetup ).not.toHaveBeenCalled();
		expect( ConnectGoogleSearchConsoleAccountCard ).toHaveBeenCalledWith(
			expect.objectContaining( { isCompletingSetup: false } ),
			expect.anything()
		);
		await expectFlowParamsToBeCleared();
	} );

	it( 'does nothing when the OAuth return belongs to another service riding the same shared Google connection', () => {
		getQuery.mockReturnValue( {
			'google-mc': 'connected',
			'google-service': 'tag-manager',
		} );
		mockAccount( { status: DISCONNECTED } );

		render( <GoogleSearchConsoleAccountCard /> );

		expect( scrollIntoView ).not.toHaveBeenCalled();
		expect( handleCompleteSetup ).not.toHaveBeenCalled();
		expect( replace ).not.toHaveBeenCalled();
	} );
} );
