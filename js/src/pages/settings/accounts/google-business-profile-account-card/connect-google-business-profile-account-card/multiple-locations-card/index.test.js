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

jest.mock( '~/hooks/useGoogleAccount', () =>
	jest.fn().mockName( 'useGoogleAccount' )
);
jest.mock( '../../hooks/useGoogleBusinessProfileLocations', () =>
	jest.fn().mockName( 'useGoogleBusinessProfileLocations' )
);

describe( 'MultipleLocationsCard', () => {
	beforeEach( () => {
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

	it( 'shows an "Action needed" badge, the picker, and an enabled Save once a location is picked', () => {
		render(
			<MultipleLocationsCard
				locationId="1111"
				onLocationChange={ jest.fn() }
				isConnecting={ false }
				onConnectClick={ jest.fn() }
			/>
		);

		expect( screen.getByText( 'Action needed' ) ).toBeInTheDocument();
		expect(
			screen.getByRole( 'combobox', { name: 'Select a location' } )
		).toHaveValue( '1111' );
		expect( screen.getByRole( 'button', { name: 'Save' } ) ).toBeEnabled();
	} );

	it( 'disables Save until a location is picked', () => {
		render(
			<MultipleLocationsCard
				locationId={ undefined }
				onLocationChange={ jest.fn() }
				isConnecting={ false }
				onConnectClick={ jest.fn() }
			/>
		);

		expect( screen.getByRole( 'button', { name: 'Save' } ) ).toBeDisabled();
	} );
} );
