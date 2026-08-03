/**
 * External dependencies
 */
import '@testing-library/jest-dom';
import { render, screen } from '@testing-library/react';

/**
 * Internal dependencies
 */
import { SHIPPING_RATE_METHOD } from '~/constants';
import MarketsHeader from '.';

jest.mock( '../add-market-button', () =>
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

	test( 'renders a loading skeleton inside the description when shippingRate is undefined', () => {
		const { container } = render( <MarketsHeader /> );

		const description = container.querySelector(
			'.gla-markets-header__description'
		);
		const placeholder = container.querySelector(
			'.gla-markets-header__description-placeholder'
		);

		expect( description ).toBeInTheDocument();
		expect( description.textContent ).toBe( '' );
		expect( placeholder ).toBeInTheDocument();
		expect( placeholder ).toHaveAttribute( 'aria-busy', 'true' );
	} );

	test( 'renders the "automatic" description when shippingRate is "automatic"', () => {
		const { container } = render(
			<MarketsHeader shippingRate={ SHIPPING_RATE_METHOD.AUTOMATIC } />
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
		render( <MarketsHeader shippingRate={ SHIPPING_RATE_METHOD.FLAT } /> );

		expect(
			screen.getByText(
				'Shipping rates are manually configured per market.'
			)
		).toBeInTheDocument();
	} );

	test( 'renders the "manual" description when shippingRate is "manual"', () => {
		const { container } = render(
			<MarketsHeader shippingRate={ SHIPPING_RATE_METHOD.MANUAL } />
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
