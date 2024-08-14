/**
 * Helper functions for handling the cart.
 *
 * @typedef { import( '@playwright/test' ).Page } Page
 */

/**
 * External dependencies
 */
const { expect } = require( '@playwright/test' );

/**
 * Internal dependencies
 */
import { LOAD_STATE } from './constants';
const config = require( '../config/default.json' );

/**
 * Adds a single product to the cart.
 *
 * @param {Page} page
 * @param {number} productID
 */
export async function singleProductAddToCart( page, productID ) {
	await page.goto( `?p=${ productID }` );

	const addToCart = '.single_add_to_cart_button';
	await page.locator( addToCart ).first().click();
	await expect(
		page.getByText( 'has been added to your cart' )
	).toBeVisible();

	// Wait till all tracking event request have been sent after page reloaded.
	await page.waitForLoadState( LOAD_STATE.DOM_CONTENT_LOADED );
}

/**
 * Adds a related single product to the cart.
 *
 * @param {Page} page
 * @return {number} Product ID of the added product.
 */
export async function relatedProductAddToCart( page ) {
	const addToCart = ( await page.locator( '.related.products' ).isVisible() )
		? '.related.products .add_to_cart_button.product_type_simple'
		: '.wp-block-woocommerce-related-products .add_to_cart_button.product_type_simple';

	const addToCartButton = await page.locator( addToCart ).first();
	await addToCartButton.click();
	await expect( addToCartButton.getByText( '1 in cart' ) ).toBeVisible();
	return await page.$eval( addToCart, ( el ) => el.dataset.product_id );
}

/**
 * Add a product to the cart from a block shop page.
 *
 * @param {Page} page
 */
export async function blockProductAddToCart( page ) {
	const addToCart =
		'.wp-block-button__link.add_to_cart_button:not([disabled])';

	await page.locator( addToCart ).first().click();
	await expect( page.locator( addToCart ).first() ).toHaveClass( /added/ );
}

/**
 * Perform checkout steps to purchase a product.
 *
 * @param {Page} page
 */
export async function checkout( page ) {
	const user = config.addresses.customer.billing;

	await page.goto( 'checkout' );

	if ( await page.locator( '#billing_first_name' ).isVisible() ) {
		await page.locator( '#billing_first_name' ).fill( user.firstname );
		await page.locator( '#billing_last_name' ).fill( user.lastname );
		await page
			.locator( '#billing_address_1' )
			.fill( user.addressfirstline );
		await page.locator( '#billing_city' ).fill( user.city );
		await page.locator( '#billing_state' ).selectOption( user.state );
		await page.locator( '#billing_postcode' ).fill( user.postcode );
		await page.locator( '#billing_phone' ).fill( user.phone );
		await page.locator( '#billing_email' ).fill( user.email );

		await page.locator( 'text=Cash on delivery' ).click();
		await expect( page.locator( 'div.payment_method_cod' ) ).toBeVisible();
	} else {
		await page.getByLabel( 'Email address' ).fill( user.email );
		await page.getByLabel( 'First name' ).fill( user.firstname );
		await page.getByLabel( 'Last name' ).fill( user.lastname );
		await page
			.getByLabel( 'Address', { exact: true } )
			.fill( user.addressfirstline );
		await page.getByLabel( 'City' ).fill( user.city );
		await page.getByLabel( 'ZIP Code' ).fill( user.postcode );
		await page.locator( '#billing-state' ).selectOption( user.statename );
	}

	//TODO: See if there's an alternative method to click the button without relying on waitForTimeout.
	await page.waitForTimeout( 3000 );

	await page.locator( 'text=Place order' ).click();

	await expect(
		page.locator( '.wc-block-order-confirmation-status' )
	).toContainText( 'order has been received' );
}
