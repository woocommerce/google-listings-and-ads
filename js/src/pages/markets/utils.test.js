/**
 * External dependencies
 */
import '@testing-library/jest-dom';
import { render } from '@testing-library/react';

/**
 * Internal dependencies
 */
import { getShippingRateLabel } from './utils';
import {
	SHIPPING_RATE_OPTION,
	WC_SHIPPING_SETTINGS_URL,
	GOOGLE_MERCHANT_CENTER_URL,
} from './constants';

const renderLabel = ( shippingRate ) =>
	render( <div>{ getShippingRateLabel( shippingRate ) }</div> );

describe( 'getShippingRateLabel', () => {
	test( 'returns the WooCommerce-synced label for the "automatic" option', () => {
		const { container, getByRole } = renderLabel(
			SHIPPING_RATE_OPTION.AUTOMATIC
		);

		expect( container.textContent ).toBe(
			'Shipping rates are synced from your WooCommerce settings.'
		);
		expect(
			getByRole( 'link', { name: 'WooCommerce settings' } )
		).toHaveAttribute( 'href', WC_SHIPPING_SETTINGS_URL );
	} );

	test( 'returns the per-market label (no link) for the "flat" option', () => {
		const { container, queryByRole } = renderLabel(
			SHIPPING_RATE_OPTION.FLAT
		);

		expect( container.textContent ).toBe(
			'Shipping rates are manually configured per market.'
		);
		expect( queryByRole( 'link' ) ).not.toBeInTheDocument();
	} );

	test( 'returns the Google-Merchant-Center label for the "manual" option', () => {
		const { container, getByRole } = renderLabel(
			SHIPPING_RATE_OPTION.MANUAL
		);

		expect( container.textContent ).toContain(
			'Shipping is managed in Google Merchant Center'
		);
		expect(
			getByRole( 'link', { name: /Google Merchant Center/ } )
		).toHaveAttribute( 'href', GOOGLE_MERCHANT_CENTER_URL );
	} );

	test( 'returns null for an unknown / undefined value so callers can render a skeleton', () => {
		expect( getShippingRateLabel( undefined ) ).toBeNull();
		expect( getShippingRateLabel( 'not-a-real-option' ) ).toBeNull();
	} );
} );
