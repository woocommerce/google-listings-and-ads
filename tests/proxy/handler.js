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
	// Only patch and the account_restricted error are mocked; see README.
	if (
		request.params.path.includes( 'google-gbp/v4' ) &&
		request.params.path.includes( 'localPosts' )
	) {
		if ( request.method === 'patch' ) {
			return require( './mocks/gbp/local-posts/patch.json' );
		}

		if (
			request.method === 'post' &&
			config.proxyMode === 'account_restricted'
		) {
			return h
				.response(
					require( './mocks/gbp/local-posts/errors/account-restricted.json' )
				)
				.code( 400 );
		}

		return false;
	}

	// Mock responses for the Google Business Profile Verifications API.
	// https://developers.google.com/my-business/reference/verifications/rest/v1/locations/getVoiceOfMerchantState
	//
	// Stays mocked even though the real host is confirmed live; see the README.
	if (
		request.params.path.includes( 'google-gbp-verifications' ) &&
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
