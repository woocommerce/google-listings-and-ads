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
	},
	{
		type: 'location',
		label: 'Create new location',
		eventName: 'gla_google_business_profile_create_location_button_click',
	},
] )( 'CreateNewLink for a new $type', ( { type, label, eventName } ) => {
	beforeEach( () => {
		jest.clearAllMocks();
		useGoogleAccount.mockReturnValue( { google: undefined } );
	} );

	it( 'records the event and calls onClick once clicked', async () => {
		const user = userEvent.setup();
		const onClick = jest.fn().mockName( 'onClick' );
		render( <CreateNewLink type={ type } onClick={ onClick } /> );

		await user.click(
			screen.getByRole( 'link', {
				name: `${ label } (opens in a new tab)`,
			} )
		);

		expect( recordGlaEvent ).toHaveBeenCalledWith( eventName, {
			context: 'settings-business-profile',
		} );
		expect( onClick ).toHaveBeenCalledTimes( 1 );
	} );
} );
