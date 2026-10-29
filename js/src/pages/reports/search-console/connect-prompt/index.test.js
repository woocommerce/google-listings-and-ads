/**
 * External dependencies
 */
import '@testing-library/jest-dom';
import { fireEvent, render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { getHistory } from '@woocommerce/navigation';

/**
 * Internal dependencies
 */
import ConnectPrompt from './index';
import useGoogleSearchConsoleAccount from '~/hooks/useGoogleSearchConsoleAccount';
import useGoogleSearchConsoleConnectRedirect from '~/google-search-console/hooks/useGoogleSearchConsoleConnectRedirect';
import { recordGlaEvent } from '~/utils/tracks';
import { GOOGLE_SEARCH_CONSOLE_ACCOUNT_STATUS } from '~/constants';

jest.mock(
	'~/google-search-console/hooks/useGoogleSearchConsoleConnectRedirect',
	() => jest.fn().mockName( 'useGoogleSearchConsoleConnectRedirect' )
);

jest.mock( '~/hooks/useGoogleSearchConsoleAccount', () =>
	jest.fn().mockName( 'useGoogleSearchConsoleAccount' )
);

jest.mock( '@woocommerce/navigation', () => ( {
	...jest.requireActual( '@woocommerce/navigation' ),
	getHistory: jest.fn().mockName( 'getHistory' ),
} ) );

jest.mock( '~/utils/tracks', () => ( {
	__esModule: true,
	recordGlaEvent: jest.fn(),
} ) );

describe( 'ConnectPrompt', () => {
	let connect;

	beforeEach( () => {
		recordGlaEvent.mockClear();
		useGoogleSearchConsoleAccount.mockReturnValue( {
			account: {
				status: GOOGLE_SEARCH_CONSOLE_ACCOUNT_STATUS.DISCONNECTED,
			},
		} );
		connect = jest.fn().mockName( 'connect' );
		useGoogleSearchConsoleConnectRedirect.mockReturnValue( {
			connect,
			loading: false,
		} );
	} );

	it( 'should render the connect copy and start the connect flow when clicking Connect', async () => {
		render( <ConnectPrompt /> );

		expect(
			screen.getByAltText( 'Google Search Console logo' )
		).toBeInTheDocument();
		expect(
			screen.getByRole( 'heading', {
				name: 'Connect your Search Console profile',
			} )
		).toBeInTheDocument();
		expect(
			screen.getByText(
				'Connect it to see how shoppers find your store in organic Google Search, including clicks, impressions, and top queries.'
			)
		).toBeInTheDocument();

		await userEvent.click(
			screen.getByRole( 'button', { name: 'Connect' } )
		);

		expect( connect ).toHaveBeenCalledTimes( 1 );
		expect( recordGlaEvent ).toHaveBeenCalledWith(
			'gla_google_search_console_account_connect_button_click',
			{ context: 'reports-search-console' }
		);
	} );

	it.each( [
		GOOGLE_SEARCH_CONSOLE_ACCOUNT_STATUS.INCOMPLETE,
		GOOGLE_SEARCH_CONSOLE_ACCOUNT_STATUS.ACTION_NEEDED,
		GOOGLE_SEARCH_CONSOLE_ACCOUNT_STATUS.RECONNECT,
		GOOGLE_SEARCH_CONSOLE_ACCOUNT_STATUS.CONNECTION_FAILED,
	] )(
		'should render the needs-attention copy with a link to the Search Console card in the accounts settings when the status is "%s"',
		( status ) => {
			useGoogleSearchConsoleAccount.mockReturnValue( {
				account: { status },
			} );

			render( <ConnectPrompt /> );

			expect(
				screen.getByRole( 'heading', {
					name: 'Your Search Console profile needs attention',
				} )
			).toBeInTheDocument();
			expect(
				screen.queryByRole( 'button', { name: 'Connect' } )
			).not.toBeInTheDocument();

			const href = screen
				.getByRole( 'link', { name: 'Go to settings' } )
				.getAttribute( 'href' );
			expect( href ).toContain( 'section=accounts' );
			expect( href ).toContain( 'focus-account-card=search-console' );
		}
	);

	describe( 'Go to settings', () => {
		let push;

		beforeEach( () => {
			push = jest.fn().mockName( 'history.push' );
			getHistory.mockReturnValue( { push } );
			useGoogleSearchConsoleAccount.mockReturnValue( {
				account: {
					status: GOOGLE_SEARCH_CONSOLE_ACCOUNT_STATUS.RECONNECT,
				},
			} );
		} );

		it( 'should record the click with the report context', async () => {
			render( <ConnectPrompt /> );

			await userEvent.click(
				screen.getByRole( 'link', { name: 'Go to settings' } )
			);

			expect( recordGlaEvent ).toHaveBeenCalledWith(
				'gla_google_search_console_settings_button_click',
				{ context: 'reports-search-console' }
			);
		} );

		it( 'should open the Search Console card through the router instead of reloading the page', async () => {
			render( <ConnectPrompt /> );
			const link = screen.getByRole( 'link', { name: 'Go to settings' } );

			await userEvent.click( link );

			expect( push ).toHaveBeenCalledTimes( 1 );
			expect( push ).toHaveBeenCalledWith( link.getAttribute( 'href' ) );
		} );

		it( 'should leave a click with a modifier key to the browser', () => {
			render( <ConnectPrompt /> );

			fireEvent.click(
				screen.getByRole( 'link', { name: 'Go to settings' } ),
				{ ctrlKey: true }
			);

			expect( push ).not.toHaveBeenCalled();
		} );
	} );

	it.each( [
		[ 'no account', null ],
		[
			'a transient error',
			{ status: GOOGLE_SEARCH_CONSOLE_ACCOUNT_STATUS.TRANSIENT_ERROR },
		],
	] )( 'should render the connect copy for %s', ( _, account ) => {
		useGoogleSearchConsoleAccount.mockReturnValue( { account } );

		render( <ConnectPrompt /> );

		expect(
			screen.getByRole( 'heading', {
				name: 'Connect your Search Console profile',
			} )
		).toBeInTheDocument();
	} );
} );
