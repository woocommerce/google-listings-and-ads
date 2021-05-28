'use strict';

module.exports.checkRequest = ( request ) => {
	if ( request.params.path.includes( 'googleAds:search' ) ) {
		const body = JSON.parse( request.payload );
		if ( body.query.includes( 'shopping_performance_view' ) ) {
			const file = body.query.includes( 'segments.product_item_id' )
				? 'products'
				: 'programs';
			const page = body.pageToken ? '-' + body.pageToken : '';

			return require( `./mocks/ads/reports/${ file }${ page }.json` );
		}
	}
	if ( request.params.path.includes( 'reports/search' ) ) {
		const body = JSON.parse( request.payload );
		const file = body.query.includes( 'segments.offer_id' )
			? 'products'
			: 'programs';
		const page = body.pageToken ? '-' + body.pageToken : '';

		return require( `./mocks/mc/reports/${ file }${ page }.json` );
	}

	return false;
};
