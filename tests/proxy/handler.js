'use strict';

const config = require( './config' );

module.exports.checkRequest = ( request, h ) => {
	if ( config.logResponses ) {
		// eslint-disable-next-line no-console
		console.log( 'Request path: ', '\n', request.params.path );
	}

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

	// Mock responses for the Merchant Center API reports search.
	// https://developers.google.com/shopping-content/reference/rest/v2.1/reports/search
	if ( request.params.path.includes( 'reports/search' ) ) {
		const body = JSON.parse( request.payload );
		if ( config.logResponses ) {
			// eslint-disable-next-line no-console
			console.log( 'Request query: ', '\n', body.query );
		}

		// Handle access errors early.
		if ( config.proxyMode === 'access_error' ) {
			return h
				.response(
					require( './mocks/mc/price-benchmarks/403_error.json' )
				)
				.code( 403 );
		}

		let mockPath = false;
		const isSingleProduct = body.query.includes(
			'WHERE product_view.id IN'
		);

		if ( body.query.includes( 'FROM PriceCompetitivenessProductView' ) ) {
			const file = isSingleProduct
				? 'price-competitiveness-item'
				: 'price-competitiveness';
			mockPath = `./mocks/mc/price-benchmarks/${ file }.json`;
		}

		if ( body.query.includes( 'FROM PriceInsightsProductView' ) ) {
			const file = isSingleProduct
				? 'price-insights-item'
				: 'price-insights';
			mockPath = `./mocks/mc/price-benchmarks/${ file }.json`;
		}

		if ( body.query.includes( 'FROM ProductView' ) ) {
			return false;
		}

		if ( body.query.includes( 'FROM MerchantPerformanceView' ) ) {
			if ( body.query.includes( 'WHERE segments.date BETWEEN' ) ) {
				mockPath = './mocks/mc/price-benchmarks/merchant-report.json';
			} else {
				const file = body.query.includes( 'segments.offer_id' )
					? 'products'
					: 'programs';
				const page = body.pageToken ? '-' + body.pageToken : '';

				mockPath = `./mocks/mc/reports/${ file }${ page }.json`;
			}
		}

		return mockPath ? require( mockPath ) : false;
	}

	// Mock responses for the Merchant Center API products custom batch responses.
	// https://developers.google.com/shopping-content/reference/rest/v2.1/products/custombatch
	if ( request.params.path.includes( 'products/batch' ) ) {
		const body = JSON.parse( request.payload );
		if (
			config.proxyMode === 'delete_error' &&
			body.entries[ 0 ].method === 'delete'
		) {
			const response = require( './mocks/mc/delete_errors' );

			return response.deleteErrors( body );
		}

		if (
			config.proxyMode === 'update_error' &&
			body.entries[ 0 ].method === 'insert'
		) {
			const response = require( './mocks/mc/update_errors' );

			return response.updateErrors( body );
		}
	}

	return false;
};
