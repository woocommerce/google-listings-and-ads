/**
 * External dependencies
 */
import '@testing-library/jest-dom';
import { render, screen } from '@testing-library/react';

/**
 * Internal dependencies
 */
import LoadingLocationsCard from './loading-locations-card';

describe( 'LoadingLocationsCard', () => {
	it( 'shows a loading spinner and no indicator', () => {
		render( <LoadingLocationsCard /> );

		expect( screen.getByRole( 'status' ) ).toBeInTheDocument();
		expect( screen.queryByRole( 'button' ) ).not.toBeInTheDocument();
	} );
} );
