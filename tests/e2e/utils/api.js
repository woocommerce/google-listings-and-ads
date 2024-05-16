/**
 * Helper functions for requests sent through the REST API.
 */

/**
 * External dependencies
 */
const axios = require( 'axios' ).default;

/**
 * Internal dependencies
 */
const config = require( '../config/default.json' );

export function api( version ) {
	const token = Buffer.from(
		`${ config.users.admin.username }:${ config.users.admin.password }`,
		'utf8'
	).toString( 'base64' );

	return axios.create( {
		baseURL: `${ config.url }wp-json/${ version ?? 'wc/v3' }/`,
		headers: {
			'Content-Type': 'application/json',
			Authorization: `Basic ${ token }`,
		},
	} );
}

export function apiWP() {
	return api( 'wp/v2' );
}

/**
 * Creates a simple product.
 *
 * @return {Promise<number>} Product ID of the created product.
 */
export async function createSimpleProduct() {
	const product = config.products.simple;

	return await api()
		.post( 'products', product )
		.then( ( response ) => response.data.id );
}

/**
 * Creates a variable product.
 *
 * @return {Promise<number>} Product ID of the created product.
 */
export async function createVariableProduct() {
	const variableProduct = config.products.variable;

	return await api()
		.post( 'products', variableProduct )
		.then( ( response ) => response.data.id );
}

/**
 * Creates variation products.
 *
 * @param {number|string} productId The product ID to be associated with the variation products to be created.
 *
 * @return {Promise<number[]>} Product IDs of the created products.
 */
export async function createVariationProducts( productId ) {
	const variationProducts = config.products.variations;

	return await api()
		.post( `products/${ productId }/variations/batch`, {
			create: variationProducts,
		} )
		.then( ( response ) => response.data.create.map( ( { id } ) => id ) );
}

/**
 * Creates a variable product with 3 variation products.
 *
 * @return {Promise<number>} Product ID of the created variable product.
 */
export async function createVariableWithVariationProducts() {
	const variableId = await createVariableProduct();
	await createVariationProducts( variableId );
	return variableId;
}

/**
 * Set Test Conversion ID.
 */
export async function setConversionID() {
	await api().post( 'gla-test/conversion-id' );
}

/**
 * Clear Test Conversion ID.
 */
export async function clearConversionID() {
	await api().delete( 'gla-test/conversion-id' );
}

/**
 * Set Onboarded Merchant.
 */
export async function setOnboardedMerchant() {
	await api().post( 'gla-test/onboarded-merchant' );
}

/**
 * Clear Onboarded Merchant.
 */
export async function clearOnboardedMerchant() {
	await api().delete( 'gla-test/onboarded-merchant' );
}
