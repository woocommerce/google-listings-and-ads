/**
 * External dependencies
 */
import '@testing-library/jest-dom';
import { render, screen, within } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import apiFetch from '@wordpress/api-fetch';

/**
 * Internal dependencies
 */
import ConnectGoogleBusinessProfileAccountCard from './index';
import useGoogleBusinessProfileLocations from '../hooks/useGoogleBusinessProfileLocations';
import useGoogleAccount from '~/hooks/useGoogleAccount';
import { useAppDispatch } from '~/data';
import { recordGlaEvent } from '~/utils/tracks';

jest.mock( '@wordpress/api-fetch', () => jest.fn() );
jest.mock( '../hooks/useGoogleBusinessProfileLocations' );
jest.mock( '~/hooks/useGoogleAccount' );
jest.mock( '~/data', () => ( {
	useAppDispatch: jest.fn(),
} ) );
jest.mock( '~/utils/tracks', () => ( {
	recordGlaEvent: jest.fn().mockName( 'recordGlaEvent' ),
} ) );

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

const CREATE_URL =
	'https://accounts.google.com/accountchooser?continue=https%3A%2F%2Fbusiness.google.com%2Fcreate&Email=merchant%40example.com';

describe( 'ConnectGoogleBusinessProfileAccountCard', () => {
	let refetch;
	let connectGoogleBusinessProfileLocation;

	const mockLocations = ( overrides ) => {
		useGoogleBusinessProfileLocations.mockReturnValue( {
			locations: null,
			isLoading: false,
			hasError: false,
			refetch,
			...overrides,
		} );
	};

	beforeEach( () => {
		jest.clearAllMocks();

		refetch = jest.fn().mockName( 'refetch' );
		connectGoogleBusinessProfileLocation = jest
			.fn()
			.mockName( 'connectGoogleBusinessProfileLocation' )
			.mockResolvedValue( {} );
		useAppDispatch.mockReturnValue( {
			connectGoogleBusinessProfileLocation,
		} );
		useGoogleAccount.mockReturnValue( {
			google: { email: 'merchant@example.com' },
		} );
	} );

	describe( 'while looking up locations', () => {
		it( 'shows a loading spinner and no "Action needed" badge or notice', () => {
			mockLocations( { isLoading: true } );

			render( <ConnectGoogleBusinessProfileAccountCard /> );

			expect(
				document.querySelector( '.gla-account-card__indicator' )
			).not.toBeEmptyDOMElement();
			expect(
				screen.queryByText( 'Action needed' )
			).not.toBeInTheDocument();
			expect(
				document.querySelector( '.components-notice' )
			).not.toBeInTheDocument();
		} );
	} );

	describe( 'when the lookup fails', () => {
		it( 'shows an error notice whose "Try again" requests the locations again', async () => {
			const user = userEvent.setup();
			mockLocations( { hasError: true } );

			render( <ConnectGoogleBusinessProfileAccountCard /> );

			expect(
				screen.getByText(
					"We couldn't load your Google Business Profile locations.",
					{ selector: 'p' }
				)
			).toBeInTheDocument();
			expect(
				screen.queryByText( 'Action needed' )
			).not.toBeInTheDocument();

			await user.click(
				screen.getByRole( 'button', { name: 'Try again' } )
			);

			expect( refetch ).toHaveBeenCalledTimes( 1 );
		} );
	} );

	describe( 'when there is no profile', () => {
		beforeEach( () => {
			mockLocations( { locations: [] } );
		} );

		it( 'shows "Action needed", the no-profile notice with the connected email, and its actions', () => {
			render( <ConnectGoogleBusinessProfileAccountCard /> );

			expect( screen.getByText( 'Action needed' ) ).toBeInTheDocument();
			expect(
				screen.getByText(
					"We couldn't find a Google Business Profile associated with your merchant@example.com account. If you have already created an account, click the 'Check again' button to fetch your account details.",
					{ selector: 'p' }
				)
			).toBeInTheDocument();

			const createLink = screen.getByRole( 'link', {
				name: /Create new account/,
			} );
			expect( createLink ).toHaveAttribute( 'href', CREATE_URL );
			expect( createLink ).toHaveAttribute( 'target', '_blank' );
		} );

		it( 'falls back to "Google" when the connected email is not known', () => {
			useGoogleAccount.mockReturnValue( { google: undefined } );

			render( <ConnectGoogleBusinessProfileAccountCard /> );

			expect(
				screen.getByText(
					"We couldn't find a Google Business Profile associated with your Google account. If you have already created an account, click the 'Check again' button to fetch your account details.",
					{ selector: 'p' }
				)
			).toBeInTheDocument();
		} );

		it( '"Check again" requests the locations again', async () => {
			const user = userEvent.setup();

			render( <ConnectGoogleBusinessProfileAccountCard /> );

			await user.click(
				screen.getByRole( 'button', { name: 'Check again' } )
			);

			expect( refetch ).toHaveBeenCalledTimes( 1 );
		} );

		it( '"Create new account" asks to refresh the page, records the event, and calls no plugin endpoint', async () => {
			const user = userEvent.setup();

			render( <ConnectGoogleBusinessProfileAccountCard /> );

			await user.click(
				screen.getByRole( 'link', { name: /Create new account/ } )
			);

			expect(
				screen.getByText( 'Refresh the page to see your new account.', {
					selector: 'p',
				} )
			).toBeInTheDocument();
			expect( recordGlaEvent ).toHaveBeenCalledWith(
				'gla_google_business_profile_create_account_button_click',
				{ context: 'settings-business-profile' }
			);
			expect( apiFetch ).not.toHaveBeenCalled();
			expect( refetch ).not.toHaveBeenCalled();
		} );
	} );

	describe( 'when exactly one location is found', () => {
		beforeEach( () => {
			mockLocations( { locations: [ downtown ] } );
		} );

		it( 'shows the location with a "Connect" button and no dropdown', () => {
			render( <ConnectGoogleBusinessProfileAccountCard /> );

			expect(
				screen.getByText(
					'We found a Google Business Profile location.',
					{ selector: 'p' }
				)
			).toBeInTheDocument();
			expect(
				screen.getByText( '2423 1st Ave, Seattle, WA, 98121', {
					selector: 'p',
				} )
			).toBeInTheDocument();
			expect( screen.queryByRole( 'combobox' ) ).not.toBeInTheDocument();
			expect(
				screen.queryByText( 'Action needed' )
			).not.toBeInTheDocument();
		} );

		it( '"Create new location" opens Google, asks to refresh the page and keeps the address', async () => {
			const user = userEvent.setup();

			render( <ConnectGoogleBusinessProfileAccountCard /> );

			const link = screen.getByRole( 'link', {
				name: /Create new location/,
			} );
			expect( link ).toHaveAttribute( 'href', CREATE_URL );
			expect( link ).toHaveAttribute( 'target', '_blank' );

			await user.click( link );

			expect(
				screen.getByText(
					'Refresh the page to see your new location.',
					{
						selector: 'p',
					}
				)
			).toBeInTheDocument();
			expect(
				screen.getByText( '2423 1st Ave, Seattle, WA, 98121', {
					selector: 'p',
				} )
			).toBeInTheDocument();
			expect( recordGlaEvent ).toHaveBeenCalledWith(
				'gla_google_business_profile_create_location_button_click',
				{ context: 'settings-business-profile' }
			);
			expect( apiFetch ).not.toHaveBeenCalled();
		} );

		it( '"Connect" connects that location', async () => {
			const user = userEvent.setup();

			render( <ConnectGoogleBusinessProfileAccountCard /> );

			await user.click(
				screen.getByRole( 'button', { name: 'Connect' } )
			);

			expect( connectGoogleBusinessProfileLocation ).toHaveBeenCalledWith(
				downtown
			);
		} );

		it( 're-enables "Connect" once the request settles, even if it did not connect', async () => {
			const user = userEvent.setup();

			render( <ConnectGoogleBusinessProfileAccountCard /> );

			await user.click(
				screen.getByRole( 'button', { name: 'Connect' } )
			);

			expect(
				screen.getByRole( 'button', { name: 'Connect' } )
			).toBeEnabled();
		} );

		it( 're-enables "Connect" when connecting fails', async () => {
			const user = userEvent.setup();
			connectGoogleBusinessProfileLocation.mockRejectedValue(
				new Error( 'failed' )
			);

			render( <ConnectGoogleBusinessProfileAccountCard /> );

			await user.click(
				screen.getByRole( 'button', { name: 'Connect' } )
			);

			expect(
				screen.getByRole( 'button', { name: 'Connect' } )
			).toBeEnabled();
		} );
	} );

	describe( 'when more than one location is found', () => {
		beforeEach( () => {
			mockLocations( { locations: [ downtown, riverside ] } );
		} );

		it( 'shows "Action needed", the notice, a location dropdown, "Save" and "Create new location"', () => {
			render( <ConnectGoogleBusinessProfileAccountCard /> );

			expect( screen.getByText( 'Action needed' ) ).toBeInTheDocument();
			expect(
				screen.getByText(
					'We found multiple Google Business Profile locations. Pick one to connect.',
					{ selector: 'p' }
				)
			).toBeInTheDocument();

			const select = screen.getByRole( 'combobox', {
				name: 'Select a location',
			} );
			expect(
				within( select )
					.getAllByRole( 'option' )
					.map( ( option ) => {
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
				screen.getByRole( 'link', { name: /Create new location/ } )
			).toHaveAttribute( 'href', CREATE_URL );
		} );

		it( '"Save" connects the picked location', async () => {
			const user = userEvent.setup();

			render( <ConnectGoogleBusinessProfileAccountCard /> );

			await user.selectOptions(
				screen.getByRole( 'combobox', { name: 'Select a location' } ),
				'2222'
			);
			await user.click( screen.getByRole( 'button', { name: 'Save' } ) );

			expect( connectGoogleBusinessProfileLocation ).toHaveBeenCalledWith(
				riverside
			);
		} );

		it( '"Create new location" asks to refresh the page and records the event', async () => {
			const user = userEvent.setup();

			render( <ConnectGoogleBusinessProfileAccountCard /> );

			await user.click(
				screen.getByRole( 'link', { name: /Create new location/ } )
			);

			expect(
				screen.getByText(
					'Refresh the page to see your new location.',
					{
						selector: 'p',
					}
				)
			).toBeInTheDocument();
			expect( recordGlaEvent ).toHaveBeenCalledWith(
				'gla_google_business_profile_create_location_button_click',
				{ context: 'settings-business-profile' }
			);
			expect( apiFetch ).not.toHaveBeenCalled();
		} );
	} );
} );
