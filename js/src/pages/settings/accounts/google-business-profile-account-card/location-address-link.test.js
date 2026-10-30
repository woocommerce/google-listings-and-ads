/**
 * External dependencies
 */
import '@testing-library/jest-dom';
import { render, screen } from '@testing-library/react';

/**
 * Internal dependencies
 */
import LocationAddressLink from './location-address-link';

describe( 'LocationAddressLink', () => {
	it( "links the address to the location's listing on Google", () => {
		render(
			<LocationAddressLink
				address="2423 1st Ave, Seattle, WA, 98121"
				url="https://maps.google.com/?cid=1111"
			/>
		);

		expect(
			screen.getByRole( 'link', {
				name: '2423 1st Ave, Seattle, WA, 98121 (opens in a new tab)',
			} )
		).toHaveAttribute( 'href', 'https://maps.google.com/?cid=1111' );
	} );

	it( 'shows the address as plain text when there is no listing URL', () => {
		render(
			<LocationAddressLink
				address="2423 1st Ave, Seattle, WA, 98121"
				url={ undefined }
			/>
		);

		expect(
			screen.getByText( '2423 1st Ave, Seattle, WA, 98121' )
		).toBeInTheDocument();
		expect( screen.queryByRole( 'link' ) ).not.toBeInTheDocument();
	} );
} );
