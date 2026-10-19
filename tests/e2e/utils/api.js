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

/**
 * Set Ads Completed At.
 */
export async function setCompletedAdsSetup() {
	await api().post( 'gla-test/ads-completed' );
}

/**
 * Clear Ads Completed At.
 */
export async function clearCompletedAdsSetup() {
	await api().delete( 'gla-test/ads-completed' );
}

/**
 * Set MC Setup.
 */
export async function setCompleteMCSetup() {
	await api().post( 'gla-test/mc-completed' );
}

/**
 * Clear MC Setup.
 */
export async function clearCompleteMCSetup() {
	await api().delete( 'gla-test/mc-completed' );
}

/**
 * Set gla_install_version for Hiding GTIN
 */
export async function setVersionForHideGtin() {
	await api().post( 'gla-test/gtin-hidden' );
}

/**
 * Set gla_install_version for disabling GTIN
 */
export async function setVersionForDisabledGtin() {
	await api().post( 'gla-test/gtin-disabled' );
}

/**
 * Set Service Based Merchant.
 */
export async function setServiceBasedMerchant() {
	await api().post( 'gla-test/service-based-merchant' );
}

/**
 * Clear Service Based Merchant.
 */
export async function clearServiceBasedMerchant() {
	await api().delete( 'gla-test/service-based-merchant' );
}

/**
 * Set GCR Notifications Dismissed.
 */
export async function setGCRNotificationsDismissed() {
	await api().post( 'gla-test/gcr-notifications-dismissed' );
}

/**
 * Clear GCR Notifications Dismissed.
 */
export async function clearGCRNotificationsDismissed() {
	await api().delete( 'gla-test/gcr-notifications-dismissed' );
}

/**
 * Fetch the currently active notifications from the real notification system.
 *
 * @return {Promise<Array>} The active notifications.
 */
export async function getNotifications() {
	return await api( 'wc/gla' )
		.get( 'notifications' )
		.then( ( response ) => response.data.notifications );
}

/**
 * Set a fake connected Merchant Center account ID.
 */
export async function setMerchantId() {
	await api().post( 'gla-test/merchant-id' );
}

/**
 * Clear a previously set fake Merchant Center account ID.
 */
export async function clearMerchantId() {
	await api().delete( 'gla-test/merchant-id' );
}

/**
 * Save Merchant Center settings, including the Google Customer Reviews fields
 * (`gcr_collect_reviews_after_purchase`, `gcr_badge_widget_enabled`,
 * `gcr_badge_widget_position`), via the real settings REST route.
 *
 * @param {Object} payload Partial settings object to save.
 */
export async function saveMCSettings( payload ) {
	await api( 'wc/gla' ).post( 'mc/settings', payload );
}

/**
 * Seed a shipping time (in days) for a destination country, so
 * EstimatedDeliveryTimeResolver can resolve a delivery date for orders shipped there.
 *
 * @param {string} countryCode ISO 3166-1 alpha-2 country code.
 * @param {number} time        Shipping time in days.
 * @param {number} maxTime     Maximum shipping time in days.
 */
export async function setShippingTime( countryCode, time, maxTime ) {
	await api( 'wc/gla' ).post( 'mc/shipping/times', {
		country_code: countryCode,
		time,
		max_time: maxTime,
	} );
}

/**
 * Clear a previously seeded shipping time for a destination country.
 *
 * @param {string} countryCode ISO 3166-1 alpha-2 country code.
 */
export async function clearShippingTime( countryCode ) {
	await api( 'wc/gla' ).delete( `mc/shipping/times/${ countryCode }` );
}

/**
 * Fetch a WooCommerce order by ID.
 *
 * @param {number} orderId
 * @return {Promise<Object>} The order.
 */
export async function getOrder( orderId ) {
	return await api()
		.get( `orders/${ orderId }` )
		.then( ( response ) => response.data );
}
