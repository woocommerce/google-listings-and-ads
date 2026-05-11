/**
 * External dependencies
 */
import '@testing-library/jest-dom';
import { render, screen } from '@testing-library/react';

/**
 * Internal dependencies
 */
import MarketsDashboard from '../markets-dashboard';
import MarketsHeader from '../markets-header';
import useDataViewsScript from '~/hooks/useDataViewsScript';
import useSettings from '~/hooks/useSettings';
import { SHIPPING_RATE_METHOD } from '~/constants';

jest.mock( '~/hooks/useDataViewsScript' );
jest.mock( '~/hooks/useSettings' );

jest.mock( '../market-data-views', () =>
	jest.fn().mockReturnValue( <div data-testid="market-data-views" /> )
);

jest.mock( './markets-header', () =>
	jest.fn().mockReturnValue( <div data-testid="markets-header" /> )
);

const mockShippingRate = ( shippingRate ) =>
	useSettings.mockReturnValue( {
		settings: shippingRate ? { shipping_rate: shippingRate } : undefined,
	} );

const mockDataViewStatus = ( status = 'loading' ) =>
	useDataViewsScript.mockReturnValue( status );

beforeEach( () => {
	mockShippingRate();
	mockDataViewStatus();
} );

afterEach( () => {
	useDataViewsScript.mockReset();
	useSettings.mockReset();
	MarketsHeader.mockClear();
} );

describe( 'MarketsDashboard', () => {
	describe( 'DataViews shim loading', () => {
		test( 'renders a spinner while the shim has not loaded yet', () => {
			mockDataViewStatus( 'loading' );
			render( <MarketsDashboard /> );

			expect(
				screen.getByRole( 'status', { name: 'Loading…' } )
			).toBeInTheDocument();
			expect(
				screen.queryByTestId( 'market-data-views' )
			).not.toBeInTheDocument();
		} );

		test( 'renders MarketDataViews once the shim is available', () => {
			mockDataViewStatus( 'ready' );

			render( <MarketsDashboard /> );

			expect(
				screen.getByTestId( 'market-data-views' )
			).toBeInTheDocument();
			expect(
				screen.queryByRole( 'status', { name: 'Loading…' } )
			).not.toBeInTheDocument();
		} );

		test( 'renders a warning notice and hides card when DataViews script fails', () => {
			mockDataViewStatus( 'failed' );
			render( <MarketsDashboard /> );

			expect(
				screen.getAllByText(
					'There was an error loading the markets dashboard.'
				).length
			).toBeGreaterThan( 0 );
			expect(
				screen.queryByTestId( 'market-data-views' )
			).not.toBeInTheDocument();
			expect(
				screen.queryByRole( 'status', { name: 'Loading…' } )
			).not.toBeInTheDocument();
		} );
	} );

	describe( 'Shipping rate wiring', () => {
		test( 'forwards the resolved shipping rate from useSettings to MarketsHeader', () => {
			mockShippingRate( SHIPPING_RATE_METHOD.AUTOMATIC );
			render( <MarketsDashboard /> );

			expect( MarketsHeader ).toHaveBeenCalledWith(
				expect.objectContaining( {
					shippingRate: SHIPPING_RATE_METHOD.AUTOMATIC,
				} ),
				expect.anything()
			);
		} );

		test( 'forwards `undefined` to MarketsHeader when settings have not resolved', () => {
			render( <MarketsDashboard /> );

			expect( MarketsHeader ).toHaveBeenCalledWith(
				expect.objectContaining( { shippingRate: undefined } ),
				expect.anything()
			);
		} );
	} );
} );
