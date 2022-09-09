/**
 * Helper functions for handling the cart.
 */

/**
 * External dependencies
 */
import {
	SHOP_CART_PAGE, // eslint-disable-line import/named
	uiUnblocked,
} from '@woocommerce/e2e-utils';

/* global page */

/**
 * Adds a related product to the cart.
 *
 * @return {number} Product ID of the added product.
 */
export async function relatedProductAddToCart() {
	const addToCart = '.related.products .add_to_cart_button';

	await page.click( addToCart );
	await expect( page ).toMatchElement( addToCart + '.added' );

	return await page.$eval( addToCart, ( el ) => {
		return el.getAttribute( 'data-product_id' );
	} );
}

/**
 * Add a product to the cart from a block shop page.
 */
export async function blockProductAddToCart() {
	const addToCart =
		'.wp-block-button__link.add_to_cart_button:not([disabled])';

	await page.waitForSelector( addToCart );
	await page.click( addToCart );
	await expect( page ).toMatchElement( addToCart + '.added' );
}

/**
 * Empty the cart.
 *
 * Needed until this issue is included in the @woocommerce/e2e-utils package:
 * https://github.com/woocommerce/woocommerce/pull/31977
 */
export async function emptyCart() {
	await page.goto( SHOP_CART_PAGE, {
		waitUntil: 'networkidle0',
	} );

	// Remove products if they exist
	if ( ( await page.$( '.remove' ) ) !== null ) {
		let products = await page.$$( '.remove' );
		while ( products && products.length > 0 ) {
			await page.click( '.remove' );
			await uiUnblocked();
			products = await page.$$( '.remove' );
		}
	}

	await page.waitForSelector( '.woocommerce-info' );
	await expect( page ).toMatchElement( '.woocommerce-info', {
		text: 'Your cart is currently empty.',
	} );
}
