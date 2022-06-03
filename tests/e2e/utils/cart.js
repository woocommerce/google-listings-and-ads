/**
 * Helper functions for handling the cart.
 */

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
