/**
 * External dependencies
 */
import '@testing-library/jest-dom';
import { render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';

/**
 * Internal dependencies
 */
import CreateNewLocationLink from './create-new-location-link';
import useGoogleAccount from '~/hooks/useGoogleAccount';
import { recordGlaEvent } from '~/utils/tracks';

jest.mock( '~/hooks/useGoogleAccount', () =>
	jest.fn().mockName( 'useGoogleAccount' )
);
jest.mock( '~/utils/tracks', () => ( {
	recordGlaEvent: jest.fn(),
} ) );

describe( 'CreateNewLocationLink', () => {
	beforeEach( () => {
		useGoogleAccount.mockReturnValue( { google: undefined } );
	} );

	it( 'shows no refresh reminder before the link is clicked', () => {
		render( <CreateNewLocationLink /> );

		expect(
			screen.queryByText( 'Refresh the page to see your new location.' )
		).not.toBeInTheDocument();
	} );

	it( 'records the event and shows a refresh reminder above the link once clicked', async () => {
		const user = userEvent.setup();
		const { container } = render( <CreateNewLocationLink /> );

		await user.click(
			screen.getByRole( 'link', {
				name: 'Create new location (opens in a new tab)',
			} )
		);

		expect( recordGlaEvent ).toHaveBeenCalledWith(
			'gla_google_business_profile_create_location_button_click',
			{ context: 'settings-business-profile' }
		);
		expect(
			screen.getByText( 'Refresh the page to see your new location.', {
				selector: 'p',
			} )
		).toBeInTheDocument();
		expect(
			container.textContent.indexOf( 'Refresh the page' )
		).toBeLessThan(
			container.textContent.indexOf( 'Create new location' )
		);
	} );
} );
