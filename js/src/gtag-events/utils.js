/**
 * Internal dependencies
 */
import { SEND_TO_GROUP } from './constants';

/* global glaGtagData */

/**
 * Track an event using the global gtag function.
 *
 * @param {string} eventName
 * @param {Object} eventParams
 * @throws Will throw an error if the global gtag function is not available.
 */
export const trackEvent = ( eventName, eventParams ) => {
	if ( typeof gtag !== 'function' ) {
		throw new Error( 'Function gtag not implemented.' );
	}

	window.gtag( 'event', eventName, {
		send_to: SEND_TO_GROUP,
		...eventParams,
	} );
};

/**
 * Track an add_to_cart event.
 *
 * @param {Product} product
 * @param {number} quantity
 */
export const trackAddToCartEvent = ( product, quantity = 1 ) => {
	trackEvent( 'add_to_cart', {
		ecomm_pagetype: 'cart',
		event_category: 'ecommerce',
		items: [ getCartItemObject( product, quantity ) ],
	} );
};

/**
 * Formats data into a cart Item object.
 *
 * @param {Product} product
 * @param {number} quantity
 * @return {Item} Item object.
 */
export const getCartItemObject = ( product, quantity ) => {
	const item = {
		id: 'gla_' + product.id,
		quantity,
		google_business_vertical: 'retail',
	};

	if ( product.name ) {
		item.name = product.name;
	}

	if ( product?.categories?.length ) {
		item.category = product.categories[ 0 ].name;
	}

	if ( product?.prices?.price ) {
		item.price =
			parseInt( product.prices.price, 10 ) /
			10 ** product.prices.currency_minor_unit;
	}

	return item;
};

/**
 * Formats a regular price into a price object.
 *
 * @param {number} price
 * @return {ProductPrices} Price object.
 */
export const getPriceObject = ( price ) => {
	return {
		price: price * 10 ** glaGtagData.currency_minor_unit,
		currency_minor_unit: glaGtagData.currency_minor_unit,
	};
};

/**
 * Formats a product object to include name and price from global data.
 *
 * @param {Product} product
 * @return {Product} Product object with optional fields added.
 */
export const getProductObject = ( product ) => {
	if ( glaGtagData.products[ product.id ] ) {
		product.name = glaGtagData.products[ product.id ].name;
		product.prices = getPriceObject(
			glaGtagData.products[ product.id ].price
		);
	}
	return product;
};

/**
 * Updates product data with the retrieved variation.
 *
 * @param {Variation} variation
 */
export const retrievedVariation = ( variation ) => {
	if ( ! variation?.variation_id ) {
		return;
	}

	glaGtagData.products[ variation.variation_id ] = {
		name: variation.display_name,
		price: variation.display_price,
	};
};

/**
 * Product data to be included in tracking event.
 *
 * @typedef {Object} Product
 * @property {number} id              ID number.
 * @property {string} [name]          Name to display on the frontend.
 * @property {Array} [categories]     List of product categories.
 * @property {ProductPrices} [prices] Price data.
 */

/**
 * Product prices.
 *
 * @typedef {Object} ProductPrices
 * @property {number} price               Price in the smallest common currency unit.
 * @property {number} currency_minor_unit The precision (decimal places).
 */

/**
 * Item data to include in a tracked event.
 *
 * @typedef {Object} Item
 * @property {string} id                       ID number including the `gla_` prefix.
 * @property {number} [quantity]               Quantity of this item.
 * @property {string} [name]                   Product name.
 * @property {string} [category]               First product category name.
 * @property {number} [price]                  Price as a decimal number.
 * @property {string} google_business_vertical Set to `retail`.
 */

/**
 * Variation data, sent when a variation has been selected to add to cart.
 *
 * @typedef {Object} Variation
 * @property {number} variation_id    ID number.
 * @property {string} [display_name]  Name to display on the frontend.
 * @property {number} [display_price] Price value to display on the frontend.
 */
