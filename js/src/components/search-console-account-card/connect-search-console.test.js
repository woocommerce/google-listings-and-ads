/**
 * External dependencies
 */
import '@testing-library/jest-dom';
import { render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';

/**
 * Internal dependencies
 */
import ConnectSearchConsole from './connect-search-console';
import useApiFetchCallback from '~/hooks/useApiFetchCallback';
import useDispatchCoreNotices from '~/hooks/useDispatchCoreNotices';
import useSearchConsoleAccount from '~/hooks/useSearchConsoleAccount';

jest.mock( '~/hooks/useApiFetchCallback', () =>
	jest.fn().mockName( 'useApiFetchCallback' )
);

jest.mock( '~/hooks/useDispatchCoreNotices', () =>
	jest.fn().mockName( 'useDispatchCoreNotices' )
);

jest.mock( '~/hooks/useSearchConsoleAccount', () =>
	jest.fn().mockName( 'useSearchConsoleAccount' )
);

// AppButton's `eventName` prop records a tracking event on click, which pulls in
// `@woocommerce/navigation`'s `getQuery()` and needs a real `window.location` shape that
// this suite's minimal `window.location` stub (needed to assert redirects) doesn't provide.
jest.mock( '~/utils/tracks', () => ( {
	recordGlaEvent: jest.fn(),
} ) );

describe( 'ConnectSearchConsole', () => {
	let fetchSearchConsoleConnect;
	let createNotice;

	beforeEach( () => {
		fetchSearchConsoleConnect = jest
			.fn()
			.mockName( 'fetchSearchConsoleConnect' );
		useApiFetchCallback.mockReturnValue( [
			fetchSearchConsoleConnect,
			{ loading: false, data: undefined },
		] );

		createNotice = jest.fn().mockName( 'createNotice' );
		useDispatchCoreNotices.mockReturnValue( { createNotice } );

		useSearchConsoleAccount.mockReturnValue( {
			searchConsoleAccount: { status: 'disconnected' },
		} );

		delete window.location;
		window.location = { href: '' };
	} );

	it( 'renders the auth prompt copy by default', () => {
		render( <ConnectSearchConsole /> );

		expect(
			screen.getByText( /Sign in to Google to connect/ )
		).toBeInTheDocument();
	} );

	it( 'skips the auth prompt copy when the backend flags it', () => {
		useSearchConsoleAccount.mockReturnValue( {
			searchConsoleAccount: {
				status: 'disconnected',
				skip_auth_prompt: true,
			},
		} );

		render( <ConnectSearchConsole /> );

		expect(
			screen.queryByText( /Sign in to Google to connect/ )
		).not.toBeInTheDocument();
		expect(
			screen.getByText( /Connect your Search Console property/ )
		).toBeInTheDocument();
	} );

	it( 'redirects to the returned URL when clicking "Connect"', async () => {
		const user = userEvent.setup();
		fetchSearchConsoleConnect.mockResolvedValue( {
			url: 'https://accounts.google.com/o/oauth2/auth',
		} );

		render( <ConnectSearchConsole /> );

		await user.click( screen.getByRole( 'button', { name: 'Connect' } ) );

		expect( fetchSearchConsoleConnect ).toHaveBeenCalledTimes( 1 );
		expect( window.location.href ).toBe(
			'https://accounts.google.com/o/oauth2/auth'
		);
	} );

	it( 'shows an error notice when the connect request fails', async () => {
		const user = userEvent.setup();
		fetchSearchConsoleConnect.mockRejectedValue( new Error( 'failed' ) );

		render( <ConnectSearchConsole /> );

		await user.click( screen.getByRole( 'button', { name: 'Connect' } ) );

		expect( createNotice ).toHaveBeenCalledWith(
			'error',
			expect.stringContaining( 'Unable to connect' )
		);
	} );
} );
