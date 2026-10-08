/**
 * External dependencies
 */
import '@testing-library/jest-dom';
import { render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';

/**
 * Internal dependencies
 */
import AllowAccessGoogleBusinessProfileAccountCard from './allow-access-google-business-profile-account-card';
import useApiFetchCallback from '~/hooks/useApiFetchCallback';
import useGoogleAccount from '~/hooks/useGoogleAccount';
import { handleApiError } from '~/utils/handleError';

jest.mock( '~/hooks/useApiFetchCallback' );
jest.mock( '~/hooks/useGoogleAccount' );
jest.mock( '~/utils/handleError', () => ( {
	handleApiError: jest.fn(),
} ) );

describe( 'AllowAccessGoogleBusinessProfileAccountCard', () => {
	let fetchGoogleBusinessProfileConnect;

	beforeEach( () => {
		jest.clearAllMocks();

		fetchGoogleBusinessProfileConnect = jest
			.fn()
			.mockName( 'fetchGoogleBusinessProfileConnect' );
		useApiFetchCallback.mockReturnValue( [
			fetchGoogleBusinessProfileConnect,
			{ loading: false, data: undefined },
		] );
		useGoogleAccount.mockReturnValue( {
			google: { email: 'merchant@example.com' },
		} );

		const location = window.location;
		delete window.location;
		window.location = { ...location, href: '' };
	} );

	it( 'shows the card description, the access notice and a "Connect" button', () => {
		render( <AllowAccessGoogleBusinessProfileAccountCard /> );

		expect(
			screen.getByText( 'Google Business Profile' )
		).toBeInTheDocument();
		expect(
			screen.getByText(
				'Where your business appears on Google Search and Maps.'
			)
		).toBeInTheDocument();
		expect(
			screen.getByText(
				'By connecting you are granting Google Business Profile access.',
				{ selector: 'p' }
			)
		).toBeInTheDocument();
		expect(
			screen.getByRole( 'button', { name: 'Connect' } )
		).toBeEnabled();

		expect( useApiFetchCallback ).toHaveBeenCalledWith( {
			path: '/wc/gla/business-profile/connect?login_hint=merchant%40example.com',
		} );
	} );

	it( 'omits login_hint when the connected account email is not yet known', () => {
		useGoogleAccount.mockReturnValue( { google: undefined } );

		render( <AllowAccessGoogleBusinessProfileAccountCard /> );

		expect( useApiFetchCallback ).toHaveBeenCalledWith( {
			path: '/wc/gla/business-profile/connect?',
		} );
	} );

	it( 'redirects the browser to the returned URL on click', async () => {
		const user = userEvent.setup();
		fetchGoogleBusinessProfileConnect.mockResolvedValue( {
			url: 'https://accounts.google.com/o/oauth2/auth',
		} );

		render( <AllowAccessGoogleBusinessProfileAccountCard /> );

		await user.click( screen.getByRole( 'button', { name: 'Connect' } ) );

		expect( fetchGoogleBusinessProfileConnect ).toHaveBeenCalledTimes( 1 );
		expect( window.location.href ).toBe(
			'https://accounts.google.com/o/oauth2/auth'
		);
	} );

	it( 'reports the error via handleApiError when the request fails', async () => {
		const user = userEvent.setup();
		const error = new Error( 'failed' );
		fetchGoogleBusinessProfileConnect.mockRejectedValue( error );

		render( <AllowAccessGoogleBusinessProfileAccountCard /> );

		await user.click( screen.getByRole( 'button', { name: 'Connect' } ) );

		expect( handleApiError ).toHaveBeenCalledWith(
			error,
			'There was an error connecting your Google Business Profile account.'
		);
		expect( window.location.href ).toBe( '' );
	} );

	it( 'disables the button while the request is in flight', () => {
		useApiFetchCallback.mockReturnValue( [
			fetchGoogleBusinessProfileConnect,
			{ loading: true, data: undefined },
		] );

		render( <AllowAccessGoogleBusinessProfileAccountCard /> );

		expect(
			screen.getByRole( 'button', { name: 'Connect' } )
		).toBeDisabled();
	} );

	it( 'keeps the button disabled once resolved but not yet redirected', () => {
		useApiFetchCallback.mockReturnValue( [
			fetchGoogleBusinessProfileConnect,
			{ loading: false, data: { url: 'https://example.com/' } },
		] );

		render( <AllowAccessGoogleBusinessProfileAccountCard /> );

		expect(
			screen.getByRole( 'button', { name: 'Connect' } )
		).toBeDisabled();
	} );
} );
