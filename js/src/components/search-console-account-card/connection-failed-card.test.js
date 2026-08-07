/**
 * External dependencies
 */
import '@testing-library/jest-dom';
import { render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';

/**
 * Internal dependencies
 */
import ConnectionFailedCard from './connection-failed-card';
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

describe( 'ConnectionFailedCard', () => {
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

	it( 'renders a "Connection failed" message and a destructive "Retry" button', () => {
		render( <ConnectionFailedCard /> );

		expect( screen.getByText( /Connection failed:/ ) ).toBeInTheDocument();
		expect(
			screen.getByRole( 'button', { name: 'Retry' } )
		).toBeInTheDocument();
	} );

	it( 'redirects to the returned URL when clicking "Retry"', async () => {
		const user = userEvent.setup();
		fetchSearchConsoleConnect.mockResolvedValue( {
			url: 'https://accounts.google.com/o/oauth2/auth',
		} );

		render( <ConnectionFailedCard /> );

		await user.click( screen.getByRole( 'button', { name: 'Retry' } ) );

		expect( window.location.href ).toBe(
			'https://accounts.google.com/o/oauth2/auth'
		);
	} );

	it( 'shows an error notice when the retry request fails', async () => {
		const user = userEvent.setup();
		fetchSearchConsoleConnect.mockRejectedValue( new Error( 'failed' ) );

		render( <ConnectionFailedCard /> );

		await user.click( screen.getByRole( 'button', { name: 'Retry' } ) );

		expect( createNotice ).toHaveBeenCalledWith(
			'error',
			expect.stringContaining( 'Unable to connect' )
		);
	} );
} );
