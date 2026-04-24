/**
 * External dependencies
 */
import '@testing-library/jest-dom';
import { render, screen } from '@testing-library/react';

/**
 * Internal dependencies
 */
import MarketsDashboard from './';

// TODO: Subject to change as the page gains content.
describe( 'MarketsDashboard', () => {
	test( 'renders the placeholder heading', () => {
		render( <MarketsDashboard /> );
		expect(
			screen.getByRole( 'heading', { name: 'Markets' } )
		).toBeInTheDocument();
	} );
} );
