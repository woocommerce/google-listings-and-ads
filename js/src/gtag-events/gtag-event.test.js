/**
 * Internal dependencies
 */
import {
	getCartItemObject,
	getPriceObject,
	getProductObject,
	retrievedVariation,
	trackAddToCartEvent,
	trackEvent,
} from './utils';

describe( 'gtagEvents', () => {
	beforeEach( () => {
		window.gtag = jest.fn();
		window.glaGtagData = {
			currency_minor_unit: 2,
			products: [],
		};

		window.glaGtagData.products[ 1234 ] = {
			name: 'Test Name',
			price: 10.12,
		};
	} );

	it( 'gtag function not implemented', () => {
		window.gtag = undefined;
		expect( trackEvent ).toThrow( 'Function gtag not implemented.' );
	} );

	it( 'track event', () => {
		trackEvent( 'event_name' );
		expect( window.gtag ).toHaveBeenCalledWith( 'event', 'event_name', {
			send_to: 'GLA',
		} );
	} );

	it( 'track add to cart event', () => {
		const product = {
			id: 1234,
			name: 'Test name',
			prices: {
				price: 1012,
				currency_minor_unit: 2,
			},
		};
		trackAddToCartEvent( product, 3 );
		expect( window.gtag ).toHaveBeenCalledWith( 'event', 'add_to_cart', {
			ecomm_pagetype: 'cart',
			event_category: 'ecommerce',
			items: [
				{
					id: 'gla_1234',
					name: 'Test name',
					price: 10.12,
					quantity: 3,
					google_business_vertical: 'retail',
				},
			],
			send_to: 'GLA',
		} );
	} );

	it( 'track add to cart - default quantity', () => {
		const product = { id: 3456 };
		trackAddToCartEvent( product );
		expect( window.gtag ).toHaveBeenCalledWith( 'event', 'add_to_cart', {
			ecomm_pagetype: 'cart',
			event_category: 'ecommerce',
			items: [
				{
					id: 'gla_3456',
					quantity: 1,
					google_business_vertical: 'retail',
				},
			],
			send_to: 'GLA',
		} );
	} );

	it( 'formatted item object', () => {
		const product = {
			id: 1234,
			name: 'Test name',
			categories: [ { name: 'One' }, { name: 'Two' } ],
			prices: {
				price: 9999,
				currency_minor_unit: 2,
			},
		};
		expect( getCartItemObject( product, 2 ) ).toEqual( {
			id: 'gla_1234',
			name: 'Test name',
			category: 'One',
			price: 99.99,
			quantity: 2,
			google_business_vertical: 'retail',
		} );
	} );

	it( 'formatted item object - no additional details', () => {
		const product = { id: 1234 };
		expect( getCartItemObject( product, 2 ) ).toEqual( {
			id: 'gla_1234',
			quantity: 2,
			google_business_vertical: 'retail',
		} );
	} );

	it( 'formatted price object', () => {
		expect( getPriceObject( 10.12 ) ).toEqual( {
			price: 1012,
			currency_minor_unit: 2,
		} );
	} );

	it( 'formatted product object', () => {
		expect( getProductObject( { id: 1234 } ) ).toEqual( {
			id: 1234,
			name: 'Test Name',
			prices: {
				price: 1012,
				currency_minor_unit: 2,
			},
		} );
	} );

	it( 'formatted product object - no additional details', () => {
		expect( getProductObject( { id: 9999 } ) ).toEqual( {
			id: 9999,
		} );
	} );

	it( 'updated variable product data', () => {
		retrievedVariation( {
			variation_id: 5678,
			display_name: 'Test Variation Name',
			display_price: 34.56,
		} );
		expect( window.glaGtagData.products[ 5678 ] ).toEqual( {
			name: 'Test Variation Name',
			price: 34.56,
		} );
	} );
} );
