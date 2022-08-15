/**
 * Helper functions for visiting a product page.
 */

/**
 * External dependencies
 */
import {
	shopper, // eslint-disable-line import/named
} from '@woocommerce/e2e-utils';

/* global page */

/**
 * Redirects to a single product page.
 * Waits till the cart form fully loads because shopper.goToProduct uses a
 * URL with product ID which leads to a redirect for pretty permalinks.
 *
 * @param {number} productID
 */
export async function goToSingleProduct( productID ) {
	await shopper.goToProduct( productID );
	await page.waitForSelector( 'body.single-product form.cart' );
}
