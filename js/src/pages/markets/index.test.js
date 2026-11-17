/**
 * External dependencies
 */
import '@testing-library/jest-dom';
import { render, screen } from '@testing-library/react';

/**
 * Internal dependencies
 */
import Markets from './';

jest.mock( '~/components/main-page-header', () =>
	jest.fn( () => <div data-testid="main-page-header" /> )
);

jest.mock( '~/components/gtin-migration-banner', () => () => null );

jest.mock( '~/components/experience-rating-banner', () =>
	jest.fn().mockReturnValue( null ).mockName( 'ExperienceRatingBanner' )
);

jest.mock( './components/markets-dashboard', () =>
	jest.fn( () => <div data-testid="markets-dashboard" /> )
);

describe( 'Markets page', () => {
	test( 'renders the main page header', () => {
		render( <Markets /> );
		expect( screen.getByTestId( 'main-page-header' ) ).toBeInTheDocument();
	} );

	test( 'renders the MarketsDashboard', () => {
		render( <Markets /> );
		expect( screen.getByTestId( 'markets-dashboard' ) ).toBeInTheDocument();
	} );
} );
