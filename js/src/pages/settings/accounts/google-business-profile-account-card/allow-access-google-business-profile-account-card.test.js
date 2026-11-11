/**
 * External dependencies
 */
import '@testing-library/jest-dom';
import { render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { getQuery, getNewPath, getHistory } from '@woocommerce/navigation';

/**
 * Internal dependencies
 */
import AllowAccessGoogleBusinessProfileAccountCard from './allow-access-google-business-profile-account-card';
import useApiFetchCallback from '~/hooks/useApiFetchCallback';
import useGoogleAccount from '~/hooks/useGoogleAccount';
import { handleApiError } from '~/utils/handleError';
import { redirectTo } from '~/utils/urls';

jest.mock( '~/hooks/useApiFetchCallback' );
jest.mock( '~/hooks/useGoogleAccount' );
jest.mock( '~/utils/handleError', () => ( {
	handleApiError: jest.fn(),
} ) );
jest.mock( '~/utils/urls', () => ( {
	redirectTo: jest.fn().mockName( 'redirectTo' ),
} ) );
jest.mock( '@woocommerce/navigation', () => ( {
	...jest.requireActual( '@woocommerce/navigation' ),
	getQuery: jest.fn().mockName( 'getQuery' ),
	getNewPath: jest.fn().mockName( 'getNewPath' ),
	getHistory: jest.fn().mockName( 'getHistory' ),
} ) );

describe( 'AllowAccessGoogleBusinessProfileAccountCard', () => {
	let fetchGoogleBusinessProfileConnect;
	let replace;

	beforeEach( () => {
		jest.clearAllMocks();

		replace = jest.fn().mockName( 'replace' );
		getHistory.mockReturnValue( { replace } );
		getNewPath.mockReturnValue( 'cleaned-path' );
		getQuery.mockReturnValue( {} );

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

	it( 'leaves the URL alone on a normal visit', () => {
		render( <AllowAccessGoogleBusinessProfileAccountCard /> );

		expect( replace ).not.toHaveBeenCalled();
	} );

	it( "ignores a return from another service's connect flow", () => {
		getQuery.mockReturnValue( { 'google-service': 'search-console' } );

		render( <AllowAccessGoogleBusinessProfileAccountCard /> );

		expect(
			screen.getByText(
				'By connecting you are granting Google Business Profile access.',
				{ selector: 'p' }
			)
		).toBeInTheDocument();
		expect( replace ).not.toHaveBeenCalled();
	} );

	it( 'says access was not granted on a return from Google, keeps "Connect", and removes the query arg', () => {
		getQuery.mockReturnValue( { 'google-service': 'business-profile' } );

		render( <AllowAccessGoogleBusinessProfileAccountCard /> );

		expect(
			screen.getByText( "Google Business Profile access wasn't granted" )
		).toBeInTheDocument();
		expect(
			screen.getByText(
				'Click Connect to try again, and allow access when Google asks.',
				{ selector: 'p' }
			)
		).toBeInTheDocument();
		expect(
			screen.queryByText(
				'By connecting you are granting Google Business Profile access.'
			)
		).not.toBeInTheDocument();
		expect(
			screen.getByRole( 'button', { name: 'Connect' } )
		).toBeEnabled();
		expect( getNewPath ).toHaveBeenCalledWith( {
			'google-service': undefined,
		} );
		expect( replace ).toHaveBeenCalledWith( 'cleaned-path' );
	} );

	it( 'keeps saying access was not granted once the query arg is removed', () => {
		getQuery.mockReturnValue( { 'google-service': 'business-profile' } );
		const { rerender } = render(
			<AllowAccessGoogleBusinessProfileAccountCard />
		);

		getQuery.mockReturnValue( {} );
		rerender( <AllowAccessGoogleBusinessProfileAccountCard /> );

		expect(
			screen.getByText( "Google Business Profile access wasn't granted" )
		).toBeInTheDocument();
		expect( replace ).toHaveBeenCalledTimes( 1 );
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
		expect( redirectTo ).toHaveBeenCalledWith(
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
		expect( redirectTo ).not.toHaveBeenCalled();
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
