/**
 * External dependencies
 */
import '@testing-library/jest-dom';
import { render, screen } from '@testing-library/react';

/**
 * Internal dependencies
 */
import PriceBenchmark from './';
import useDataViewsScript from '~/hooks/useDataViewsScript';

jest.mock( '~/hooks/useDataViewsScript' );

jest.mock( './banner', () =>
	jest.fn().mockReturnValue( <div data-testid="price-benchmark-banner" /> )
);

jest.mock( '~/components/experience-rating-banner', () =>
	jest.fn().mockReturnValue( <div data-testid="experience-rating-banner" /> )
);

jest.mock( '~/components/main-tab-nav', () =>
	jest.fn().mockReturnValue( <div data-testid="main-tab-nav" /> )
);

jest.mock( './product-comparison-chart', () =>
	jest.fn().mockReturnValue( <div data-testid="product-comparison-chart" /> )
);

jest.mock( './price-benchmark-suggestions', () =>
	jest
		.fn()
		.mockReturnValue( <div data-testid="price-benchmark-suggestions" /> )
);

const mockDataViewState = ( state = {} ) =>
	useDataViewsScript.mockReturnValue( {
		dataViewIsLoading: false,
		dataViewHasFailed: false,
		dataViewIsReady: false,
		...state,
	} );

beforeEach( () => {
	mockDataViewState();
} );

afterEach( () => {
	useDataViewsScript.mockReset();
} );

describe( 'PriceBenchmark', () => {
	test( 'renders spinner while DataViews script is loading', () => {
		mockDataViewState( { dataViewIsLoading: true } );

		render( <PriceBenchmark /> );

		expect(
			screen.getByRole( 'status', { name: 'Loading…' } )
		).toBeInTheDocument();
		expect(
			screen.queryByTestId( 'price-benchmark-suggestions' )
		).not.toBeInTheDocument();
	} );

	test( 'renders suggestions once DataViews script is ready', () => {
		mockDataViewState( { dataViewIsReady: true } );

		render( <PriceBenchmark /> );

		expect(
			screen.getByTestId( 'price-benchmark-suggestions' )
		).toBeInTheDocument();
		expect(
			screen.queryByRole( 'status', { name: 'Loading…' } )
		).not.toBeInTheDocument();
	} );

	test( 'renders a warning notice and hides card when DataViews script fails', () => {
		mockDataViewState( { dataViewHasFailed: true } );

		render( <PriceBenchmark /> );

		expect(
			screen.getAllByText(
				'There was an error loading the price benchmark suggestions.'
			).length
		).toBeGreaterThan( 0 );
		expect(
			screen.queryByRole( 'status', { name: 'Loading…' } )
		).not.toBeInTheDocument();
		expect(
			screen.queryByTestId( 'price-benchmark-suggestions' )
		).not.toBeInTheDocument();
	} );
} );
