/**
 * External dependencies
 */
import '@testing-library/jest-dom';
import { render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';

/**
 * Internal dependencies
 */
import ConnectPrompt from './index';
import useGoogleSearchConsoleConnectRedirect from '~/hooks/useGoogleSearchConsoleConnectRedirect';
import { recordGlaEvent } from '~/utils/tracks';

jest.mock( '~/hooks/useGoogleSearchConsoleConnectRedirect', () =>
	jest.fn().mockName( 'useGoogleSearchConsoleConnectRedirect' )
);

jest.mock( '~/utils/tracks', () => ( {
	__esModule: true,
	recordGlaEvent: jest.fn(),
} ) );

describe( 'ConnectPrompt', () => {
	let connect;

	beforeEach( () => {
		recordGlaEvent.mockClear();
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

	it( 'should render the needs-attention copy with a link to the Search Console card in the accounts settings', () => {
		render( <ConnectPrompt needsAttention /> );

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
	} );
} );
