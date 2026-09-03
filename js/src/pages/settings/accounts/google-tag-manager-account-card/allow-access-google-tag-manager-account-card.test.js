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
import { handleApiError } from '~/utils/handleError';

jest.mock( '~/hooks/useApiFetchCallback' );
jest.mock( '~/utils/handleError', () => ( {
	handleApiError: jest.fn(),
} ) );

describe( 'AllowAccessGoogleTagManagerAccountCard', () => {
	let fetchGoogleTagManagerConnect;

	beforeEach( () => {
		jest.clearAllMocks();

		fetchGoogleTagManagerConnect = jest
			.fn()
			.mockName( 'fetchGoogleTagManagerConnect' );
		useApiFetchCallback.mockReturnValue( [
			fetchGoogleTagManagerConnect,
			{ loading: false, data: undefined },
		] );

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

	it( 'reports the error via handleApiError when the request fails', async () => {
		const user = userEvent.setup();
		const error = new Error( 'failed' );
		fetchGoogleTagManagerConnect.mockRejectedValue( error );

		render( <AllowAccessGoogleTagManagerAccountCard /> );

		await user.click(
			screen.getByRole( 'button', { name: 'Allow access' } )
		);

		expect( handleApiError ).toHaveBeenCalledWith(
			error,
			'There was an error connecting your Google Tag Manager account.'
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
