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

jest.mock( './components/markets-dashboard', () =>
	jest.fn().mockReturnValue( <div data-testid="markets-dashboard" /> )
);

describe( 'Markets page', () => {
	test( 'renders the main tab navigation', () => {
		render( <Markets /> );
		expect( screen.getByTestId( 'main-tab-nav' ) ).toBeInTheDocument();
	} );

	test( 'renders the MarketsDashboard', () => {
		render( <Markets /> );
		expect( screen.getByTestId( 'markets-dashboard' ) ).toBeInTheDocument();
	} );
} );
