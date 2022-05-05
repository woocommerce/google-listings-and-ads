/**
 * Track an event using the global gtag function.
 */
export const trackEvent = (	eventName, eventParams ) => {
	if ( typeof gtag !== 'function' ) {
		throw new Error( 'Function gtag not implemented.' );
	}
	// eslint-disable-next-line no-console
	console.log( `Tracking event ${ eventName }`, eventParams );
	window.gtag( 'event', eventName, eventParams );
};

/**
 * Formats data into an Item object.
 *
 * @see https://developers.google.com/analytics/devguides/collection/ga4/reference/events#add_to_cart
 */
 export const getItemObject = ( product, quantity ) => {
	const productIdentifier = 'gla_' + product.id;
	const productCategory =
		'categories' in product && product.categories.length
			? product.categories[ 0 ].name
			: '';
	return {
		item_id: productIdentifier,
		item_name: product.name,
		item_category: productCategory,
		price: (
			parseInt( product.prices.price, 10 ) /
			10 ** product.prices.currency_minor_unit
		).toString(),
		quantity,
		google_business_vertical: 'retail',
	};
};
