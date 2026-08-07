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

		if ( body.query.includes( 'recommendation' ) ) {
			if ( config.logResponses ) {
				// eslint-disable-next-line no-console
				console.log(
					'Returning mock recommendations for query: ',
					body.query
				);
			}

			if (
				body.query.includes( 'IMPROVE_PERFORMANCE_MAX_AD_STRENGTH' )
			) {
				return require( './mocks/ads/recommendations/pmax-asset.json' );
			}

			return require( './mocks/ads/recommendations/campaign-budget.json' );
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

	if (
		request.params.path.includes( 'google/manager/link-customer' ) &&
		request.method === 'post'
	) {
		return h
			.response(
				require( './mocks/ads/connection/link-existing-account-error.json' )
			)
			.code( 400 );
	}

	if (
		request.params.path.includes( 'google/manager/link-merchant' ) &&
		request.method === 'post'
	) {
		return h
			.response(
				require( './mocks/mc/connection/link-existing-account-error.json' )
			)
			.code( 400 );
	}

	// Mock responses for the Search Console connection state (GOOWOO-883).
	// The BE for this endpoint (GOOWOO-882) hasn't landed yet, so this switches on
	// `config.proxyMode` to exercise every FE connection state against a mocked payload,
	// following this same file's established `proxyMode`-keyed convention.
	if (
		request.params.path.includes( 'search-console/connection' ) &&
		request.method === 'get'
	) {
		const mockPath = SEARCH_CONSOLE_CONNECTION_MOCKS[ config.proxyMode ];

		return mockPath ? require( mockPath ) : false;
	}

	return false;
};

const SEARCH_CONSOLE_CONNECTION_MOCKS = {
	search_console_not_connected: './mocks/search-console/connection/not-connected.json',
	search_console_not_connected_skip_auth_prompt:
		'./mocks/search-console/connection/not-connected-skip-auth-prompt.json',
	search_console_property_selection_single:
		'./mocks/search-console/connection/property-selection-single.json',
	search_console_property_selection_multi:
		'./mocks/search-console/connection/property-selection-multi.json',
	search_console_property_selection_no_match:
		'./mocks/search-console/connection/property-selection-no-match.json',
	search_console_verification: './mocks/search-console/connection/verification.json',
	search_console_verification_request_access:
		'./mocks/search-console/connection/verification-request-access.json',
	search_console_action_needed: './mocks/search-console/connection/action-needed.json',
	search_console_reconnect: './mocks/search-console/connection/reconnect.json',
	search_console_connection_failed:
		'./mocks/search-console/connection/connection-failed.json',
	search_console_incomplete: './mocks/search-console/connection/incomplete.json',
	search_console_connected: './mocks/search-console/connection/connected.json',
};
