/**
 * External dependencies
 */
import '@testing-library/jest-dom';
import { render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';

/**
 * Internal dependencies
 */
import NoLocationsCard from './index';
import { useAppDispatch } from '~/data';
import useGoogleAccount from '~/hooks/useGoogleAccount';
import { recordGlaEvent } from '~/utils/tracks';

jest.mock( '~/data', () => ( {
	...jest.requireActual( '~/data' ),
	useAppDispatch: jest.fn().mockName( 'useAppDispatch' ),
} ) );
jest.mock( '~/hooks/useGoogleAccount', () =>
	jest.fn().mockName( 'useGoogleAccount' )
);
jest.mock( '~/utils/tracks', () => ( {
	recordGlaEvent: jest.fn(),
} ) );

describe( 'NoLocationsCard', () => {
	beforeEach( () => {
		jest.clearAllMocks();
		useAppDispatch.mockReturnValue( {
			fetchGoogleBusinessProfileAccount: jest.fn(),
			fetchGoogleBusinessProfileLocations: jest.fn(),
		} );
		useGoogleAccount.mockReturnValue( {
			google: { email: 'merchant@example.com' },
		} );
	} );

	it( 'shows an "Action needed" badge and the zero-locations notice', () => {
		render( <NoLocationsCard /> );

		expect( screen.getByText( 'Action needed' ) ).toBeInTheDocument();
		expect(
			screen.getByText( /We couldn't find a Google Business Profile/, {
				selector: 'p',
			} )
		).toBeInTheDocument();
	} );

	it( 'records the event when "Check again" is clicked', async () => {
		const user = userEvent.setup();
		render( <NoLocationsCard /> );

		await user.click(
			screen.getByRole( 'button', { name: 'Check again' } )
		);

		expect( recordGlaEvent ).toHaveBeenCalledWith(
			'gla_google_business_profile_check_again_button_click',
			{ context: 'settings-business-profile' }
		);
	} );
} );
