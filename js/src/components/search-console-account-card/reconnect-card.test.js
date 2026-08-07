/**
 * External dependencies
 */
import '@testing-library/jest-dom';
import { render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';

/**
 * Internal dependencies
 */
import ReconnectCard from './reconnect-card';
import useApiFetchCallback from '~/hooks/useApiFetchCallback';
import useDispatchCoreNotices from '~/hooks/useDispatchCoreNotices';

jest.mock( '~/hooks/useApiFetchCallback', () =>
	jest.fn().mockName( 'useApiFetchCallback' )
);

jest.mock( '~/hooks/useDispatchCoreNotices', () =>
	jest.fn().mockName( 'useDispatchCoreNotices' )
);

// AppButton's `eventName` prop records a tracking event on click, which pulls in
// `@woocommerce/navigation`'s `getQuery()` and needs a real `window.location` shape that
// this suite's minimal `window.location` stub (needed to assert redirects) doesn't provide.
jest.mock( '~/utils/tracks', () => ( {
	recordGlaEvent: jest.fn(),
} ) );

describe( 'ReconnectCard', () => {
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

		delete window.location;
		window.location = { href: '' };
	} );

	it( 'renders a "Connection expired" message and a destructive "Reconnect" button', () => {
		render( <ReconnectCard /> );

		expect( screen.getByText( /Connection expired:/ ) ).toBeInTheDocument();
		expect(
			screen.getByRole( 'button', { name: 'Reconnect' } )
		).toBeInTheDocument();
	} );

	it( 'redirects to the returned URL when clicking "Reconnect"', async () => {
		const user = userEvent.setup();
		fetchSearchConsoleConnect.mockResolvedValue( {
			url: 'https://accounts.google.com/o/oauth2/auth',
		} );

		render( <ReconnectCard /> );

		await user.click( screen.getByRole( 'button', { name: 'Reconnect' } ) );

		expect( window.location.href ).toBe(
			'https://accounts.google.com/o/oauth2/auth'
		);
	} );

	it( 'shows an error notice when the reconnect request fails', async () => {
		const user = userEvent.setup();
		fetchSearchConsoleConnect.mockRejectedValue( new Error( 'failed' ) );

		render( <ReconnectCard /> );

		await user.click( screen.getByRole( 'button', { name: 'Reconnect' } ) );

		expect( createNotice ).toHaveBeenCalledWith(
			'error',
			expect.stringContaining( 'Unable to reconnect' )
		);
	} );
} );
