/**
 * External dependencies
 */
import '@testing-library/jest-dom';
import { render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';

/**
 * Internal dependencies
 */
import AllowAccessGoogleTagManagerAccountCard from './allow-access-google-tag-manager-account-card';
import useApiFetchCallback from '~/hooks/useApiFetchCallback';
import useDispatchCoreNotices from '~/hooks/useDispatchCoreNotices';

jest.mock( '~/hooks/useApiFetchCallback' );
jest.mock( '~/hooks/useDispatchCoreNotices' );

describe( 'AllowAccessGoogleTagManagerAccountCard', () => {
	let fetchGoogleTagManagerConnect;
	let createNotice;

	beforeEach( () => {
		jest.clearAllMocks();

		fetchGoogleTagManagerConnect = jest
			.fn()
			.mockName( 'fetchGoogleTagManagerConnect' );
		useApiFetchCallback.mockReturnValue( [
			fetchGoogleTagManagerConnect,
			{ loading: false, data: undefined },
		] );

		createNotice = jest.fn().mockName( 'createNotice' );
		useDispatchCoreNotices.mockReturnValue( { createNotice } );

		const location = window.location;
		delete window.location;
		window.location = { ...location, href: '' };
	} );

	it( 'shows the scope-grant message and an "Allow access" button', () => {
		render( <AllowAccessGoogleTagManagerAccountCard /> );

		expect(
			screen.getByText(
				'Google needs your permission before this store can connect to your Google Tag Manager account.'
			)
		).toBeInTheDocument();

		expect(
			screen.getByRole( 'button', { name: 'Allow access' } )
		).toBeEnabled();

		expect( useApiFetchCallback ).toHaveBeenCalledWith( {
			path: '/wc/gla/tag-manager/connect',
		} );
	} );

	it( 'redirects the browser to the returned URL on click', async () => {
		const user = userEvent.setup();
		fetchGoogleTagManagerConnect.mockResolvedValue( {
			url: 'https://accounts.google.com/o/oauth2/auth',
		} );

		render( <AllowAccessGoogleTagManagerAccountCard /> );

		await user.click(
			screen.getByRole( 'button', { name: 'Allow access' } )
		);

		expect( fetchGoogleTagManagerConnect ).toHaveBeenCalledTimes( 1 );
		expect( window.location.href ).toBe(
			'https://accounts.google.com/o/oauth2/auth'
		);
	} );

	it( 'shows a generic error notice when the request fails', async () => {
		const user = userEvent.setup();
		fetchGoogleTagManagerConnect.mockRejectedValue( new Error( 'failed' ) );

		render( <AllowAccessGoogleTagManagerAccountCard /> );

		await user.click(
			screen.getByRole( 'button', { name: 'Allow access' } )
		);

		expect( createNotice ).toHaveBeenCalledWith(
			'error',
			'Unable to connect your Google Tag Manager account. Please try again later.'
		);
	} );

	it( 'disables the button while the request is in flight', () => {
		useApiFetchCallback.mockReturnValue( [
			fetchGoogleTagManagerConnect,
			{ loading: true, data: undefined },
		] );

		render( <AllowAccessGoogleTagManagerAccountCard /> );

		expect(
			screen.getByRole( 'button', { name: 'Allow access' } )
		).toBeDisabled();
	} );

	it( 'keeps the button disabled once resolved but not yet redirected', () => {
		useApiFetchCallback.mockReturnValue( [
			fetchGoogleTagManagerConnect,
			{ loading: false, data: { url: 'https://example.com/' } },
		] );

		render( <AllowAccessGoogleTagManagerAccountCard /> );

		expect(
			screen.getByRole( 'button', { name: 'Allow access' } )
		).toBeDisabled();
	} );
} );
