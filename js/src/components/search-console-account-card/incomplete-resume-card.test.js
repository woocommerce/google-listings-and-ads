/**
 * External dependencies
 */
import '@testing-library/jest-dom';
import { render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';

/**
 * Internal dependencies
 */
import IncompleteResumeCard from './incomplete-resume-card';
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

describe( 'IncompleteResumeCard', () => {
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

	it( 'never renders a silent success and always shows a resume action', () => {
		render( <IncompleteResumeCard /> );

		expect( screen.getByText( /isn't complete yet/ ) ).toBeInTheDocument();
		expect(
			screen.getByRole( 'button', { name: 'Resume setup' } )
		).toBeInTheDocument();
	} );

	it( 'redirects to the returned URL when clicking "Resume setup"', async () => {
		const user = userEvent.setup();
		fetchSearchConsoleConnect.mockResolvedValue( {
			url: 'https://accounts.google.com/o/oauth2/auth',
		} );

		render( <IncompleteResumeCard /> );

		await user.click(
			screen.getByRole( 'button', { name: 'Resume setup' } )
		);

		expect( window.location.href ).toBe(
			'https://accounts.google.com/o/oauth2/auth'
		);
	} );

	it( 'shows an error notice when the resume request fails', async () => {
		const user = userEvent.setup();
		fetchSearchConsoleConnect.mockRejectedValue( new Error( 'failed' ) );

		render( <IncompleteResumeCard /> );

		await user.click(
			screen.getByRole( 'button', { name: 'Resume setup' } )
		);

		expect( createNotice ).toHaveBeenCalledWith(
			'error',
			expect.stringContaining( 'Unable to resume' )
		);
	} );
} );
