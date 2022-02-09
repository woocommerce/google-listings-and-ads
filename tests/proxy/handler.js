'use strict';

const config = require( './config' );

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

	if ( 'delete_error' === config.proxyMode && request.params.path.includes( 'products/batch' ) ) {
		const body = JSON.parse( request.payload );
		if ( 'delete' === body.entries[0].method ) {
			const response = require( './mocks/mc/delete_errors' );
			return response.deleteErrors( body );
		}
	}

	return false;
};
