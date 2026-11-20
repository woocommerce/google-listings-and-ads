/**
 * External dependencies
 */
import '@testing-library/jest-dom';
import { render, screen } from '@testing-library/react';

/**
 * Internal dependencies
 */
import { getShippingRateLabel } from './getShippingRateLabel';
import { SHIPPING_RATE_METHOD } from '~/constants';
import {
	GOOGLE_MERCHANT_CENTER_URL,
	WC_SHIPPING_SETTINGS_URL,
} from '../constants';

describe( 'getShippingRateLabel', () => {
	test( 'returns null for an unknown or undefined shipping rate', () => {
		expect( getShippingRateLabel( undefined ) ).toBeNull();
		expect( getShippingRateLabel( 'unknown' ) ).toBeNull();
	} );

	test( 'returns a plain string for FLAT shipping rate', () => {
		expect( getShippingRateLabel( SHIPPING_RATE_METHOD.FLAT ) ).toBe(
			'Shipping rates are manually configured per market.'
		);
	} );

	test( 'returns a JSX element linking to WooCommerce settings for AUTOMATIC', () => {
		const { container } = render(
			getShippingRateLabel( SHIPPING_RATE_METHOD.AUTOMATIC )
		);

		expect( container ).toHaveTextContent(
			'Shipping rates are synced from your WooCommerce settings.'
		);
		expect(
			screen.getByRole( 'link', { name: 'WooCommerce settings' } )
		).toHaveAttribute( 'href', WC_SHIPPING_SETTINGS_URL );
	} );

	test( 'returns a JSX element linking to Google Merchant Center for MANUAL', () => {
		const { container } = render(
			getShippingRateLabel( SHIPPING_RATE_METHOD.MANUAL )
		);

		expect( container ).toHaveTextContent(
			/Shipping is managed in Google Merchant Center/
		);
		expect(
			screen.getByRole( 'link', { name: /Google Merchant Center/ } )
		).toHaveAttribute( 'href', GOOGLE_MERCHANT_CENTER_URL );
	} );
} );
