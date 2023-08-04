/**
 * External dependencies
 */
const { test, expect } = require( '@playwright/test' );

/**
 * Internal dependencies
 */
import {
	createSimpleProduct,
	setConversionID,
	clearConversionID,
} from '../../utils/api';
import {
	blockProductAddToCart,
	checkout,
	relatedProductAddToCart,
	singleProductAddToCart,
} from '../../utils/customer';
import { createBlockShopPage } from '../../utils/block-page';
import { getEventData, trackGtagEvent } from '../../utils/track-event';

const config = require( '../../config/default' );
const productPrice = config.products.simple.regularPrice;

let simpleProductID;

test.describe( 'GTag events', () => {
	test.beforeAll( async () => {
		await setConversionID();
		simpleProductID = await createSimpleProduct();
	} );

	test.afterAll( async () => {
		await clearConversionID();
	} );

	test( 'Global GTag snippet appears on a frontend page', async ( {
		page,
	} ) => {
		await page.goto( 'shop' );

		await expect(
			page.$$eval( 'head', ( elements ) =>
				elements.some( ( el ) =>
					el.innerHTML.includes( 'Global site tag (gtag.js)' )
				)
			)
		).resolves.toBeTruthy();
	} );

	test( 'Page view event is sent on a frontend page', async ( { page } ) => {
		const event = trackGtagEvent( page, 'page_view' );

		await page.goto( 'shop' );
		await expect( event ).resolves.toBeTruthy();
	} );

	test( 'View item event is sent on a single product page', async ( {
		page,
	} ) => {
		const event = trackGtagEvent( page, 'view_item' );

		await page.goto( `?p=${ simpleProductID }` );

		await event.then( ( request ) => {
			const data = getEventData( request );
			expect( data.id ).toEqual( 'gla_' + simpleProductID );
			expect( data.ecomm_pagetype ).toEqual( 'product' );
			expect( data.google_business_vertical ).toEqual( 'retail' );
		} );
	} );

	test( 'Add to cart event is sent from a block shop page', async ( {
		page,
	} ) => {
		await createBlockShopPage();

		const event = trackGtagEvent( page, 'add_to_cart' );

		// Go to block shop page
		await page.goto( 'all-products-block' );
		await blockProductAddToCart( page );

		await event.then( ( request ) => {
			const data = getEventData( request );
			expect( data.id ).toEqual( 'gla_' + simpleProductID );
			expect( data.ecomm_pagetype ).toEqual( 'cart' );
			expect( data.event_category ).toEqual( 'ecommerce' );
			expect( data.google_business_vertical ).toEqual( 'retail' );
		} );
	} );

	test( 'Add to cart event is sent on a single product page', async ( {
		page,
	} ) => {
		const event = trackGtagEvent( page, 'add_to_cart' );

		await singleProductAddToCart( page, simpleProductID );

		await event.then( ( request ) => {
			const data = getEventData( request );
			expect( data.id ).toEqual( 'gla_' + simpleProductID );
			expect( data.ecomm_pagetype ).toEqual( 'cart' );
			expect( data.event_category ).toEqual( 'ecommerce' );
			expect( data.google_business_vertical ).toEqual( 'retail' );
		} );
	} );

	test( 'Add to cart event is sent from related product on single product page', async ( {
		page,
	} ) => {
		await createSimpleProduct(); // Create an additional product for related to show up.

		await page.goto( `?p=${ simpleProductID }` );
		await page.waitForLoadState( 'networkidle' );

		const event = trackGtagEvent( page, 'add_to_cart' );
		const relatedProductID = await relatedProductAddToCart( page );

		await event.then( ( request ) => {
			const data = getEventData( request );
			expect( data.id ).toEqual( 'gla_' + relatedProductID );
			expect( data.ecomm_pagetype ).toEqual( 'cart' );
			expect( data.event_category ).toEqual( 'ecommerce' );
			expect( data.google_business_vertical ).toEqual( 'retail' );
		} );
	} );

	test( 'Add to cart event is sent from the shop page', async ( {
		page,
	} ) => {
		const event = trackGtagEvent( page, 'add_to_cart' );

		// Go to shop page (newest first)
		await page.goto( 'shop?orderby=date' );
		const addToCart = `[data-product_id="${ simpleProductID }"]`;
		await page.locator( addToCart ).first().click();
		await expect( page.locator( addToCart ).first() ).toHaveClass(
			/added/
		);

		await event.then( ( request ) => {
			const data = getEventData( request );
			expect( data.id ).toEqual( 'gla_' + simpleProductID );
			expect( data.ecomm_pagetype ).toEqual( 'cart' );
			expect( data.event_category ).toEqual( 'ecommerce' );
			expect( data.google_business_vertical ).toEqual( 'retail' );
		} );
	} );

	test( 'Cart page view event is sent from the cart page', async ( {
		page,
	} ) => {
		await singleProductAddToCart( page, simpleProductID );

		const event = trackGtagEvent( page, 'page_view' );
		await page.goto( 'cart' );

		await event.then( ( request ) => {
			const data = getEventData( request );
			expect( data.ecomm_pagetype ).toEqual( 'cart' );
			expect( data.google_business_vertical ).toEqual( 'retail' );
		} );
	} );

	test( 'Conversion event is sent on order complete page', async ( {
		page,
	} ) => {
		await singleProductAddToCart( page, simpleProductID );

		const event = trackGtagEvent( page, 'conversion' );
		await checkout( page );

		await event.then( ( request ) => {
			const data = getEventData( request );
			expect( data.value ).toEqual( String( productPrice ) );
			expect( data.currency_code ).toEqual( 'USD' );
		} );
	} );

	test( 'Purchase event is sent on order complete page', async ( {
		page,
	} ) => {
		await singleProductAddToCart( page, simpleProductID );

		const event = trackGtagEvent( page, 'purchase' );
		await checkout( page );

		await event.then( ( request ) => {
			const data = getEventData( request );
			expect( data.value ).toEqual( String( productPrice ) );
			expect( data.ecomm_pagetype ).toEqual( 'purchase' );
			expect( data.currency_code ).toEqual( 'USD' );
			expect( data.country ).toEqual( 'US' );
		} );
	} );
} );
