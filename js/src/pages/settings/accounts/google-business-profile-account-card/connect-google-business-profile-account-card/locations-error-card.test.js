/**
 * External dependencies
 */
import '@testing-library/jest-dom';
import { render, screen } from '@testing-library/react';

/**
 * Internal dependencies
 */
import LocationsErrorCard from './locations-error-card';
import { useAppDispatch } from '~/data';

jest.mock( '~/data', () => ( {
	...jest.requireActual( '~/data' ),
	useAppDispatch: jest.fn().mockName( 'useAppDispatch' ),
} ) );

describe( 'LocationsErrorCard', () => {
	it( 'shows the locations error notice and no indicator', () => {
		useAppDispatch.mockReturnValue( {
			fetchGoogleBusinessProfileLocations: jest.fn(),
		} );

		render( <LocationsErrorCard /> );

		expect(
			screen.getByText(
				"We couldn't load your Google Business Profile locations.",
				{ selector: 'p' }
			)
		).toBeInTheDocument();
		expect( screen.queryByText( 'Action needed' ) ).not.toBeInTheDocument();
	} );
} );
