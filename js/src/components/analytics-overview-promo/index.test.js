/**
 * External dependencies
 */
import '@testing-library/jest-dom';
import { render, screen } from '@testing-library/react';

/**
 * Internal dependencies
 */
import AnalyticsOverviewPromo from './index';

describe( 'AnalyticsOverviewPromo', () => {
	it( 'renders a placeholder', () => {
		render( <AnalyticsOverviewPromo /> );

		expect( screen.getByText( 'placeholder' ) ).toBeInTheDocument();
	} );
} );
