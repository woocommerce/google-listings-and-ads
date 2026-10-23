/**
 * External dependencies
 */
import '@testing-library/jest-dom';
import { render, screen, waitFor } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { dispatch } from '@wordpress/data';

/**
 * Internal dependencies
 */
import ConnectGoogleBusinessProfileAccountCard from './index';
import { useAppDispatch } from '~/data';
import { STORE_KEY, ERROR_SLOTS } from '~/data/constants';
import useApiFetchCallback from '~/hooks/useApiFetchCallback';
import useGoogleAccount from '~/hooks/useGoogleAccount';
import useGoogleBusinessProfileLocations from '../hooks/useGoogleBusinessProfileLocations';
import { recordGlaEvent } from '~/utils/tracks';

const CONNECTION_ERROR_SLOTS = [
	ERROR_SLOTS.GOOGLE_BUSINESS_PROFILE_CONNECTION_ERROR_SLOT,
];

jest.mock( '~/data', () => ( {
	...jest.requireActual( '~/data' ),
	useAppDispatch: jest.fn().mockName( 'useAppDispatch' ),
} ) );
jest.mock( '~/hooks/useApiFetchCallback' );
jest.mock( '~/hooks/useGoogleAccount', () =>
	jest.fn().mockName( 'useGoogleAccount' )
);
jest.mock( '../hooks/useGoogleBusinessProfileLocations', () =>
	jest.fn().mockName( 'useGoogleBusinessProfileLocations' )
);
jest.mock( '~/utils/tracks', () => ( {
	recordGlaEvent: jest.fn().mockName( 'recordGlaEvent' ),
} ) );

// `ExternalLink` appends this to the link's accessible name.
const CREATE_ACCOUNT_LINK_NAME = 'Create new account (opens in a new tab)';
const CREATE_LOCATION_LINK_NAME = 'Create new location (opens in a new tab)';

// The creation URL, resolved to the connected Google account (`merchant@example.com`).
const CREATE_LINK_HREF =
	'https://accounts.google.com/accountchooser?continue=https%3A%2F%2Fbusiness.google.com%2Fcreate&Email=merchant%40example.com';

const NO_ACCOUNT_TEXT =
	"We couldn't find a Google Business Profile associated with your merchant@example.com account. If you have already created an account, click the 'Check again' button to fetch your account details.";

const CONNECTION_FAILED_TEXT =
	"Something went wrong. Check that you're signed in to the right Google account, then try again.";

const buildLocation = ( id, street ) => {
	return {
		id,
		accountId: '106234255840114990952',
		title: "Jane's Bakery",
		address: `${ street }, Seattle, WA, 98121`,
		placeId: `place-${ id }`,
		mapsUri: `https://maps.google.com/?cid=${ id }`,
	};
};

const downtown = buildLocation( '1111', '2423 1st Ave' );
const riverside = buildLocation( '2222', '456 Riverside Ave' );

/**
 * Mocks `useGoogleBusinessProfileLocations` (the candidate locations list).
 *
 * @param {Object[]|null} [locations] The locations to mock.
 * @param {boolean} [hasFinishedResolution] Whether the resolver has finished.
 */
function mockLocations( locations, hasFinishedResolution = true ) {
	useGoogleBusinessProfileLocations.mockReturnValue( {
		locations,
		hasFinishedResolution,
	} );
}

/**
 * Finds notice body text. `Notice` also announces its content to screen readers, so the text is
 * matched against the rendered paragraph only.
 *
 * @param {string} text The text to find.
 * @return {HTMLElement} The paragraph.
 */
const getNoticeText = ( text ) => {
	return screen.getByText( text, { selector: 'p' } );
};

/**
 * Same as `getNoticeText`, but returns `null` when the text isn't shown.
 *
 * @param {string} text The text to find.
 * @return {HTMLElement|null} The paragraph, or `null`.
 */
const queryNoticeText = ( text ) => {
	return screen.queryByText( text, { selector: 'p' } );
};

