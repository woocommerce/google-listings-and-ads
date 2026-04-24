/**
 * External dependencies
 */
import '@testing-library/jest-dom';
import { render, screen } from '@testing-library/react';

/**
 * Internal dependencies
 */
import MarketsDashboard from './';
import useSettings from '~/hooks/useSettings';
import { SHIPPING_RATE_OPTION } from '../constants';

jest.mock( '~/hooks/useSettings' );

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
} );

describe( 'MarketsDashboard', () => {
	describe( 'DataViews shim loading', () => {
		test( 'renders a spinner while the shim has not loaded yet', () => {
			render( <MarketsDashboard /> );

			expect(
				screen.getByRole( 'status', { name: 'Loading…' } )
			).toBeInTheDocument();
			expect(
				screen.queryByText( 'MarketDataViews placeholder' )
			).not.toBeInTheDocument();
		} );

		test( 'renders MarketDataViews once the shim is available', () => {
			window.wp = {
				dataviews: { filterSortAndPaginate: jest.fn() },
			};

			render( <MarketsDashboard /> );

			expect(
				screen.getByText( 'MarketDataViews placeholder' )
			).toBeInTheDocument();
			expect(
				screen.queryByRole( 'status', { name: 'Loading…' } )
			).not.toBeInTheDocument();
		} );
	} );

	describe( 'Shipping rate description', () => {
		test( 'renders the "automatic" description', () => {
			mockShippingRate( SHIPPING_RATE_OPTION.AUTOMATIC );
			const { container } = render( <MarketsDashboard /> );

			expect(
				container.querySelector( '.gla-markets-header__description' )
					.textContent
			).toBe(
				'Shipping rates are synced from your WooCommerce settings.'
			);
		} );

		test( 'renders the "flat" description', () => {
			mockShippingRate( SHIPPING_RATE_OPTION.FLAT );
			render( <MarketsDashboard /> );

			expect(
				screen.getByText(
					'Shipping rates are manually configured per market.'
				)
			).toBeInTheDocument();
		} );

		test( 'renders the "manual" description', () => {
			mockShippingRate( SHIPPING_RATE_OPTION.MANUAL );
			const { container } = render( <MarketsDashboard /> );

			expect(
				container.querySelector( '.gla-markets-header__description' )
					.textContent
			).toContain( 'Shipping is managed in Google Merchant Center' );
		} );

		test( 'renders no description when settings have not resolved', () => {
			const { container } = render( <MarketsDashboard /> );

			expect(
				container.querySelector( '.gla-markets-header__description' )
			).not.toBeInTheDocument();
		} );
	} );
} );
