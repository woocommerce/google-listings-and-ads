/**
 * External dependencies
 */
import '@testing-library/jest-dom';
import { render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';

/**
 * Internal dependencies
 */
import SingleLocationCard from './index';
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

const location = {
	id: '1111',
	title: "Jane's Bakery",
	address: '2423 1st Ave, Seattle, WA, 98121',
};

describe( 'SingleLocationCard', () => {
	let connectLocation;

	beforeEach( () => {
		connectLocation = jest.fn().mockName( 'connectLocation' );
		useGoogleAccount.mockReturnValue( { google: undefined } );
		useGoogleBusinessProfileLocations.mockReturnValue( {
			locations: [ location ],
			hasFinishedResolution: true,
		} );
		useConnectGoogleBusinessProfileLocation.mockReturnValue( {
			isConnecting: false,
			connectLocation,
		} );
	} );

	it( 'shows an enabled Connect button and the location notice', () => {
		render( <SingleLocationCard /> );

		expect(
			screen.getByRole( 'button', { name: 'Connect' } )
		).toBeEnabled();
		expect(
			screen.getByText( '2423 1st Ave, Seattle, WA, 98121', {
				selector: 'p',
			} )
		).toBeInTheDocument();
		expect( screen.queryByText( 'Action needed' ) ).not.toBeInTheDocument();
	} );

	it( 'connects the location when Connect is clicked', async () => {
		const user = userEvent.setup();
		render( <SingleLocationCard /> );

		await user.click( screen.getByRole( 'button', { name: 'Connect' } ) );

		expect( connectLocation ).toHaveBeenCalledTimes( 1 );
		expect( connectLocation ).toHaveBeenCalledWith( '1111' );
	} );

	it( 'disables Connect while the connect request is in flight', () => {
		useConnectGoogleBusinessProfileLocation.mockReturnValue( {
			isConnecting: true,
			connectLocation,
		} );

		render( <SingleLocationCard /> );

		expect(
			screen.getByRole( 'button', { name: 'Connect' } )
		).toBeDisabled();
	} );
} );
