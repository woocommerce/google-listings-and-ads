'use strict';

const config = require( './config' );

const SHARED_CONNECTION_PATH = 'google/connection/google-mc';
const BUSINESS_PROFILE_SCOPE =
	'https://www.googleapis.com/auth/business.manage';

// Whether a mocked Business Profile grant has happened since the proxy started.
let isBusinessProfileScopeGranted = false;

/**
 * Whether the response to this request should report the Business Profile scope as granted.
 *
 * @param {Object} request Hapi request.
 * @return {boolean} True for the shared connection's status request, once a mocked grant has happened.
 */
module.exports.shouldAddBusinessProfileScope = ( request ) => {
	return (
		isBusinessProfileScopeGranted &&
		request.method === 'get' &&
		request.params.path === SHARED_CONNECTION_PATH
	);
};

/**
 * Add the Business Profile scope to the Connect Server's real status response.
 *
 * @param {Error|null} err Proxy error, if the Connect Server request failed.
 * @param {Object} res Connect Server response stream.
 * @param {Object} request Hapi request.
 * @param {Object} h Hapi response toolkit.
 * @return {Promise<Object>} The status response, with the scope added to a successful JSON response.
 */
module.exports.addBusinessProfileScope = async ( err, res, request, h ) => {
	if ( err ) {
		throw err;
	}

	const chunks = [];
	for await ( const chunk of res ) {
		chunks.push( chunk );
	}
	const body = Buffer.concat( chunks ).toString();

	// Pass anything but a successful JSON response through unchanged, so the real error shows.
	const passThrough = () => {
		return h
			.response( body )
			.code( res.statusCode )
			.type( res.headers[ 'content-type' ] || 'text/plain' );
	};

	if ( res.statusCode !== 200 ) {
		return passThrough();
	}

	let payload;
	try {
		payload = JSON.parse( body );
	} catch ( parseError ) {
		return passThrough();
	}

	if ( Array.isArray( payload.scope ) ) {
		payload.scope = [
			...new Set( [ ...payload.scope, BUSINESS_PROFILE_SCOPE ] ),
		];
	}

	return h.response( payload ).code( res.statusCode );
};

module.exports.checkRequest = ( request, h ) => {
	if ( config.logResponses ) {
		// eslint-disable-next-line no-console
		console.log( 'Request path: ', '\n', request.params.path );
	}

	// Mock the Business Profile access grant. See "Available mocks" in the README.
	if (
		request.params.path === SHARED_CONNECTION_PATH &&
		request.method === 'post'
	) {
		const body = JSON.parse( request.payload || '{}' );

		if (
			( body.additionalScopes || [] ).includes( BUSINESS_PROFILE_SCOPE )
		) {
			isBusinessProfileScopeGranted = true;

			const returnUrl = new URL( body.returnUrl );
			returnUrl.searchParams.set( 'google-mc', 'connected' );

			return { oauthUrl: returnUrl.toString() };
		}
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

		// Anything else must be a GET read-back — any other verb (PUT, HEAD,
		// etc.) is a client bug and should pass through rather than be
		// masked by a fixture.
		if ( request.method !== 'get' ) {
			return false;
		}

		// A bare list request (GET .../localPosts, no specific post ID) is
		// deliberately not mocked (see README) — fall through to the real
		// Connect Server instead of wrongly serving a single-post fixture.
		if ( ! request.params.path.match( /localPosts\/[^/]+/ ) ) {
			return false;
		}

		if ( request.params.path.match( /localPosts\/9002(?:$|[/:])/ ) ) {
			return require( './mocks/gbp/local-posts/get/live.json' );
		}

		if ( request.params.path.match( /localPosts\/9003(?:$|[/:])/ ) ) {
			return require( './mocks/gbp/local-posts/get/rejected.json' );
		}

		return require( './mocks/gbp/local-posts/get/processing.json' );
	}

	// Mock responses for the Google Business Profile Verifications API.
	// https://developers.google.com/my-business/reference/verifications/rest/v1/locations/getVoiceOfMerchantState
	//
	// Checked independently of the accounts/locations branch below: this
	// method is top-level (v1/{name=locations/*}:getVoiceOfMerchantState),
	// NOT nested under accounts/{a}/ — the mocked locations.list response
	// returns bare `locations/*` names, and a client building the URL from
	// that name would never match an 'accounts/' prefix check.
	//
	// Same placeholder-path caveat as above.
	if (
		request.params.path.includes( 'google-gbp' ) &&
		request.params.path.includes( 'getVoiceOfMerchantState' )
	) {
		if ( request.params.path.includes( 'locations/2222' ) ) {
			return require( './mocks/gbp/accounts/locations/voice-of-merchant/unverified.json' );
		}

		if ( request.params.path.includes( 'locations/3333' ) ) {
			return require( './mocks/gbp/accounts/locations/voice-of-merchant/suspended.json' );
		}

		return require( './mocks/gbp/accounts/locations/voice-of-merchant/eligible.json' );
	}

	// Mock responses for the Google Business Profile Account Management (v1) and
	// Business Information (v1) APIs — accounts and their locations.
	// https://developers.google.com/my-business/reference/accountmanagement/rest/v1/accounts/list
	// https://developers.google.com/my-business/reference/businessinformation/rest/v1/accounts.locations/list
	//
	// Same placeholder-path caveat as above.
	if ( request.params.path.includes( 'google-gbp/accounts' ) ) {
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