describe( 'ConnectGoogleBusinessProfileAccountCard', () => {
	let fetchConnect;
	let fetchGoogleBusinessProfileAccount;
	let fetchGoogleBusinessProfileLocations;
	let receiveDetailedError;
	let clearDetailedErrorBySlots;

	beforeEach( () => {
		jest.clearAllMocks();

		// `hasConnectionError` derives from this slot in the real store — start each test from a
		// clean slate.
		dispatch( STORE_KEY ).clearDetailedErrorBySlots(
			CONNECTION_ERROR_SLOTS
		);

		fetchConnect = jest.fn().mockName( 'fetchConnect' ).mockResolvedValue();
		useApiFetchCallback.mockReturnValue( [
			fetchConnect,
			{ loading: false },
		] );

		fetchGoogleBusinessProfileAccount = jest
			.fn()
			.mockName( 'fetchGoogleBusinessProfileAccount' )
			.mockResolvedValue();
		fetchGoogleBusinessProfileLocations = jest
			.fn()
			.mockName( 'fetchGoogleBusinessProfileLocations' )
			.mockResolvedValue();
		receiveDetailedError = jest
			.fn()
			.mockName( 'receiveDetailedError' )
			.mockImplementation( ( slot, error ) =>
				dispatch( STORE_KEY ).receiveDetailedError( slot, error )
			);
		clearDetailedErrorBySlots = jest
			.fn()
			.mockName( 'clearDetailedErrorBySlots' )
			.mockImplementation( ( slots ) =>
				dispatch( STORE_KEY ).clearDetailedErrorBySlots( slots )
			);
		useAppDispatch.mockReturnValue( {
			fetchGoogleBusinessProfileAccount,
			fetchGoogleBusinessProfileLocations,
			receiveDetailedError,
			clearDetailedErrorBySlots,
		} );

		useGoogleAccount.mockReturnValue( {
			google: { email: 'merchant@example.com' },
		} );
	} );

	it( 'shows a loading spinner and no indicator until the locations list has resolved', () => {
		mockLocations( null, false );

		render( <ConnectGoogleBusinessProfileAccountCard /> );

		expect( screen.getByRole( 'status' ) ).toBeInTheDocument();
		expect( screen.queryByText( 'Action needed' ) ).not.toBeInTheDocument();
		expect(
			screen.queryByRole( 'button', { name: 'Connect' } )
		).not.toBeInTheDocument();
	} );

	describe( 'when the locations could not be loaded', () => {
		it( 'shows an error notice with "Try again" and no indicator', () => {
			mockLocations( null );

			render( <ConnectGoogleBusinessProfileAccountCard /> );

			expect(
				getNoticeText(
					"We couldn't load your Google Business Profile locations."
				)
			).toBeInTheDocument();
			expect(
				screen.queryByText( 'Action needed' )
			).not.toBeInTheDocument();
			expect(
				screen.queryByRole( 'button', { name: 'Connect' } )
			).not.toBeInTheDocument();
		} );

		it( '"Try again" fetches the locations again', async () => {
			const user = userEvent.setup();
			mockLocations( null );

			render( <ConnectGoogleBusinessProfileAccountCard /> );

			await user.click(
				screen.getByRole( 'button', { name: 'Try again' } )
			);

			expect( fetchGoogleBusinessProfileLocations ).toHaveBeenCalledTimes(
				1
			);
		} );
	} );

	describe( 'when no location was found', () => {
		it( 'shows the no-account notice with an "Action needed" badge, no Connect button', () => {
			mockLocations( [] );

			render( <ConnectGoogleBusinessProfileAccountCard /> );

			expect( screen.getByText( 'Action needed' ) ).toBeInTheDocument();
			expect( getNoticeText( NO_ACCOUNT_TEXT ) ).toBeInTheDocument();
			expect(
				screen.queryByRole( 'button', { name: 'Connect' } )
			).not.toBeInTheDocument();
			expect(
				screen.getByRole( 'link', { name: CREATE_ACCOUNT_LINK_NAME } )
			).toHaveAttribute( 'href', CREATE_LINK_HREF );
		} );

		it( 'falls back to "Google" when the connected email is unknown', () => {
			useGoogleAccount.mockReturnValue( { google: {} } );
			mockLocations( [] );

			render( <ConnectGoogleBusinessProfileAccountCard /> );

			expect(
				getNoticeText(
					"We couldn't find a Google Business Profile associated with your Google account. If you have already created an account, click the 'Check again' button to fetch your account details."
				)
			).toBeInTheDocument();
		} );

		it( '"Check again" fetches the connection and the locations again', async () => {
			const user = userEvent.setup();
			mockLocations( [] );

			render( <ConnectGoogleBusinessProfileAccountCard /> );

			await user.click(
				screen.getByRole( 'button', { name: 'Check again' } )
			);

			expect( fetchGoogleBusinessProfileAccount ).toHaveBeenCalledTimes(
				1
			);
			expect( fetchGoogleBusinessProfileLocations ).toHaveBeenCalledTimes(
				1
			);
		} );

		it( '"Create new account" records the event and asks to refresh the page', async () => {
			const user = userEvent.setup();
			mockLocations( [] );

			render( <ConnectGoogleBusinessProfileAccountCard /> );

			expect(
				queryNoticeText( 'Refresh the page to see your new account.' )
			).not.toBeInTheDocument();

			await user.click(
				screen.getByRole( 'link', { name: CREATE_ACCOUNT_LINK_NAME } )
			);

			expect( recordGlaEvent ).toHaveBeenCalledWith(
				'gla_google_business_profile_create_account_button_click',
				{ context: 'settings-business-profile' }
			);
			expect(
				getNoticeText( 'Refresh the page to see your new account.' )
			).toBeInTheDocument();
			expect( getNoticeText( NO_ACCOUNT_TEXT ) ).toBeInTheDocument();
			expect( fetchConnect ).not.toHaveBeenCalled();
		} );
	} );

	describe( 'when exactly one location was found', () => {
		it( 'shows the location and auto-selects it, enabling Connect immediately', async () => {
			const user = userEvent.setup();
			mockLocations( [ downtown ] );

			render( <ConnectGoogleBusinessProfileAccountCard /> );

			expect(
				screen.queryByText( 'Action needed' )
			).not.toBeInTheDocument();
			expect(
				getNoticeText( 'We found a Google Business Profile location.' )
			).toBeInTheDocument();
			expect(
				getNoticeText( '2423 1st Ave, Seattle, WA, 98121' )
			).toBeInTheDocument();
			expect( screen.queryByRole( 'combobox' ) ).not.toBeInTheDocument();
			expect(
				screen.getByRole( 'link', { name: CREATE_LOCATION_LINK_NAME } )
			).toHaveAttribute( 'href', CREATE_LINK_HREF );

			const connectButton = screen.getByRole( 'button', {
				name: 'Connect',
			} );
			expect( connectButton ).toBeEnabled();

			await user.click( connectButton );

			expect( useApiFetchCallback ).toHaveBeenLastCalledWith( {
				path: '/wc/gla/business-profile/locations',
				method: 'POST',
				data: { id: '1111' },
			} );
			expect( fetchConnect ).toHaveBeenCalledTimes( 1 );
			expect( fetchGoogleBusinessProfileAccount ).toHaveBeenCalledTimes(
				1
			);
		} );

		it( 'keeps the Connect button disabled through the connection refresh, not just the connect request', async () => {
			const user = userEvent.setup();
			mockLocations( [ downtown ] );

			let resolveAccountFetch;
			fetchGoogleBusinessProfileAccount.mockReturnValue(
				new Promise( ( resolve ) => {
					resolveAccountFetch = resolve;
				} )
			);

			render( <ConnectGoogleBusinessProfileAccountCard /> );

			const connectButton = screen.getByRole( 'button', {
				name: 'Connect',
			} );
			await user.click( connectButton );

			expect( fetchConnect ).toHaveBeenCalledTimes( 1 );
			expect( connectButton ).toBeDisabled();

			resolveAccountFetch();

			await waitFor( () => expect( connectButton ).toBeEnabled() );
		} );

		it( '"Create new location" records the event and asks to refresh the page', async () => {
			const user = userEvent.setup();
			mockLocations( [ downtown ] );

			render( <ConnectGoogleBusinessProfileAccountCard /> );

			await user.click(
				screen.getByRole( 'link', { name: CREATE_LOCATION_LINK_NAME } )
			);

			expect( recordGlaEvent ).toHaveBeenCalledWith(
				'gla_google_business_profile_create_location_button_click',
				{ context: 'settings-business-profile' }
			);
			expect(
				getNoticeText( 'Refresh the page to see your new location.' )
			).toBeInTheDocument();
			expect(
				getNoticeText( 'We found a Google Business Profile location.' )
			).toBeInTheDocument();
			expect(
				screen.getByRole( 'button', { name: 'Connect' } )
			).toBeInTheDocument();
		} );
	} );

	describe( 'when multiple locations were found', () => {
		it( 'shows an "Action needed" badge, the location picker and "Save", with no Connect button', () => {
			mockLocations( [ downtown, riverside ] );

			render( <ConnectGoogleBusinessProfileAccountCard /> );

			expect( screen.getByText( 'Action needed' ) ).toBeInTheDocument();
			expect(
				getNoticeText(
					'We found multiple Google Business Profile locations. Pick one to connect.'
				)
			).toBeInTheDocument();
			expect(
				screen.getByRole( 'combobox', { name: 'Select a location' } )
			).toHaveValue( '1111' );
			expect(
				screen.getAllByRole( 'option' ).map( ( option ) => {
					return option.textContent;
				} )
			).toEqual( [
				'2423 1st Ave, Seattle, WA, 98121',
				'456 Riverside Ave, Seattle, WA, 98121',
			] );
			expect(
				screen.getByRole( 'button', { name: 'Save' } )
			).toBeEnabled();
			expect(
				screen.queryByRole( 'button', { name: 'Connect' } )
			).not.toBeInTheDocument();
			expect(
				screen.getByRole( 'link', { name: CREATE_LOCATION_LINK_NAME } )
			).toHaveAttribute( 'href', CREATE_LINK_HREF );
		} );

		it( '"Save" connects the picked location and refreshes the connection', async () => {
			const user = userEvent.setup();
			mockLocations( [ downtown, riverside ] );

			render( <ConnectGoogleBusinessProfileAccountCard /> );

			await user.selectOptions(
				screen.getByRole( 'combobox', { name: 'Select a location' } ),
				'2222'
			);
			await user.click( screen.getByRole( 'button', { name: 'Save' } ) );

			expect( useApiFetchCallback ).toHaveBeenLastCalledWith( {
				path: '/wc/gla/business-profile/locations',
				method: 'POST',
				data: { id: '2222' },
			} );
			expect( fetchConnect ).toHaveBeenCalledTimes( 1 );
			expect( fetchGoogleBusinessProfileAccount ).toHaveBeenCalledTimes(
				1
			);
		} );

		it( '"Create new location" asks to refresh the page and keeps the picker', async () => {
			const user = userEvent.setup();
			mockLocations( [ downtown, riverside ] );

			render( <ConnectGoogleBusinessProfileAccountCard /> );

			await user.click(
				screen.getByRole( 'link', { name: CREATE_LOCATION_LINK_NAME } )
			);

			expect(
				getNoticeText( 'Refresh the page to see your new location.' )
			).toBeInTheDocument();
			expect(
				screen.getByRole( 'combobox', { name: 'Select a location' } )
			).toBeInTheDocument();
			expect(
				screen.getByRole( 'button', { name: 'Save' } )
			).toBeInTheDocument();
		} );
	} );

	describe( 'when connecting fails', () => {
		it( 'shows the connection-failed notice and does not refresh the connection', async () => {
			const user = userEvent.setup();
			fetchConnect.mockRejectedValue( new Error( 'Request failed' ) );
			mockLocations( [ downtown ] );

			render( <ConnectGoogleBusinessProfileAccountCard /> );

			await user.click(
				screen.getByRole( 'button', { name: 'Connect' } )
			);

			expect( screen.getByText( 'Not connected' ) ).toBeInTheDocument();
			expect(
				screen.getByText(
					"We couldn't connect Google Business Profile",
					{ selector: 'span' }
				)
			).toBeInTheDocument();
			expect(
				getNoticeText( CONNECTION_FAILED_TEXT )
			).toBeInTheDocument();
			expect(
				screen.getByRole( 'button', { name: 'Try again' } )
			).toBeInTheDocument();
			// The location detail is hidden while the connection error notice is showing.
			expect(
				queryNoticeText(
					'We found a Google Business Profile location.'
				)
			).not.toBeInTheDocument();
			expect( fetchGoogleBusinessProfileAccount ).not.toHaveBeenCalled();
		} );

		it( 'shows the backend error message when the connect request fails with a structured API error', async () => {
			const user = userEvent.setup();
			fetchConnect.mockRejectedValue( {
				code: 'API_ERROR',
				data: {
					message:
						'This Google Business Profile location is no longer available. Check again to refresh the list.',
				},
			} );
			mockLocations( [ downtown ] );

			render( <ConnectGoogleBusinessProfileAccountCard /> );

			await user.click(
				screen.getByRole( 'button', { name: 'Connect' } )
			);

			expect(
				getNoticeText(
					'This Google Business Profile location is no longer available. Check again to refresh the list.'
				)
			).toBeInTheDocument();
			expect(
				queryNoticeText( CONNECTION_FAILED_TEXT )
			).not.toBeInTheDocument();
		} );

		it( '"Try again" returns to the location picker, preserving the picked location, and does not itself reconnect', async () => {
			const user = userEvent.setup();
			fetchConnect.mockRejectedValue( new Error( 'Request failed' ) );
			mockLocations( [ downtown, riverside ] );

			render( <ConnectGoogleBusinessProfileAccountCard /> );

			await user.selectOptions(
				screen.getByRole( 'combobox', { name: 'Select a location' } ),
				'2222'
			);
			await user.click( screen.getByRole( 'button', { name: 'Save' } ) );
			expect( screen.getByText( 'Not connected' ) ).toBeInTheDocument();
			fetchConnect.mockClear();

			await user.click(
				screen.getByRole( 'button', { name: 'Try again' } )
			);

			expect(
				screen.queryByText( 'Not connected' )
			).not.toBeInTheDocument();
			expect(
				screen.getByRole( 'combobox', { name: 'Select a location' } )
			).toHaveValue( '2222' );
			expect( fetchConnect ).not.toHaveBeenCalled();
		} );
	} );
} );
