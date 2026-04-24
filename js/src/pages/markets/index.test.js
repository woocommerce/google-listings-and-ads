/**
 * External dependencies
 */
import '@testing-library/jest-dom';
import { render, screen } from '@testing-library/react';

/**
 * Internal dependencies
 */
import Markets from './';

jest.mock( '~/components/main-tab-nav', () =>
	jest.fn().mockReturnValue( <div data-testid="main-tab-nav" /> )
);

jest.mock( '~/components/experience-rating-banner', () =>
	jest.fn().mockReturnValue( null ).mockName( 'ExperienceRatingBanner' )
);

// TODO: Subject to change as the page gains content.
describe( 'Markets page', () => {
	test( 'renders the main tab navigation', () => {
		render( <Markets /> );
		expect( screen.getByTestId( 'main-tab-nav' ) ).toBeInTheDocument();
	} );

	test( 'renders the MarketsDashboard placeholder heading', () => {
		render( <Markets /> );
		expect(
			screen.getByRole( 'heading', { name: 'Markets' } )
		).toBeInTheDocument();
	} );
} );
