/**
 * Internal dependencies
 */
import { sendToGroup } from './constants';

/* global glaGtagData */

/**
 * Track an event using the global gtag function.
 *
 * @param {string} eventName
 * @param {Object} eventParams
 */
export const trackEvent = ( eventName, eventParams ) => {
	if ( typeof gtag !== 'function' ) {
		throw new Error( 'Function gtag not implemented.' );
	}
	// eslint-disable-next-line no-console
	console.log( `Tracking event ${ eventName }` );
	window.gtag( 'event', eventName, {
		send_to: sendToGroup,
		...eventParams,
	} );
};

/**
 * Track an add_to_cart event.
 *
 * @param {Object} product
 * @param {number} quantity
 */
export const trackAddToCartEvent = ( product, quantity = 1 ) => {
	trackEvent( 'add_to_cart', {
		ecomm_pagetype: 'cart',
		event_category: 'ecommerce',
		items: [ getItemObject( product, quantity ) ],
	} );
};

/**
 * Formats data into a cart Item object.
 *
 * @param {Object} product
 * @param {number} quantity
 * @return {Object} Item object.
 */
export const getItemObject = ( product, quantity ) => {
	const item = {
		id: 'gla_' + product.id,
		quantity,
		google_business_vertical: 'retail',
	};

	if ( product.name ) {
		item.name = product.name;
	}

	if ( 'categories' in product && product.categories.length ) {
		item.category = product.categories[ 0 ].name;
	}

	if ( product.prices && product.prices.price ) {
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
 * @return {Object} Price object.
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
 * @param {Object} product
 * @return {Object} Product object.
 */
export const getProductObject = ( product ) => {
	if ( product.id && glaGtagData.products[ product.id ] ) {
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
 * @param {Object} variation
 */
export const retrievedVariation = ( variation ) => {
	glaGtagData.products[ variation.variation_id ] = {
		name: variation.display_name,
		price: variation.display_price,
	};
};
