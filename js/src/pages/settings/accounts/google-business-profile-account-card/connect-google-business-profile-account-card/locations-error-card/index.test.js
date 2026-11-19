/**
 * External dependencies
 */
import '@testing-library/jest-dom';
import { render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';

/**
 * Internal dependencies
 */
import LocationsErrorCard from './index';
import { useAppDispatch } from '~/data';
import { recordGlaEvent } from '~/utils/tracks';

jest.mock( '~/data', () => ( {
	...jest.requireActual( '~/data' ),
	useAppDispatch: jest.fn().mockName( 'useAppDispatch' ),
} ) );
jest.mock( '~/utils/tracks', () => ( {
	recordGlaEvent: jest.fn(),
} ) );

describe( 'LocationsErrorCard', () => {
	beforeEach( () => {
		jest.clearAllMocks();
		useAppDispatch.mockReturnValue( {
			fetchGoogleBusinessProfileLocations: jest.fn(),
		} );
	} );

	it( 'shows the locations error notice and no indicator', () => {
		render( <LocationsErrorCard /> );

		expect(
			screen.getByText(
				"We couldn't load your Google Business Profile locations.",
				{ selector: 'p' }
			)
		).toBeInTheDocument();
		expect( screen.queryByText( 'Action needed' ) ).not.toBeInTheDocument();
	} );

	it( 'records the event when "Try again" is clicked', async () => {
		const user = userEvent.setup();
		render( <LocationsErrorCard /> );

		await user.click( screen.getByRole( 'button', { name: 'Try again' } ) );

		expect( recordGlaEvent ).toHaveBeenCalledWith(
			'gla_google_business_profile_locations_try_again_button_click',
			{ context: 'settings-business-profile' }
		);
	} );
} );
