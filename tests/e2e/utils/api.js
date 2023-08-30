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
 * @return {number} Product ID of the created product.
 */
export async function createSimpleProduct() {
	const product = config.products.simple;

	return await api()
		.post( 'products', {
			name: product.name,
			type: 'simple',
			regular_price: String( product.regularPrice ),
		} )
		.then( ( response ) => response.data.id );
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
