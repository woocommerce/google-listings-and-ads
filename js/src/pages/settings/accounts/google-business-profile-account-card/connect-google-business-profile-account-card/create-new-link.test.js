/**
 * External dependencies
 */
import '@testing-library/jest-dom';
import { render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';

/**
 * Internal dependencies
 */
import CreateNewLink from './create-new-link';
import useGoogleAccount from '~/hooks/useGoogleAccount';
import { recordGlaEvent } from '~/utils/tracks';

jest.mock( '~/hooks/useGoogleAccount', () =>
	jest.fn().mockName( 'useGoogleAccount' )
);
jest.mock( '~/utils/tracks', () => ( {
	recordGlaEvent: jest.fn(),
} ) );

describe.each( [
	{
		type: 'account',
		label: 'Create new account',
		eventName: 'gla_google_business_profile_create_account_button_click',
		refreshText: 'Refresh the page to see your new account.',
	},
	{
		type: 'location',
		label: 'Create new location',
		eventName: 'gla_google_business_profile_create_location_button_click',
		refreshText: 'Refresh the page to see your new location.',
	},
] )(
	'CreateNewLink for a new $type',
	( { type, label, eventName, refreshText } ) => {
		beforeEach( () => {
			jest.clearAllMocks();
			useGoogleAccount.mockReturnValue( { google: undefined } );
		} );

		it( 'shows no refresh reminder before the link is clicked', () => {
			render( <CreateNewLink type={ type } /> );

			expect( screen.queryByText( refreshText ) ).not.toBeInTheDocument();
		} );

		it( 'records the event and shows a refresh reminder above the link once clicked', async () => {
			const user = userEvent.setup();
			const { container } = render( <CreateNewLink type={ type } /> );

			await user.click(
				screen.getByRole( 'link', {
					name: `${ label } (opens in a new tab)`,
				} )
			);

			expect( recordGlaEvent ).toHaveBeenCalledWith( eventName, {
				context: 'settings-business-profile',
			} );
			expect(
				screen.getByText( refreshText, { selector: 'p' } )
			).toBeInTheDocument();
			expect(
				container.textContent.indexOf( 'Refresh the page' )
			).toBeLessThan( container.textContent.indexOf( label ) );
		} );
	}
);
