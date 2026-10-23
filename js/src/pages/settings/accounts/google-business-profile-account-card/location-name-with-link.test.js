/**
 * External dependencies
 */
import '@testing-library/jest-dom';
import { render, screen } from '@testing-library/react';

/**
 * Internal dependencies
 */
import LocationNameWithLink from './location-name-with-link';
import useGoogleAccount from '~/hooks/useGoogleAccount';

jest.mock( '~/hooks/useGoogleAccount', () =>
	jest.fn().mockName( 'useGoogleAccount' )
);

const location = {
	id: '1111',
	title: "Jane's Bakery",
	address: '2423 1st Ave, Seattle, WA, 98121',
	mapsUri: 'https://maps.google.com/?cid=1111',
};

describe( 'LocationNameWithLink', () => {
	it( "links the location's address to its listing on Google", () => {
		useGoogleAccount.mockReturnValue( { google: undefined } );

		render( <LocationNameWithLink location={ location } /> );

		expect(
			screen.getByRole( 'link', {
				name: '2423 1st Ave, Seattle, WA, 98121 (opens in a new tab)',
			} )
		).toHaveAttribute( 'href', 'https://maps.google.com/?cid=1111' );
	} );

	it( 'resolves the link to the connected Google account when its email is known', () => {
		useGoogleAccount.mockReturnValue( {
			google: { email: 'merchant@example.com' },
		} );

		render( <LocationNameWithLink location={ location } /> );

		expect(
			screen.getByRole( 'link', {
				name: '2423 1st Ave, Seattle, WA, 98121 (opens in a new tab)',
			} )
		).toHaveAttribute(
			'href',
			'https://accounts.google.com/accountchooser?continue=https%3A%2F%2Fmaps.google.com%2F%3Fcid%3D1111&Email=merchant%40example.com'
		);
	} );

	it( 'shows the address as plain text when the location has no listing URL', () => {
		useGoogleAccount.mockReturnValue( { google: undefined } );

		render(
			<LocationNameWithLink location={ { ...location, mapsUri: '' } } />
		);

		expect(
			screen.getByText( '2423 1st Ave, Seattle, WA, 98121' )
		).toBeInTheDocument();
		expect( screen.queryByRole( 'link' ) ).not.toBeInTheDocument();
	} );
} );
