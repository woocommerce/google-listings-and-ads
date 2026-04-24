/**
 * External dependencies
 */
import '@testing-library/jest-dom';
import { render, screen } from '@testing-library/react';

/**
 * Internal dependencies
 */
import MarketsHeader from './';
import { SHIPPING_RATE_OPTION } from '../../constants';

jest.mock( '../add-market', () =>
	jest.fn().mockReturnValue( <div data-testid="add-market" /> )
);

describe( 'MarketsHeader', () => {
	test( 'renders the "Markets" heading and the AddMarket CTA', () => {
		render( <MarketsHeader /> );

		expect(
			screen.getByRole( 'heading', { name: 'Markets', level: 1 } )
		).toBeInTheDocument();
		expect( screen.getByTestId( 'add-market' ) ).toBeInTheDocument();
	} );

	test( 'omits the description when shippingRate is undefined', () => {
		const { container } = render( <MarketsHeader /> );

		expect(
			container.querySelector( '.gla-markets-header__description' )
		).not.toBeInTheDocument();
	} );

	test( 'renders the "automatic" description when shippingRate is "automatic"', () => {
		const { container } = render(
			<MarketsHeader shippingRate={ SHIPPING_RATE_OPTION.AUTOMATIC } />
		);

		expect(
			container.querySelector( '.gla-markets-header__description' )
				.textContent
		).toBe( 'Shipping rates are synced from your WooCommerce settings.' );
		expect(
			screen.getByRole( 'link', { name: 'WooCommerce settings' } )
		).toBeInTheDocument();
	} );

	test( 'renders the "flat" description when shippingRate is "flat"', () => {
		render( <MarketsHeader shippingRate={ SHIPPING_RATE_OPTION.FLAT } /> );

		expect(
			screen.getByText(
				'Shipping rates are manually configured per market.'
			)
		).toBeInTheDocument();
	} );

	test( 'renders the "manual" description when shippingRate is "manual"', () => {
		const { container } = render(
			<MarketsHeader shippingRate={ SHIPPING_RATE_OPTION.MANUAL } />
		);

		expect(
			container.querySelector( '.gla-markets-header__description' )
				.textContent
		).toContain( 'Shipping is managed in Google Merchant Center' );
		expect(
			screen.getByRole( 'link', { name: /Google Merchant Center/ } )
		).toBeInTheDocument();
	} );
} );
