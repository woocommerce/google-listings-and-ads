/**
 * External dependencies
 */
import '@testing-library/jest-dom';
import { render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';

/**
 * Internal dependencies
 */
import ConnectLocationForm from './connect-location-form';
import useGoogleAccount from '~/hooks/useGoogleAccount';
import useGoogleBusinessProfileLocations from '../../hooks/useGoogleBusinessProfileLocations';
import useConnectGoogleBusinessProfileLocation from '../../hooks/useConnectGoogleBusinessProfileLocation';

jest.mock( '~/hooks/useGoogleAccount', () =>
	jest.fn().mockName( 'useGoogleAccount' )
);
jest.mock( '../../hooks/useGoogleBusinessProfileLocations', () =>
	jest.fn().mockName( 'useGoogleBusinessProfileLocations' )
);
jest.mock( '../../hooks/useConnectGoogleBusinessProfileLocation', () =>
	jest.fn().mockName( 'useConnectGoogleBusinessProfileLocation' )
);

describe( 'ConnectLocationForm', () => {
	let connectLocation;

	beforeEach( () => {
		connectLocation = jest.fn().mockName( 'connectLocation' );
		useConnectGoogleBusinessProfileLocation.mockReturnValue( {
			isConnecting: false,
			connectLocation,
		} );
		useGoogleAccount.mockReturnValue( { google: undefined } );
		useGoogleBusinessProfileLocations.mockReturnValue( {
			locations: [
				{ id: '1111', address: '2423 1st Ave, Seattle, WA, 98121' },
				{
					id: '2222',
					address: '456 Riverside Ave, Seattle, WA, 98121',
				},
			],
		} );
	} );

	it( 'shows the picker, with the first location picked and Save enabled', () => {
		render( <ConnectLocationForm /> );

		expect(
			screen.getByRole( 'combobox', { name: 'Select a location' } )
		).toHaveValue( '1111' );
		expect( screen.getByRole( 'button', { name: 'Save' } ) ).toBeEnabled();
	} );

	it( 'connects the picked location when Save is clicked', async () => {
		const user = userEvent.setup();
		render( <ConnectLocationForm /> );

		await user.selectOptions(
			screen.getByRole( 'combobox', { name: 'Select a location' } ),
			'2222'
		);
		await user.click( screen.getByRole( 'button', { name: 'Save' } ) );

		expect( connectLocation ).toHaveBeenCalledTimes( 1 );
		expect( connectLocation ).toHaveBeenCalledWith( '2222' );
	} );
} );
