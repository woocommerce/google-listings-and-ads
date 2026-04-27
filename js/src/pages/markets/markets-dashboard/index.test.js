/**
 * External dependencies
 */
import '@testing-library/jest-dom';
import { render, screen } from '@testing-library/react';

/**
 * Internal dependencies
 */
import MarketsDashboard from './';
import MarketsHeader from './markets-header';
import useSettings from '~/hooks/useSettings';
import { SHIPPING_RATE_OPTION } from '../constants';

jest.mock( '~/hooks/useSettings' );

jest.mock( './market-data-views', () =>
	jest.fn().mockReturnValue( <div data-testid="market-data-views" /> )
);

jest.mock( './markets-header', () =>
	jest.fn().mockReturnValue( <div data-testid="markets-header" /> )
);

const mockShippingRate = ( shippingRate ) =>
	useSettings.mockReturnValue( {
		settings: shippingRate ? { shipping_rate: shippingRate } : undefined,
	} );

beforeEach( () => {
	delete window.wp;
	window.glaData.dataViewsScriptUrl = '';
	mockShippingRate();
} );

afterEach( () => {
	useSettings.mockReset();
	MarketsHeader.mockClear();
} );

describe( 'MarketsDashboard', () => {
	describe( 'DataViews shim loading', () => {
		test( 'renders a spinner while the shim has not loaded yet', () => {
			render( <MarketsDashboard /> );

			expect(
				screen.getByRole( 'status', { name: 'Loading…' } )
			).toBeInTheDocument();
			expect(
				screen.queryByTestId( 'market-data-views' )
			).not.toBeInTheDocument();
		} );

		test( 'renders MarketDataViews once the shim is available', () => {
			window.wp = {
				dataviews: { filterSortAndPaginate: jest.fn() },
			};

			render( <MarketsDashboard /> );

			expect(
				screen.getByTestId( 'market-data-views' )
			).toBeInTheDocument();
			expect(
				screen.queryByRole( 'status', { name: 'Loading…' } )
			).not.toBeInTheDocument();
		} );
	} );

	describe( 'Shipping rate wiring', () => {
		test( 'forwards the resolved shipping rate from useSettings to MarketsHeader', () => {
			mockShippingRate( SHIPPING_RATE_OPTION.AUTOMATIC );
			render( <MarketsDashboard /> );

			expect( MarketsHeader ).toHaveBeenCalledWith(
				expect.objectContaining( {
					shippingRate: SHIPPING_RATE_OPTION.AUTOMATIC,
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
