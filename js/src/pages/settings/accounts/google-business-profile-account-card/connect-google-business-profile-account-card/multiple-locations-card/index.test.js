/**
 * External dependencies
 */
import '@testing-library/jest-dom';
import { render, screen } from '@testing-library/react';

/**
 * Internal dependencies
 */
import MultipleLocationsCard from './index';
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

describe( 'MultipleLocationsCard', () => {
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

	it( 'shows an "Action needed" badge and the form to pick a location', () => {
		render( <MultipleLocationsCard /> );

		expect( screen.getByText( 'Action needed' ) ).toBeInTheDocument();
		expect(
			screen.getByRole( 'combobox', { name: 'Select a location' } )
		).toBeInTheDocument();
		expect(
			screen.getByRole( 'button', { name: 'Save' } )
		).toBeInTheDocument();
	} );
} );
