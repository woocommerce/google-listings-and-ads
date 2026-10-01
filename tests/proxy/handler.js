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

	// Mock responses for the Google Business Profile local posts API (v4).
	// https://developers.google.com/my-business/reference/rest/v4/accounts.locations.localPosts
	//
	// Checked before the accounts/locations branch below, since a real
	// localPosts path nests under .../accounts/{a}/locations/{l}/localPosts/{p}
	// and would otherwise also match that branch's own '/locations' check.
	//
	// The 'google-gbp' path segment is a placeholder — Woo's real Connect
	// Server path for Business Profile passthrough isn't confirmed yet.
	// Expected to be a small string change here once it is.
	if (
		request.params.path.includes( 'google-gbp' ) &&
		request.params.path.includes( 'localPosts' )
	) {
		if ( request.method === 'delete' ) {
			return {};
		}

		if ( request.method === 'patch' ) {
			return require( './mocks/gbp/local-posts/patch.json' );
		}

		if ( request.method === 'post' ) {
			if ( config.proxyMode === 'account_restricted' ) {
				return h
					.response(
						require( './mocks/gbp/local-posts/errors/account-restricted.json' )
					)
					.code( 400 );
			}

			return require( './mocks/gbp/local-posts/create.json' );
		}

		// GET — reading back a specific post's current state by ID.
		if ( request.params.path.includes( 'localPosts/9002' ) ) {
			return require( './mocks/gbp/local-posts/get/live.json' );
		}

		if ( request.params.path.includes( 'localPosts/9003' ) ) {
			return require( './mocks/gbp/local-posts/get/rejected.json' );
		}

		// A bare list request (GET .../localPosts, no specific post ID) is
		// deliberately not mocked (see README) — fall through to the real
		// Connect Server instead of wrongly serving a single-post fixture.
		if ( ! request.params.path.match( /localPosts\/[^/?]+/ ) ) {
			return false;
		}

		return require( './mocks/gbp/local-posts/get/processing.json' );
	}

	// Mock responses for the Google Business Profile Account Management (v1) and
	// Business Information (v1) APIs — accounts and their locations.
	// https://developers.google.com/my-business/reference/accountmanagement/rest/v1/accounts/list
	// https://developers.google.com/my-business/reference/businessinformation/rest/v1/accounts.locations/list
	// https://developers.google.com/my-business/reference/verifications/rest/v1/locations/getVoiceOfMerchantState
	//
	// Same placeholder-path caveat as above.
	if ( request.params.path.includes( 'google-gbp/accounts' ) ) {
		if ( request.params.path.includes( 'getVoiceOfMerchantState' ) ) {
			if ( request.params.path.includes( 'locations/2222' ) ) {
				return require( './mocks/gbp/accounts/locations/voice-of-merchant/unverified.json' );
			}

			if ( request.params.path.includes( 'locations/3333' ) ) {
				return require( './mocks/gbp/accounts/locations/voice-of-merchant/suspended.json' );
			}

			return require( './mocks/gbp/accounts/locations/voice-of-merchant/eligible.json' );
		}

		if ( request.params.path.includes( '/locations' ) ) {
			return require( './mocks/gbp/accounts/locations/list.json' );
		}

		return require( './mocks/gbp/accounts/list.json' );
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

	return false;
};
