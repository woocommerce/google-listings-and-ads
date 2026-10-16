/**
 * External dependencies
 */
import '@testing-library/jest-dom';
import { render } from '@testing-library/react';

/**
 * Internal dependencies
 */
import { useAdaptiveFormContext } from '~/components/adaptive-form';
import ShippingNotice from '.';

jest.mock( '~/components/adaptive-form', () => ( {
	useAdaptiveFormContext: jest
		.fn()
		.mockName( 'useAdaptiveFormContext' )
		.mockImplementation( () => ( { values: {} } ) ),
} ) );

describe( 'ShippingNotice', () => {
	beforeEach( () => {
		global.glaData.isMultiLingualStore = false;
		useAdaptiveFormContext.mockImplementation( () => ( { values: {} } ) );
	} );

	afterEach( () => {
		delete global.glaData.isMultiLingualStore;
	} );

	test( 'renders for the primary market on a non-multilingual store', () => {
		useAdaptiveFormContext.mockImplementation( () => ( {
			values: { id: 'primary' },
		} ) );

		render( <ShippingNotice /> );

		const notice = document.querySelector( '.gla-shipping-info-notice' );
		expect( notice ).toBeInTheDocument();
		expect( notice ).toHaveTextContent(
			'Shipping is managed in Google Merchant Center. Configure shipping rates and times for each currency in your Merchant Center account.'
		);
	} );

	test( 'renders for any market on a multilingual store', () => {
		global.glaData.isMultiLingualStore = true;

		render( <ShippingNotice /> );

		expect(
			document.querySelector( '.gla-shipping-info-notice' )
		).toBeInTheDocument();
	} );

	test( 'renders null for a non-primary market on a non-multilingual store', () => {
		useAdaptiveFormContext.mockImplementation( () => ( {
			values: { id: 'US' },
		} ) );

		render( <ShippingNotice /> );

		expect(
			document.querySelector( '.gla-shipping-info-notice' )
		).not.toBeInTheDocument();
	} );
} );
