/**
 * External dependencies
 */
import '@testing-library/jest-dom';
import { render, screen } from '@testing-library/react';

/**
 * Internal dependencies
 */
import SingleLocationCard from './index';
import useGoogleAccount from '~/hooks/useGoogleAccount';

jest.mock( '~/hooks/useGoogleAccount', () =>
	jest.fn().mockName( 'useGoogleAccount' )
);

const location = {
	id: '1111',
	title: "Jane's Bakery",
	address: '2423 1st Ave, Seattle, WA, 98121',
};

describe( 'SingleLocationCard', () => {
	beforeEach( () => {
		useGoogleAccount.mockReturnValue( { google: undefined } );
	} );

	it( 'shows an enabled Connect button and the location notice once a location is picked', () => {
		render(
			<SingleLocationCard
				location={ location }
				locationId="1111"
				isConnecting={ false }
				onConnectClick={ jest.fn() }
			/>
		);

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

	it( 'disables Connect until a location is picked', () => {
		render(
			<SingleLocationCard
				location={ location }
				locationId={ undefined }
				isConnecting={ false }
				onConnectClick={ jest.fn() }
			/>
		);

		expect(
			screen.getByRole( 'button', { name: 'Connect' } )
		).toBeDisabled();
	} );
} );
