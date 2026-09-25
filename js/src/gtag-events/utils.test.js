/**
 * Internal dependencies
 */
import {
	getCartItemObject,
	getGa4ItemObject,
	getPriceObject,
	getProductObject,
	mergeProductCategory,
	pushAddToCartDataLayerEvent,
	retrievedVariation,
	trackAddToCartEvent,
	trackEvent,
} from './utils';

describe( 'gtag-events utils', () => {
	beforeEach( () => {
		window.gtag = jest.fn();
		window.dataLayer = [];
		window.glaGtagData = {
			currency_minor_unit: 2,
			currency_code: 'USD',
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

	it( 'track add to cart event also pushes a parallel GA4 event to the data layer', () => {
		const product = {
			id: 1234,
			name: 'Test name',
			categories: [ { name: 'One' } ],
			prices: {
				price: 1012,
				currency_minor_unit: 2,
			},
		};
		trackAddToCartEvent( product, 3 );
		expect( window.dataLayer ).toContainEqual( {
			event: 'add_to_cart',
			ecommerce: {
				currency: 'USD',
				value: 30.36,
				items: [
					{
						item_id: 'gla_1234',
						item_name: 'Test name',
						item_category: 'One',
						price: 10.12,
						quantity: 3,
					},
				],
			},
		} );
	} );

	it( 'track add to cart event still reaches the data layer when gtag.js never loaded (Tag Manager connected, no Ads conversion action)', () => {
		window.gtag = undefined;
		const product = {
			id: 1234,
			name: 'Test name',
			categories: [ { name: 'One' } ],
			prices: {
				price: 1012,
				currency_minor_unit: 2,
			},
		};

		expect( () => trackAddToCartEvent( product, 3 ) ).not.toThrow();

		expect( window.dataLayer ).toContainEqual( {
			event: 'add_to_cart',
			ecommerce: {
				currency: 'USD',
				value: 30.36,
				items: [
					{
						item_id: 'gla_1234',
						item_name: 'Test name',
						item_category: 'One',
						price: 10.12,
						quantity: 3,
					},
				],
			},
		} );
	} );

	it( 'push add to cart data layer event - no price available', () => {
		const product = { id: 3456 };
		pushAddToCartDataLayerEvent( product );
		expect( window.dataLayer ).toContainEqual( {
			event: 'add_to_cart',
			ecommerce: {
				currency: 'USD',
				value: undefined,
				items: [
					{
						item_id: 'gla_3456',
						quantity: 1,
					},
				],
			},
		} );
	} );

	it( 'push add to cart data layer event - a zero price is not dropped like a missing one', () => {
		const product = {
			id: 3456,
			prices: {
				price: 0,
				currency_minor_unit: 2,
			},
		};
		pushAddToCartDataLayerEvent( product );
		expect( window.dataLayer ).toContainEqual( {
			event: 'add_to_cart',
			ecommerce: {
				currency: 'USD',
				value: 0,
				items: [
					{
						item_id: 'gla_3456',
						price: 0,
						quantity: 1,
					},
				],
			},
		} );
	} );

	it( 'formatted GA4 item object', () => {
		const product = {
			id: 1234,
			name: 'Test name',
			categories: [ { name: 'One' }, { name: 'Two' } ],
			prices: {
				price: 9999,
				currency_minor_unit: 2,
			},
		};
		expect( getGa4ItemObject( product, 2 ) ).toEqual( {
			item_id: 'gla_1234',
			item_name: 'Test name',
			item_category: 'One',
			price: 99.99,
			quantity: 2,
		} );
	} );

	it( 'formatted GA4 item object - no additional details', () => {
		const product = { id: 1234 };
		expect( getGa4ItemObject( product, 2 ) ).toEqual( {
			item_id: 'gla_1234',
			quantity: 2,
		} );
	} );

	it( 'formatted GA4 item object - a zero price is not dropped like a missing one', () => {
		const product = {
			id: 1234,
			prices: {
				price: 0,
				currency_minor_unit: 2,
			},
		};
		expect( getGa4ItemObject( product, 2 ) ).toEqual( {
			item_id: 'gla_1234',
			price: 0,
			quantity: 2,
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

	it( 'formatted item object - a zero price is not dropped like a missing one', () => {
		const product = {
			id: 1234,
			prices: {
				price: 0,
				currency_minor_unit: 2,
			},
		};
		expect( getCartItemObject( product, 2 ) ).toEqual( {
			id: 'gla_1234',
			price: 0,
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

	it( 'formatted product object - includes category when known', () => {
		window.glaGtagData.products[ 4321 ] = {
			name: 'Test Name',
			price: 10.12,
			category: 'Test Category',
		};

		expect( getProductObject( { id: 4321 } ) ).toEqual( {
			id: 4321,
			name: 'Test Name',
			prices: {
				price: 1012,
				currency_minor_unit: 2,
			},
			categories: [ { name: 'Test Category' } ],
		} );
	} );

	it( 'merges the known category into a product missing one, e.g. from a block add-to-cart payload', () => {
		window.glaGtagData.products[ 4321 ] = {
			name: 'Test Name',
			price: 10.12,
			category: 'Test Category',
		};

		expect(
			mergeProductCategory( { id: 4321, name: 'Block Name' } )
		).toEqual( {
			id: 4321,
			name: 'Block Name',
			categories: [ { name: 'Test Category' } ],
		} );
	} );

	it( 'does not overwrite a category the product already carries', () => {
		window.glaGtagData.products[ 4321 ] = {
			category: 'PHP Category',
		};

		expect(
			mergeProductCategory( {
				id: 4321,
				categories: [ { name: 'Block Category' } ],
			} )
		).toEqual( {
			id: 4321,
			categories: [ { name: 'Block Category' } ],
		} );
	} );

	it( 'leaves the product untouched when no category is known', () => {
		expect( mergeProductCategory( { id: 9999 } ) ).toEqual( {
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

	it( 'carries the parent product category forward onto the selected variation', () => {
		window.glaGtagData.products[ 1111 ] = {
			name: 'Parent Product',
			price: 39.99,
			category: 'Test Category',
		};

		retrievedVariation(
			{
				variation_id: 5678,
				display_name: 'Test Variation Name',
				display_price: 34.56,
			},
			1111
		);

		expect( window.glaGtagData.products[ 5678 ] ).toEqual( {
			name: 'Test Variation Name',
			price: 34.56,
			category: 'Test Category',
		} );
	} );

	it( 'leaves variable product data without a category when the parent has none known', () => {
		retrievedVariation(
			{
				variation_id: 5678,
				display_name: 'Test Variation Name',
				display_price: 34.56,
			},
			9999
		);

		expect( window.glaGtagData.products[ 5678 ] ).toEqual( {
			name: 'Test Variation Name',
			price: 34.56,
		} );
	} );
} );
