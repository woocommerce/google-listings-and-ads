/**
 * External dependencies
 */
import {
	createSimpleProduct,
	SHOP_PAGE, // eslint-disable-line import/named
	shopper, // eslint-disable-line import/named
	uiUnblocked,
	withRestApi, // eslint-disable-line import/named
} from '@woocommerce/e2e-utils';

/**
 * Internal dependencies
 */
import {
	clearConversionID,
	saveConversionID,
} from '../../utils/connection-test-page';
import { getEventData, trackGtagEvent } from '../../utils/track-event';
import { emptyCart, relatedProductAddToCart } from '../../utils/cart';

const config = require( 'config' ); // eslint-disable-line import/no-extraneous-dependencies
const productPrice = config.has( 'products.simple.price' )
	? config.get( 'products.simple.price' )
	: '9.99';

let simpleProductID;

describe( 'GTag events', () => {
	beforeAll( async () => {
		await saveConversionID();
		simpleProductID = await createSimpleProduct();

		// Enable COD payment method
		await withRestApi.updatePaymentGateway(
			'cod',
			{ enabled: true },
			false
		);
	} );

	afterAll( async () => {
		await clearConversionID();
	} );

	it( 'Global GTag snippet appears on a frontend page', async () => {
		await shopper.goToShop();

		await expect(
			page.$$eval( 'head', ( elements ) =>
				elements.some( ( el ) =>
					el.innerHTML.includes( 'Global site tag (gtag.js)' )
				)
			)
		).resolves.toBeTruthy();
	} );

	it( 'Page view event is sent on a frontend page', async () => {
		const event = trackGtagEvent( 'page_view' );

		await shopper.goToShop();
		await expect( event ).resolves.toBeTruthy();
	} );

	it( 'View item event is sent on a single product page', async () => {
		const event = trackGtagEvent( 'view_item' );

		await shopper.goToProduct( simpleProductID );

		await event.then( ( request ) => {
			const data = getEventData( request );
			expect( data.id ).toEqual( 'gla_' + simpleProductID );
			expect( data.ecomm_pagetype ).toEqual( 'product' );
			expect( data.google_business_vertical ).toEqual( 'retail' );
		} );
	} );

	it( 'Add to cart event is sent on a single product page', async () => {
		const event = trackGtagEvent( 'add_to_cart' );

		await shopper.goToProduct( simpleProductID );
		await page.waitForSelector( 'form.cart' );
		await shopper.addToCart();

		await event.then( ( request ) => {
			const data = getEventData( request );
			expect( data.id ).toEqual( 'gla_' + simpleProductID );
			expect( data.ecomm_pagetype ).toEqual( 'cart' );
			expect( data.event_category ).toEqual( 'ecommerce' );
			expect( data.google_business_vertical ).toEqual( 'retail' );
		} );
	} );

	it( 'Add to cart event is sent from related product on single product page', async () => {
		await createSimpleProduct(); // Create an additional product for related to show up.
		const event = trackGtagEvent( 'add_to_cart' );

		await shopper.goToProduct( simpleProductID );
		const relatedProductID = await relatedProductAddToCart();

		await event.then( ( request ) => {
			const data = getEventData( request );
			expect( data.id ).toEqual( 'gla_' + relatedProductID );
			expect( data.ecomm_pagetype ).toEqual( 'cart' );
			expect( data.event_category ).toEqual( 'ecommerce' );
			expect( data.google_business_vertical ).toEqual( 'retail' );
		} );
	} );

	it( 'Add to cart event is sent from the shop page', async () => {
		const event = trackGtagEvent( 'add_to_cart' );

		// Go to shop page (newest first)
		await page.goto( SHOP_PAGE + '?orderby=date', {
			waitUntil: 'networkidle0',
		} );
		await shopper.addToCartFromShopPage( simpleProductID );

		await event.then( ( request ) => {
			const data = getEventData( request );
			expect( data.id ).toEqual( 'gla_' + simpleProductID );
			expect( data.ecomm_pagetype ).toEqual( 'cart' );
			expect( data.event_category ).toEqual( 'ecommerce' );
			expect( data.google_business_vertical ).toEqual( 'retail' );
		} );
	} );

	it( 'Cart page view event is sent from the cart page', async () => {
		const event = trackGtagEvent( 'page_view' );
		await shopper.goToCart();

		await event.then( ( request ) => {
			const data = getEventData( request );
			expect( data.ecomm_pagetype ).toEqual( 'cart' );
			expect( data.google_business_vertical ).toEqual( 'retail' );
		} );
	} );

	it( 'Conversion event is sent on order complete page', async () => {
		await emptyCart();
		await shopper.goToProduct( simpleProductID );
		await page.waitForSelector( 'form.cart' );
		await shopper.addToCart();

		await shopper.goToCheckout();
		await shopper.fillBillingDetails(
			config.get( 'addresses.customer.billing' )
		);
		await uiUnblocked();

		const event = trackGtagEvent( 'conversion' );
		await shopper.placeOrder();

		await event.then( ( request ) => {
			const data = getEventData( request );
			expect( data.value ).toEqual( productPrice );
			expect( data.currency_code ).toEqual( 'USD' );
		} );
	} );

	it( 'Purchase event is sent on order complete page', async () => {
		await emptyCart();
		await shopper.goToProduct( simpleProductID );
		await page.waitForSelector( 'form.cart' );
		await shopper.addToCart();

		await shopper.goToCheckout();
		await shopper.fillBillingDetails(
			config.get( 'addresses.customer.billing' )
		);
		await uiUnblocked();

		const event = trackGtagEvent( 'purchase' );
		await shopper.placeOrder();

		await event.then( ( request ) => {
			const data = getEventData( request );
			expect( data.value ).toEqual( productPrice );
			expect( data.ecomm_pagetype ).toEqual( 'purchase' );
			expect( data.currency_code ).toEqual( 'USD' );
			expect( data.country ).toEqual( 'US' );
		} );
	} );
} );
