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
 * Derives the name/category/price fields shared by both the Ads-gtag item shape
 * (`getCartItemObject`) and the GA4-schema item shape (`getGa4ItemObject`), so the
 * derivation only needs to change in one place.
 *
 * @param {Product} product
 * @return {{name?: string, category?: string, price?: number}} Derived fields, present only when available on `product`.
 */
const deriveItemFields = ( product ) => {
	const fields = {};

	if ( product.name ) {
		fields.name = product.name;
	}

	if ( product?.categories?.length ) {
		fields.category = product.categories[ 0 ].name;
	}

	if (
		product?.prices?.price !== undefined &&
		product?.prices?.price !== null
	) {
		fields.price =
			parseInt( product.prices.price, 10 ) /
			10 ** product.prices.currency_minor_unit;
	}

	return fields;
};

/**
 * Formats data into a cart Item object.
 *
 * @param {Product} product
 * @param {number} quantity
 * @return {Item} Item object.
 */
export const getCartItemObject = ( product, quantity ) => {
	const { name, category, price } = deriveItemFields( product );
	const item = {
		id: 'gla_' + product.id,
		quantity,
		google_business_vertical: 'retail',
	};

	if ( name ) {
		item.name = name;
	}

	if ( category ) {
		item.category = category;
	}

	if ( price !== undefined ) {
		item.price = price;
	}

	return item;
};

/**
 * Formats data into a GA4-schema data layer item object. Uses GA4's own field names
 * (item_id/item_name/item_category), distinct from the Ads-gtag item shape in
 * `getCartItemObject`, since the two formats don't share a schema.
 *
 * @param {Product} product
 * @param {number} quantity
 * @return {Ga4Item} GA4-schema item object.
 */
export const getGa4ItemObject = ( product, quantity ) => {
	const { name, category, price } = deriveItemFields( product );
	const item = {
		item_id: 'gla_' + product.id,
		quantity,
	};

	if ( name ) {
		item.item_name = name;
	}

	if ( category ) {
		item.item_category = category;
	}

	if ( price !== undefined ) {
		item.price = price;
	}

	return item;
};

/**
 * Pushes a GA4-schema add_to_cart event to the GTM data layer, parallel to the existing
 * Ads-gtag `trackAddToCartEvent` below, so the merchant's own GTM tags can trigger on it.
 *
 * @param {Product} product
 * @param {number} quantity
 */
export const pushAddToCartDataLayerEvent = ( product, quantity = 1 ) => {
	const item = getGa4ItemObject( product, quantity );

	window.dataLayer = window.dataLayer || [];
	window.dataLayer.push( {
		event: 'add_to_cart',
		ecommerce: {
			currency: glaGtagData.currency_code,
			value:
				item.price !== undefined && item.price !== null
					? item.price * quantity
					: undefined,
			items: [ item ],
		},
	} );
};

/**
 * Track an add_to_cart event. The GTM data layer push always runs first, so it can't become
 * collateral damage if the Ads-gtag call fails for any reason. The `typeof gtag` guard below
 * stays regardless — it silences the expected, common case where Ads just isn't configured.
 *
 * @param {Product} product
 * @param {number} quantity
 */
export const trackAddToCartEvent = ( product, quantity = 1 ) => {
	pushAddToCartDataLayerEvent( product, quantity );

	if ( typeof gtag === 'function' ) {
		trackEvent( 'add_to_cart', {
			ecomm_pagetype: 'cart',
			event_category: 'ecommerce',
			items: [ getCartItemObject( product, quantity ) ],
		} );
	}
};

/**
 * Formats a regular price into a price object.
 *
 * @param {number} price
 * @return {ProductPrices} Price object.
 */
export const getPriceObject = ( price ) => {
	return {
		price: Math.round( price * 10 ** glaGtagData.currency_minor_unit ),
		currency_minor_unit: glaGtagData.currency_minor_unit,
	};
};

/**
 * Adds the known category to a product payload, overwriting any it already carries so it
 * matches the `&`-joined format the view_item event sends for the same product.
 *
 * Known gaps, both pre-existing: a variation added via the block-based "Add to Cart with
 * Options" flow resolves through `get_available_variations( 'objects' )`, which skips the
 * filter that supplies this category; and a multi-category product added via a client-rendered
 * block flow with no localized data for it, such as All Products or cart cross-sells, falls
 * back to the block's own single, unjoined category instead of this `&`-joined value.
 *
 * @param {Product} product
 * @return {Product} A new product object, with `categories` set when a category is available.
 */
export const withKnownCategory = ( product ) => {
	const category = glaGtagData.products[ product.id ]?.category;

	if ( ! category ) {
		return product;
	}

	return { ...product, categories: [ { name: category } ] };
};

/**
 * Formats a product object to include name, price, and category from global data.
 *
 * @param {Product} product
 * @return {Product} Product object with optional fields added.
 */
export const getProductObject = ( product ) => {
	const productData = glaGtagData.products[ product.id ];

	if ( ! productData ) {
		return product;
	}

	product.name = productData.name;
	product.prices = getPriceObject( productData.price );

	return withKnownCategory( product );
};

/**
 * Stores the selected variation's data, inheriting the parent product's category when known.
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

	if ( variation.category ) {
		glaGtagData.products[ variation.variation_id ].category =
			variation.category;
	}
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
 * GA4-schema item data, pushed to the GTM data layer.
 *
 * @typedef {Object} Ga4Item
 * @property {string} item_id           ID number including the `gla_` prefix.
 * @property {number} [quantity]        Quantity of this item.
 * @property {string} [item_name]       Product name.
 * @property {string} [item_category]   First product category name.
 * @property {number} [price]           Price as a decimal number.
 */

/**
 * Variation data, sent when a variation has been selected to add to cart.
 *
 * @typedef {Object} Variation
 * @property {number} variation_id    ID number.
 * @property {string} [display_name]  Name to display on the frontend.
 * @property {number} [display_price] Price value to display on the frontend.
 * @property {string} [category]      The parent product's category, joined like `view_item`'s.
 */
