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
	enableEnhancedConversions,
	disableEnhancedConversions,
} from '../../utils/api';
import {
	blockProductAddToCart,
	checkout,
	relatedProductAddToCart,
	singleProductAddToCart,
} from '../../utils/customer';
import { createBlockShopPage } from '../../utils/block-page';
import {
	getEventData,
	trackGtagEvent,
	getDataLayerValue,
} from '../../utils/track-event';

const config = require( '../../config/default' );
const productPrice = config.products.simple.regular_price;

let simpleProductID;

test.describe( 'GTag events', () => {
	test.beforeAll( async () => {
		await setConversionID();
		await enableEnhancedConversions();
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
		const event = trackGtagEvent( page, 'add_to_cart' );

		await page.goto( `?p=${ simpleProductID }` );
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
		const addToCartButton = await page.locator( addToCart ).first();
		await addToCartButton.click();
		await expect( addToCartButton.getByText( '1 in cart' ) ).toBeVisible();

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

		const event = trackGtagEvent( page, 'page_view', 'cart' );
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

		const event = trackGtagEvent( page, 'conversion', 'checkout' );
		await checkout( page );

		await event.then( ( request ) => {
			const data = getEventData( request );
			expect( data.value ).toEqual( productPrice );
			expect( data.currency_code ).toEqual( 'USD' );
		} );
	} );

	test( 'Purchase event is sent on order complete page', async ( {
		page,
	} ) => {
		await singleProductAddToCart( page, simpleProductID );

		const event = trackGtagEvent( page, 'purchase', 'checkout' );
		await checkout( page );

		await event.then( ( request ) => {
			const data = getEventData( request );
			expect( data.value ).toEqual( productPrice );
			expect( data.ecomm_pagetype ).toEqual( 'purchase' );
			expect( data.currency_code ).toEqual( 'USD' );
			expect( data.country ).toEqual( 'US' );
		} );
	} );

	test( 'User data for enhanced conversions are not sent when not enabled', async ( {
		page,
	} ) => {
		await disableEnhancedConversions();
		await singleProductAddToCart( page, simpleProductID );

		await checkout( page );

		const dataConfig = await getDataLayerValue( page, {
			type: 'config',
			key: 'AW-123456',
		} );

		expect( dataConfig ).toBeDefined();
		expect( dataConfig.allow_enhanced_conversions ).toBeUndefined();
	} );

	test( 'User data for enhanced conversions is sent when enabled', async ( {
		page,
	} ) => {
		await enableEnhancedConversions();
		await singleProductAddToCart( page, simpleProductID );

		await checkout( page );

		const dataConfig = await getDataLayerValue( page, {
			type: 'config',
			key: 'AW-123456',
		} );

		const dataUserData = await getDataLayerValue( page, {
			type: 'set',
			key: 'user_data',
		} );

		expect( dataConfig.allow_enhanced_conversions ).toBeTruthy();
		expect( dataUserData.sha256_email_address ).toBeDefined();
	} );
} );
