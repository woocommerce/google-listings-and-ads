/**
 * External dependencies
 */
import lodash from 'lodash';

const proxyFulfill = ( instance, options ) => {
	return new Proxy( instance.originalTarget || instance, {
		get( target, property ) {
			if ( property === 'originalTarget' ) {
				return target;
			}

			if ( property === 'previousOptions' ) {
				return options;
			}

			const value = Reflect.get( ...arguments );

			if ( property === 'fulfillRequest' ) {
				return function ( url, payload, status, methods ) {
					const mergedOpts = {
						...instance.previousOptions,
						...options,
					};
					const args = [ url, payload, status, methods, mergedOpts ];
					return value.apply( target, args );
				};
			}

			return value;
		},
	} );
};

/**
 * Mock Requests
 *
 * This class is used to mock requests to the server.
 */
export default class MockRequests {
	/**
	 * @param {import('@playwright/test').Page} page
	 */
	constructor( page ) {
		this.page = page;
	}

	/**
	 * Fulfill a request multiple times.
	 *
	 * @param {number} times The number of times to fulfill the request.
	 * @return {this} A proxied instance intercepts the subsequent fulfillRequest calls to attach the `times` option.
	 */
	withFulfillTimes( times ) {
		return proxyFulfill( this, { times } );
	}

	/**
	 * Defer the fulfillment of subsequent requests until calling the `continueFulfill` of the returned proxied instance.
	 *
	 * @return {this} A proxied instance intercepts the subsequent fulfillRequest calls to attach the `beforeFulfill` option.
	 */
	withFulfillDeferred() {
		let continueFulfill;
		const beforeFulfill = new Promise( ( resolve ) => {
			continueFulfill = resolve;
		} );

		const proxiedInstance = proxyFulfill( this, { beforeFulfill } );
		proxiedInstance.continueFulfill = continueFulfill;

		return proxiedInstance;
	}

	/**
	 * Fulfill a request with a payload.
	 *
	 * @param {RegExp|string} url The url to fulfill.
	 * @param {Object} payload The payload to send.
	 * @param {number} [status] The HTTP status in the response.
	 * @param {Array} [methods] The HTTP methods in the request to be fulfill.
	 * @param {Object} [options] Options to customize the request to be fulfill.
	 * @param {number} [options.times] The number of times to fulfill the request.
	 * @param {Promise<void>} [options.beforeFulfill] A promise that resolves before fulfilling the request.
	 * @return {Promise<void>}
	 */
	async fulfillRequest(
		url,
		payload,
		status = 200,
		methods = [],
		options = {}
	) {
		const handler = async ( route ) => {
			if (
				methods.length === 0 ||
				methods.includes( route.request().method() )
			) {
				const fulfillOptions = {
					status,
					contentType: 'application/json',
					headers: { 'Access-Control-Allow-Origin': '*' },
					body: JSON.stringify( payload ),
				};

				const { beforeFulfill = Promise.resolve() } = options;

				return beforeFulfill.then( () =>
					route.fulfill( fulfillOptions )
				);
			}
			return route.fallback();
		};

		await this.page.route( url, handler, { times: options.times } );
	}

	/**
	 * Fulfill the WC options default country request.
	 *
	 * @param {Object} payload
	 * @return {Promise<void>}
	 */
	async fulfillWCDefaultCountry( payload ) {
		await this.fulfillRequest(
			/wc-admin\/options\?options=.*woocommerce_default_country\b/,
			payload
		);
	}

	/**
	 * Fulfill the MC Report Program request.
	 *
	 * @param {Object} payload
	 * @return {Promise<void>}
	 */
	async fulfillMCReportProgram( payload ) {
		await this.fulfillRequest(
			/\/wc\/gla\/mc\/reports\/programs\b/,
			payload
		);
	}

	/**
	 * Fulfill the Ads Report Program request.
	 *
	 * @param {Object} payload
	 * @return {Promise<void>}
	 */
	async fulfillAdsReportProgram( payload ) {
		await this.fulfillRequest(
			/\/wc\/gla\/ads\/reports\/programs\b/,
			payload
		);
	}

	/**
	 * Fulfill the Ads Report Products request.
	 *
	 * @param {Object} payload
	 * @return {Promise<void>}
	 */
	async fulfillAdsReportProducts( payload ) {
		await this.fulfillRequest(
			/\/wc\/gla\/ads\/reports\/products\b/,
			payload
		);
	}

	/**
	 * Fulfill the Target Audience request.
	 *
	 * @param {Object} payload
	 * @param {Array} methods
	 * @return {Promise<void>}
	 */
	async fulfillTargetAudience( payload, methods = [] ) {
		await this.fulfillRequest(
			/\/wc\/gla\/mc\/target_audience\b/,
			payload,
			200,
			methods
		);
	}

	/**
	 * Fulfill the Target Audience suggestions request.
	 *
	 * @param {Object} payload
	 * @return {Promise<void>}
	 */
	async fulfillTargetAudienceSuggestions( payload ) {
		await this.fulfillRequest(
			/\/wc\/gla\/mc\/target_audience\/suggestions\b/,
			payload
		);
	}

	/**
	 * Fulfill the MC accounts request.
	 *
	 * @param {Object} payload
	 * @param {number} status
	 * @param {string[]} [methods]
	 * @return {Promise<void>}
	 */
	async fulfillMCAccounts( payload, status = 200, methods ) {
		await this.fulfillRequest(
			/\/wc\/gla\/mc\/accounts\b/,
			payload,
			status,
			methods
		);
	}

	/**
	 * Fulfill the MC accounts claim-overwrite request.
	 *
	 * @param {Object} payload
	 * @param {number} status
	 * @return {Promise<void>}
	 */
	async fulfillMCAccountsClaimOverwrite( payload, status = 200 ) {
		await this.fulfillRequest(
			/\/wc\/gla\/mc\/accounts\/claim-overwrite\b/,
			payload,
			status
		);
	}

	/**
	 * Fulfill the MC connection request.
	 *
	 * @param {Object} payload
	 * @return {Promise<void>}
	 */
	async fulfillMCConnection( payload, status = 200, methods = [ 'GET' ] ) {
		await this.fulfillRequest(
			/\/wc\/gla\/mc\/connection\b/,
			payload,
			status,
			methods
		);
	}

	/**
	 * Fulfill the MC setup request.
	 *
	 * @param {Object} payload
	 * @return {Promise<void>}
	 */
	async fulfillMCSetup( payload ) {
		await this.fulfillRequest( /\/wc\/gla\/mc\/setup\b/, payload );
	}

	/**
	 * Fulfill the JetPack Connection request.
	 *
	 * @param {Object} payload
	 * @return {Promise<void>}
	 */
	async fulfillJetPackConnection( payload ) {
		await this.fulfillRequest( /\/wc\/gla\/jetpack\/connected\b/, payload );
	}

	/**
	 * Fulfill the request to connect Jetpack.
	 *
	 * @param {Object} payload
	 * @return {Promise<void>}
	 */
	async fulfillConnectJetPack( payload ) {
		await this.fulfillRequest( /\/wc\/gla\/jetpack\/connect\b/, payload );
	}

	/**
	 * Fulfill the Google Connection request.
	 *
	 * @param {Object} payload
	 * @return {Promise<void>}
	 */
	async fulfillGoogleConnection( payload ) {
		await this.fulfillRequest( /\/wc\/gla\/google\/connected\b/, payload );
	}

	/**
	 * Fulfill the request to connect Google.
	 *
	 * @param {Object} payload
	 * @return {Promise<void>}
	 */
	async fulfillConnectGoogle( payload ) {
		await this.fulfillRequest( /\/wc\/gla\/google\/connect\b/, payload );
	}

	/**
	 * Fulfill the Ads Connection request.
	 *
	 * @param {Object} payload
	 * @return {Promise<void>}
	 */
	async fulfillAdsConnection( payload ) {
		await this.fulfillRequest( /\/wc\/gla\/ads\/connection\b/, payload );
	}

	/**
	 * Fulfill the YouTube Account Connection request.
	 *
	 * @param {Object} payload
	 * @param {number} [status=200]
	 * @param {Array} [methods=[]]
	 * @return {Promise<void>}
	 */
	async fulfillYouTubeAccountConnection(
		payload,
		status = 200,
		methods = []
	) {
		await this.fulfillRequest(
			/\/wc\/gla\/youtube\/connection\b/,
			payload,
			status,
			methods
		);
	}

	/**
	 * Fulfill the YouTube connect request.
	 *
	 * @param {Object} payload
	 * @param {number} [status=200]
	 * @param {Array} [methods=['GET']]
	 * @return {Promise<void>}
	 */
	async fulfillYouTubeConnect( payload, status = 200, methods = [ 'GET' ] ) {
		await this.fulfillRequest(
			/\/wc\/gla\/youtube\/connect\b/,
			payload,
			status,
			methods
		);
	}

	/**
	 * Fulfill the Settings request.
	 *
	 * @param {Object} payload
	 * @param {number} [status=200]
	 * @param {Array}  [methods=['GET']]
	 * @return {Promise<void>}
	 */
	async fulfillSettings( payload, status = 200, methods = [ 'GET' ] ) {
		await this.fulfillRequest(
			/\/wc\/gla\/mc\/settings\b/,
			payload,
			status,
			methods
		);
	}

	/**
	 * Fulfill the Ads Account request.
	 *
	 * @param {Object} payload
	 * @param {number} [status=200]
	 * @param {string[]} [methods]
	 * @return {Promise<void>}
	 */
	async fulfillAdsAccounts( payload, status = 200, methods ) {
		await this.fulfillRequest(
			/\/wc\/gla\/ads\/accounts\b/,
			payload,
			status,
			methods
		);
	}

	/**
	 * Fulfill the Ads Account Status request.
	 *
	 * @param {Object} payload
	 * @return {Promise<void>}
	 */
	async fulfillAdsAccountStatus( payload ) {
		await this.fulfillRequest(
			/\/wc\/gla\/ads\/account-status\b/,
			payload
		);
	}

	/**
	 * Fulfill the Sync Settings Connection request.
	 *
	 * @param {Object} payload
	 * @return {Promise<void>}
	 */
	async fulfillSettingsSync( payload ) {
		await this.fulfillRequest( /\/wc\/gla\/mc\/settings\/sync\b/, payload );
	}

	/**
	 * Fulfill contact information request.
	 *
	 * @param {Object} payload
	 * @return {Promise<void>}
	 */
	async fulfillContactInformation( payload ) {
		await this.fulfillRequest(
			/\/wc\/gla\/mc\/contact-information\b/,
			payload
		);
	}

	/**
	 * Fulfill the MC account issues request.
	 *
	 * @param {Object} payload
	 * @return {Promise<void>}
	 */
	async fulfillAccountIssuesRequest( payload ) {
		await this.fulfillRequest(
			/\/wc\/gla\/mc\/issues\/account\b/,
			payload
		);
	}

	/**
	 * Fulfill the MC product issues request.
	 *
	 * @param {Object} payload
	 * @return {Promise<void>}
	 */
	async fulfillProductIssuesRequest( payload ) {
		await this.fulfillRequest(
			/\/wc\/gla\/mc\/issues\/product\b/,
			payload
		);
	}

	/**
	 * Fulfill the MC review request.
	 *
	 * @param {Object} payload
	 * @return {Promise<void>}
	 */
	async fulfillMCReview( payload ) {
		await this.fulfillRequest( /\/wc\/gla\/mc\/review\b/, payload );
	}

	/**
	 * Fulfill product statistics request.
	 *
	 * @param {Object} payload
	 * @return {Promise<void>}
	 */
	async fulfillProductStatisticsRequest( payload ) {
		await this.fulfillRequest(
			/\/wc\/gla\/mc\/product-statistics\b/,
			payload
		);
	}

	/**
	 * Fulfill billing status request.
	 *
	 * @param {Object} payload
	 * @return {Promise<void>}
	 */
	async fulfillBillingStatusRequest( payload ) {
		await this.fulfillRequest(
			/\/wc\/gla\/ads\/billing-status\b/,
			payload
		);
	}

	/**
	 * Fulfill ads campaigns request.
	 *
	 * @param {Object} payload
	 * @param {number} status The HTTP status in the response.
	 * @param {Array} methods The HTTP methods in the request to be fulfill.
	 * @return {Promise<void>}
	 */
	async fulfillAdsCampaignsRequest( payload, status = 200, methods = [] ) {
		await this.fulfillRequest(
			/\/wc\/gla\/ads\/campaigns\b/,
			payload,
			status,
			methods
		);
	}

	/**
	 * Fulfill the budget recommendations request.
	 *
	 * @param {Object} payload
	 * @return {Promise<void>}
	 */
	async fulfillBudgetRecommendations( payload ) {
		const mergedPayload = lodash.merge(
			{
				currency: 'USD',
				recommendations: [
					{
						level: 'Recommended',
						country: 'US',
						daily_budget: 15,
						metrics: {
							cost: 105,
							conversions: 2.2,
							conversions_value: 89.98,
						},
					},
					{
						level: 'High',
						country: 'US',
						daily_budget: 20.5,
						metrics: {
							cost: 143.5,
							conversions: 2.5,
							conversions_value: 98.59,
						},
					},
					{
						level: 'Low',
						country: 'US',
						daily_budget: 7,
						metrics: {
							cost: 49,
							conversions: 2,
							conversions_value: 80.48,
						},
					},
				],
			},
			payload
		);

		await this.fulfillRequest(
			/\/wc\/gla\/ads\/campaigns\/budget-recommendation\b/,
			mergedPayload,
			200,
			[ 'GET' ]
		);
	}

	/**
	 * Mock the budget metrics.
	 *
	 * @param {Object} payload
	 * @return {Promise<void>}
	 */
	async mockBudgetMetrics( payload ) {
		const mergedPayload = lodash.merge(
			{
				currency: 'USD',
				budget: 15,
				country: 'US',
				metrics: {
					cost: 105,
					conversions: 4.3,
					conversions_value: 172.3137664794922,
				},
			},
			payload
		);

		await this.fulfillRequest(
			/\/wc\/gla\/ads\/campaigns\/budget-metrics\b/,
			mergedPayload,
			200,
			[ 'GET' ]
		);
	}

	/**
	 * Mock the Ads incentive credits.
	 *
	 * @param {Object} payload
	 * @return {Promise<void>}
	 */
	async mockAdsIncentiveCredits( payload ) {
		const mergedPayload = lodash.merge(
			{
				ads_currency: 'USD',
				currency: 'USD',
				country: 'US',
				spending: 500,
				credit: 500,
			},
			payload
		);

		await this.fulfillRequest(
			/\/wc\/gla\/ads\/incentive-credits\b/,
			mergedPayload,
			200,
			[ 'GET' ]
		);
	}

	/**
	 * Fulfill the CYO incentives GET request.
	 *
	 * @param {Array} [incentives] Incentive items array. Omit to use the default three-tier set.
	 * @param {number} [status=200]
	 * @return {Promise<void>}
	 */
	async fulfillCYOIncentives( incentives, status = 200 ) {
		const defaultIncentives = [
			{
				id: 'incentive-low-id',
				type: 'ACQUISITION',
				offer: 'low',
				termsAndConditionsUrl:
					'https://ads.google.com/aw/campaignassistant',
				requirement: {
					spend: {
						requiredAmount: { currencyCode: 'USD', units: '600' },
						awardAmount: { currencyCode: 'USD', units: '600' },
					},
				},
			},
			{
				id: 'incentive-medium-id',
				type: 'ACQUISITION',
				offer: 'medium',
				termsAndConditionsUrl:
					'https://ads.google.com/aw/campaignassistant',
				requirement: {
					spend: {
						requiredAmount: { currencyCode: 'USD', units: '1800' },
						awardAmount: { currencyCode: 'USD', units: '1200' },
					},
				},
			},
			{
				id: 'incentive-high-id',
				type: 'ACQUISITION',
				offer: 'high',
				termsAndConditionsUrl:
					'https://ads.google.com/aw/campaignassistant',
				requirement: {
					spend: {
						requiredAmount: { currencyCode: 'USD', units: '3600' },
						awardAmount: { currencyCode: 'USD', units: '1800' },
					},
				},
			},
		];

		const payload = {
			type: 'CYO_INCENTIVE',
			termsAndConditionsUrl:
				'https://ads.google.com/aw/campaignassistant',
			incentives: incentives ?? defaultIncentives,
		};

		await this.fulfillRequest(
			/\/wc\/gla\/ads\/incentives\b/,
			payload,
			status,
			[ 'GET' ]
		);
	}

	/**
	 * Fulfill the apply CYO incentive POST request.
	 * The method filter ensures GET requests to similarly-named endpoints fall through.
	 *
	 * @param {Object} [payload={ success: true }]
	 * @param {number} [status=200]
	 * @return {Promise<void>}
	 */
	async fulfillApplyCYOIncentive(
		payload = { success: true },
		status = 200
	) {
		await this.fulfillRequest(
			/\/wc\/gla\/ads\/incentives\b/,
			payload,
			status,
			[ 'POST' ]
		);
	}

	/**
	 * Fulfill the price benchmark suggestions request.
	 *
	 * @param {Object} payload
	 * @return {Promise<void>}
	 */
	async fulfillPriceBenchmarkSuggestions( payload, status = 200 ) {
		await this.fulfillRequest(
			/\/wc\/gla\/mc\/price-benchmarks(?:\/\d+)?(?:\?(?:[^#]*))?\b/,
			payload,
			status,
			[ 'GET' ]
		);
	}

	/**
	 * Fulfill the price benchmark product suggestions request for a specific product.
	 *
	 * @param {string|number} productId - The ID of the product to get price benchmark data for.
	 * @param {Object} payload - The mock response payload.
	 * @param {number} [status=200] - The HTTP status code to return.
	 * @return {Promise<void>}
	 */
	async fulfillPriceBenchmarkProductSuggestions(
		productId,
		payload,
		status = 200
	) {
		await this.fulfillRequest(
			new RegExp(
				`\\/wc\\/gla\\/mc\\/price-benchmarks\\/${ productId }\\b`
			),
			payload,
			status,
			[ 'GET' ]
		);
	}

	/**
	 * Mock the request to connect Jetpack
	 *
	 * @param {string} url
	 */
	async mockJetpackConnect( url ) {
		await this.fulfillConnectJetPack( { url } );
	}

	/**
	 * Mock Jetpack as connected.
	 *
	 * @param {string} displayName
	 * @param {string} email
	 */
	async mockJetpackConnected(
		displayName = 'John',
		email = 'mail@example.com'
	) {
		await this.fulfillJetPackConnection( {
			active: 'yes',
			owner: 'yes',
			displayName,
			email,
		} );
	}

	/**
	 * Mock Jetpack as not connected.
	 */
	async mockJetpackNotConnected() {
		await this.fulfillJetPackConnection( {
			active: 'no',
			displayName: '',
			email: '',
		} );
	}

	/**
	 * Mock the request to connect Google.
	 *
	 * @param {string} url
	 */
	async mockGoogleConnect( url ) {
		await this.fulfillConnectGoogle( { url } );
	}

	/**
	 * Mock Google as connected.
	 *
	 * @param {string} email
	 */
	async mockGoogleConnected( email = 'mail@example.com' ) {
		await this.fulfillGoogleConnection( {
			active: 'yes',
			email,
			scope: [
				'https://www.googleapis.com/auth/content',
				'https://www.googleapis.com/auth/adwords',
				'https://www.googleapis.com/auth/userinfo.email',
				'https://www.googleapis.com/auth/siteverification.verify_only',
				'openid',
			],
		} );
	}

	/**
	 * Mock Google as not connected.
	 */
	async mockGoogleNotConnected() {
		await this.fulfillGoogleConnection( {
			active: 'no',
			email: '',
			scope: [],
		} );
	}

	/**
	 * Mock Google Ads account as not yet connected.
	 *
	 * @return {Promise<void>}
	 */
	async mockAdsAccountDisconnected() {
		await this.fulfillAdsConnection( {
			id: 0,
			currency: null,
			symbol: 'NT$',
			status: 'disconnected',
		} );
	}

	/**
	 * Mock Google Ads status as disconnected.
	 *
	 * @return {Promise<void>}
	 */
	async mockAdsStatusDisconnected() {
		await this.fulfillAdsAccountStatus( {
			has_access: false,
			invite_link: '',
			step: '',
		} );
	}

	/**
	 * Mock Google Ads account status as not claimed.
	 *
	 * @return {Promise<void>}
	 */
	async mockAdsStatusNotClaimed() {
		await this.fulfillAdsAccountStatus( {
			has_access: false,
			invite_link: 'https://example.com',
			step: 'account_access',
		} );
	}

	/**
	 * Mock Google Ads account status as claimed.
	 *
	 * @return {Promise<void>}
	 */
	async mockAdsStatusClaimed() {
		await this.fulfillAdsAccountStatus( {
			has_access: true,
			invite_link: '',
			step: '',
		} );
	}

	/**
	 * Mock Google Ads account as connected but its billing setup is incomplete.
	 *
	 * @return {Promise<void>}
	 */
	async mockAdsAccountIncomplete( step = 'billing' ) {
		await this.fulfillAdsConnection( {
			id: 12345,
			currency: 'TWD',
			symbol: 'NT$',
			status: 'incomplete',
			step,
		} );
	}

	/**
	 * Mock Google Ads account as connected.
	 *
	 * @param {number} [id=12345]
	 * @param {Object} [args={}] - Additional properties to customize the account connection details.
	 * @return {Promise<void>}
	 */
	async mockAdsAccountConnected( id = 12345, args = {} ) {
		await this.fulfillAdsConnection( {
			id,
			currency: 'TWD',
			symbol: 'NT$',
			status: 'connected',
			...args,
		} );
	}

	/**
	 * Mock Ads create account.
	 *
	 * @return {Promise<void>}
	 */
	async mockAdsCreateAccount() {
		await this.fulfillAdsAccounts(
			{
				has_access: false,
				invite_link: 'https://test.com',
				step: 'claim_account',
			},
			200,
			[ 'POST' ]
		);
	}

	async mockAdsAccountCreationError() {
		await this.fulfillAdsAccounts(
			{
				code: 'API_ERROR',
				message: 'There was an error connecting to Ads account.',
				data: {
					statusCode: 400,
					message: 'Unable to accept link for the customer account',
					error: {
						code: 400,
						message: 'Request contains an invalid argument.',
						status: 'INVALID_ARGUMENT',
						details: [
							{
								'@type':
									'type.googleapis.com/google.ads.googleads.v20.errors.GoogleAdsFailure',
								errors: [
									{
										errorCode: {
											managerLinkError:
												'TOO_MANY_MANAGERS',
										},
										message:
											'Client is already linked to too many managers.',
										trigger: {
											int64Value: '6530335391',
										},
										location: {
											fieldPathElements: [
												{
													fieldName: 'operations',
													index: 0,
												},
											],
										},
									},
								],
								requestId: 'T-Ayj9dDBlp2VI4yuiq3Kw',
							},
						],
					},
				},
			},
			400,
			[ 'POST' ]
		);
	}

	/**
	 * Mock the Ads accounts response.
	 *
	 * @param {Object} payload
	 * @return {Promise<void>}
	 */
	async mockAdsAccountsResponse( payload ) {
		await this.fulfillAdsAccounts( payload );
	}

	/**
	 * Mock MC Ads no accounts.
	 */
	async mockAdsHasNoAccounts() {
		await this.fulfillAdsAccounts( [] );
	}

	/**
	 * Mock MC as connected.
	 *
	 * @param {number} id
	 * @param {null|'approved'|'error'|'dissaproved'} wpcomRestApiStatus
	 */
	async mockMCConnected( id = 1234, wpcomRestApiStatus = null ) {
		await this.fulfillMCConnection( {
			id,
			status: 'connected',
			wpcom_rest_api_status: wpcomRestApiStatus,
		} );
	}

	/**
	 * Mock MC as incomplete.
	 *
	 * @param {number} id
	 * @param {string} step
	 */
	async mockMCIncomplete( id = 1234, step = 'accounts' ) {
		await this.fulfillMCConnection( {
			id,
			status: 'incomplete',
			step,
		} );
	}

	/**
	 * Mock MC as not connected.
	 */
	async mockMCNotConnected() {
		await this.fulfillMCConnection( {
			id: 0,
			status: 'disconnected',
		} );
	}

	/**
	 * Mock MC has accounts.
	 */
	async mockMCHasAccounts() {
		await this.fulfillMCAccounts( [
			{
				id: 12345,
				subaccount: true,
				name: 'MC Account 1',
				domain: 'https://example.com',
			},
			{
				id: 23456,
				subaccount: true,
				name: 'MC Account 2',
				domain: 'https://example.com',
			},
		] );
	}

	/**
	 * Mock MC has no accounts.
	 */
	async mockMCHasNoAccounts() {
		await this.fulfillMCAccounts( [] );
	}

	/**
	 * Mock MC create account where the website is not claimed.
	 *
	 * @param {number} id
	 */
	async mockMCCreateAccountWebsiteNotClaimed( id = 12345 ) {
		await this.fulfillMCAccounts(
			{
				id,
				subaccount: null,
				name: null,
				domain: null,
			},
			200,
			[ 'POST' ]
		);
	}

	/**
	 * Mock MC create account where the website is claimed.
	 *
	 * @param {number} id
	 * @param {string} websiteUrl
	 */
	async mockMCCreateAccountWebsiteClaimed(
		id = 12345,
		websiteUrl = 'example.com'
	) {
		await this.fulfillMCAccounts(
			{
				message:
					'Website already claimed, use overwrite to complete the process.',
				id,
				website_url: websiteUrl,
			},
			403,
			[ 'POST' ]
		);
	}

	/**
	 * Mock MC accounts claim overwrite.
	 *
	 * @param {number} id
	 */
	async mockMCAccountsClaimOverwrite( id = 12345 ) {
		await this.fulfillMCAccountsClaimOverwrite( {
			id,
			subaccount: null,
			name: null,
			domain: null,
		} );
	}

	/**
	 * Mock MC setup.
	 *
	 * @param {string} status
	 * @param {string} step
	 */
	async mockMCSetup( status = 'incomplete', step = 'accounts' ) {
		await this.fulfillMCSetup( {
			status,
			step,
		} );
	}

	async mockMCAccountConnectionError(
		message = 'There was an error connecting MC Account.'
	) {
		await this.fulfillRequest(
			/\/wc\/gla\/mc\/accounts\b/,
			{
				code: 'API_ERROR',
				message: message
					? message
					: 'There was an error connecting to MC account.',
				data: {
					statusCode: 400,
					message: 'Unable to link merchant center account',
					error: {
						code: 400,
						message:
							'You do not have necessary permissions to perform this action.',
						errors: [
							{
								message:
									'You do not have necessary permissions to perform this action.',
								domain: 'global',
								reason: 'invalid',
							},
						],
					},
				},
			},
			499,
			[ 'POST' ]
		);
	}

	/**
	 * Mock contact information.
	 *
	 * @param {Object} options
	 */
	async mockContactInformation( options = {} ) {
		const defaultOptions = {
			id: 12345,
			phoneNumber: null,
			phoneVerificationStatus: null,
			mcAddress: null,
			streetAddress: 'Automata Road',
			locality: 'Taipei',
			region: null,
			postalCode: '999',
			country: 'TW',
			isMCAddressDifferent: true,
			wcAddressErrors: [],
		};

		options = { ...defaultOptions, ...options };

		await this.fulfillContactInformation( {
			id: options.id,
			phone_number: options.phoneNumber,
			phone_verification_status: options.phoneVerificationStatus,
			mc_address: options.mcAddress,
			wc_address: {
				street_address: options.streetAddress,
				locality: options.locality,
				region: options.region,
				postal_code: options.postalCode,
				country: options.country,
			},
			is_mc_address_different: options.isMCAddressDifferent,
			wc_address_errors: options.wcAddressErrors,
		} );
	}

	/**
	 * Mock the POST request to mark the Ads setup as completed.
	 *
	 * @return {Promise<void>}
	 */
	async mockCompleteAdsSetup() {
		await this.fulfillRequest(
			/\/wc\/gla\/ads\/setup\/complete\b/,
			null,
			200,
			[ 'POST' ]
		);
	}

	/**
	 * Mock the successful settings sync request.
	 *
	 * @return {Promise<void>}
	 */
	async mockSuccessfulSettingsSyncRequest() {
		await this.fulfillSettingsSync( {
			status: 'success',
			message: 'Successfully synchronized settings with Google.',
		} );
	}

	/**
	 * Fulfill the REST API Authorize request.
	 *
	 * @param {Object} payload
	 * @param {Array} methods
	 * @return {Promise<void>}
	 */
	async fulfillRESTApiAuthorize( payload, methods = [] ) {
		await this.fulfillRequest(
			/\/wc\/gla\/rest-api\/authorize\b/,
			payload,
			200,
			methods
		);
	}

	/**
	 * Mocks the API response for the enhanced conversions status setting.
	 *
	 * @param {boolean} [status=false] - The desired status for enhanced conversions (enabled or disabled).
	 * @param {Array} [methods=['GET']] - The HTTP methods to fulfill the request.
	 * @return {Promise<void>} Resolves when the mock request has been fulfilled.
	 */
	async mockEnhancedConversionsStatus( status = false, methods = [ 'GET' ] ) {
		await this.fulfillRequest(
			/\/wc\/gla\/ads\/settings\b/,
			{ enhanced_conversions_enabled: status },
			200,
			methods
		);
	}

	/**
	 * Mocks a POST request to the `/wp/v2/users/me` endpoint to fulfill user preferences.
	 *
	 * @param {Object} [payload={}] - The payload to return as the mocked response.
	 * @return {Promise<void>} Resolves when the mock request has been fulfilled.
	 */
	async fulfillUsersPreferences( payload = {} ) {
		await this.fulfillRequest( /\/wp\/v2\/users\/me(\?|$)/, payload, 200, [
			'POST',
		] );
	}

	/**
	 * Mocks the fulfillment of requests to the WooCommerce products API endpoint.
	 *
	 * @param {Object} payload - The mock response payload to return for the request.
	 * @param {Array<string>} [methods=[]] - Optional array of HTTP methods to mock. Defaults to an empty array.
	 * @return {Promise<void>} A promise that resolves when the request is fulfilled.
	 */
	async fulfillWCProduct( payload, methods = [] ) {
		await this.fulfillRequest(
			/\/wc\/v3\/products(\/.*)?\b/,
			payload,
			200,
			methods
		);
	}

	/**
	 * Fulfills a mock request for the price benchmark summary endpoint.
	 *
	 * @param {Object} payload - The mock response payload to be returned.
	 * @param {number} [status=200] - The HTTP status code to be returned. Defaults to 200.
	 * @return {Promise<void>} A promise that resolves when the request is fulfilled.
	 */
	async fulfillPriceBenchmarkSummary( payload, status = 200 ) {
		await this.fulfillRequest(
			/\/wc\/gla\/mc\/price-benchmarks\/summary\b/,
			payload,
			status,
			[ 'GET' ]
		);
	}

	/**
	 * Mocks the API request for Google Ads recommendations of a specific type.
	 *
	 * @param {Array} [payload=[]] - The mock response payload to return.
	 * @param {string} [type='IMPROVE_PERFORMANCE_MAX_AD_STRENGTH'] - The type of recommendation to mock.
	 * @param {number} [status=200] - The HTTP status code to return.
	 * @return {Promise<void>} Resolves when the mock is set up.
	 */
	async mockAdsRecommendations(
		payload = [],
		type = 'IMPROVE_PERFORMANCE_MAX_AD_STRENGTH',
		status = 200
	) {
		await this.fulfillRequest(
			new RegExp(
				`\\/wc\\/gla\\/ads\\/recommendations\\?type=${ type }\\b`
			),
			payload,
			status,
			[ 'GET' ]
		);
	}

	/**
	 * Fulfills a mock request for the shipping times endpoint.
	 *
	 * @param {Object} payload - The mock response payload to be returned.
	 * @return {Promise<void>} A promise that resolves when the request is fulfilled.
	 */
	async fulfillShippingTimes( payload, status = 200 ) {
		await this.fulfillRequest(
			/\/wc\/gla\/mc\/shipping\/times\b/,
			payload,
			status,
			[ 'GET' ]
		);
	}

	/**
	 * Fulfill the shipping rates GET request.
	 *
	 * @param {Object} payload
	 * @param {number} status The HTTP status in the response.
	 * @return {Promise<void>}
	 */
	async fulfillShippingRates( payload, status = 200 ) {
		await this.fulfillRequest(
			/\/wc\/gla\/mc\/shipping\/rates\b/,
			payload,
			status,
			[ 'GET' ]
		);
	}

	/**
	 * Fulfills the YouTube account connection mock with a payload that sets
	 * the connection status to 'disconnected', causing consumers to behave
	 * as if the account is not connected.
	 *
	 * @return {Promise<void>} Resolves when the mock request has been fulfilled.
	 */
	async mockYouTubeAccountNotConnected() {
		await this.fulfillYouTubeAccountConnection( {
			status: 'disconnected',
			channel: [],
		} );
	}

	/**
	 * Mock the YouTube connect request.
	 *
	 * @param {string} [url] The URL returned by the connect endpoint.
	 * @return {Promise<void>} Resolves when the mock request has been fulfilled.
	 */
	async mockYouTubeConnect(
		url = '/wp-admin/admin.php?page=wc-admin&path=%2Fgoogle%2Fsettings&section=accounts'
	) {
		await this.fulfillYouTubeConnect( { url } );
	}

	/**
	 * Mock helper that simulates a YouTube account connection by calling
	 * `fulfillYouTubeAccountConnection` with a predefined payload.
	 *
	 * Note: although the method name suggests a connected account, the payload
	 * sets `status` to `'connected'` while still providing channel metadata
	 * (`id` and `label`).
	 *
	 * This method is asynchronous and awaits the underlying fulfillment call.
	 *
	 * @return {Promise<*>} Resolves with whatever value `fulfillYouTubeAccountConnection` returns.
	 */
	async mockYouTubeAccountConnected() {
		await this.fulfillYouTubeAccountConnection( {
			status: 'connected',
			channel: {
				id: 'a89ahifdaffe234',
				label: 'My YouTube Channel',
			},
		} );
	}

	/**
	 * Mock the YouTube disconnect request.
	 *
	 * wordpress/api-fetch's http-v1 middleware converts DELETE to POST with
	 * an X-HTTP-Method-Override: DELETE header, so we intercept POST here and
	 * let GET requests fall through to the connection-state mock.
	 *
	 * @return {Promise<void>}
	 */
	async mockYouTubeDisconnect() {
		await this.fulfillYouTubeAccountConnection( {}, 200, [ 'POST' ] );
	}

	/**
	 * Mock helper that simulates an incomplete YouTube account connection by calling
	 * `fulfillYouTubeAccountConnection` with a predefined payload.
	 *
	 * The payload sets `status` to `'incomplete'` while still providing channel metadata
	 * (`id` and `label`), which may be used by consumers to determine that the account
	 * connection process has been started but not completed.
	 *
	 * This method is asynchronous and awaits the underlying fulfillment call.
	 *
	 * @return {Promise<*>} Resolves with whatever value `fulfillYouTubeAccountConnection` returns.
	 */
	async mockYouTubeAccountIncomplete() {
		await this.fulfillYouTubeAccountConnection( {
			status: 'incomplete',
			channel: {
				id: 'a89ahifdaffe234',
				label: 'My YouTube Channel',
			},
		} );
	}

	/**
	 * Mock helper that simulates a YouTube account connection with an ineligible channel by calling
	 * `fulfillYouTubeAccountConnection` with a predefined payload.
	 * The payload includes an error message and code indicating that the channel is not eligible for the linking program.
	 *
	 * This method is asynchronous and awaits the underlying fulfillment call.
	 *
	 * @return {Promise<*>} Resolves with whatever value `fulfillYouTubeAccountConnection` returns.
	 */
	async mockNotEligibleYouTubeChannel() {
		await this.fulfillYouTubeCompleteSetup(
			{
				message: 'The channel is not eligible for the linking program.',
				error: {
					code: 403,
					message:
						'The channel is not eligible for the linking program.',
					errors: [
						{
							message:
								'The channel is not eligible for the linking program.',
							domain: 'youtube.thirdPartyLink',
							reason: 'CHANNEL_NOT_ELIGIBLE',
						},
					],
				},
			},
			403
		);
	}

	/**
	 * Mock helper that simulates a YouTube account connection with an eligible channel by calling
	 * `fulfillYouTubeAccountConnection` with a predefined payload.
	 * The payload includes a message indicating that the channel is eligible for the linking program.
	 *
	 * This method is asynchronous and awaits the underlying fulfillment call.
	 *
	 * @return {Promise<*>} Resolves with whatever value `fulfillYouTubeAccountConnection` returns.
	 */
	async mockEligibleYouTubeChannel() {
		await this.fulfillYouTubeCompleteSetup( {
			message: 'The channel is eligible for the linking program.',
		} );
	}

	/**
	 * Fulfills a mock request for the YouTube complete setup endpoint, simulating the completion of the YouTube setup process.
	 *
	 * @param {Object} payload - The mock response payload to be returned, which may include details about the completed setup.
	 * @param {number} [status=200] - The HTTP status code to be returned. Defaults to 200.
	 * @return {Promise<void>} A promise that resolves when the request is fulfilled.
	 */
	async fulfillYouTubeCompleteSetup( payload, status = 200 ) {
		await this.fulfillRequest(
			/\/wc\/gla\/youtube\/setup\/complete\b/,
			payload,
			status,
			[ 'POST' ]
		);
	}

	/**
	 * Registers a wait for a request to the YouTube complete setup endpoint, allowing tests to wait until this specific request is made before proceeding.
	 *
	 * @return {Promise<import('playwright').Request>} A promise that resolves with the intercepted request object when a request matching the criteria is made.
	 */
	async registerYouTubeCompleteSetupRequest() {
		return this.page.waitForRequest(
			( request ) =>
				request.url().includes( '/gla/youtube/setup/complete' ) &&
				request.method() === 'POST'
		);
	}

	/**
	 * Fulfills a mock request for the final URL suggestions endpoint for campaign assets.
	 *
	 * @param {Object} payload - The mock response payload to be returned.
	 * @return {Promise<void>} A promise that resolves when the request is fulfilled.
	 */
	async fulfillFinalUrlSuggestions( payload, status = 200 ) {
		await this.fulfillRequest(
			/\/wc\/gla\/assets\/final-url\/suggestions\b/,
			payload,
			status,
			[ 'GET' ]
		);
	}

	/**
	 * Mocks a request for final URL suggestions.
	 *
	 * @param {Object} payload - The mock response payload to be returned.
	 * @param {number} [status=200] - The HTTP status code to be returned. Defaults to 200.
	 * @return {Promise<void>} A promise that resolves when the request is mocked.
	 */
	async mockFinalUrlSuggestions( payload, status = 200 ) {
		await this.fulfillFinalUrlSuggestions( payload, status );
	}

	/**
	 * Fulfills a mock request for the asset suggestions endpoint.
	 *
	 * @param {Object} payload - The mock response payload to be returned.
	 * @param {number} [status=200] - The HTTP status code to be returned.
	 * @return {Promise<void>} A promise that resolves when the request is fulfilled.
	 */
	async fulfillAssetSuggestions( payload, status = 200 ) {
		await this.fulfillRequest(
			/\/wc\/gla\/assets\/suggestions\b/,
			payload,
			status,
			[ 'GET' ]
		);
	}

	/**
	 * Mocks a request for asset suggestions.
	 *
	 * @param {Object} payload - The mock response payload to be returned.
	 */
	async mockAssetSuggestions( payload, status = 200 ) {
		await this.fulfillAssetSuggestions( payload, status );
	}

	/**
	 * Fulfills a mock request for the ads settings endpoint.
	 *
	 * @param {Object} payload - The mock response payload to be returned.
	 * @param {number} [status=200] - The HTTP status code to be returned.
	 * @return {Promise<void>} A promise that resolves when the request is fulfilled.
	 */
	async fulfillAdsSettings( payload, status = 200 ) {
		await this.fulfillRequest(
			/\/wc\/gla\/ads\/settings\b/,
			payload,
			status,
			[ 'GET' ]
		);
	}

	/**
	 * Mocks a request for the ads settings endpoint.
	 *
	 * @param {Object} payload - The mock response payload to be returned.
	 * @param {number} [status=200] - The HTTP status code to be returned.
	 * @return {Promise<void>} A promise that resolves when the request is mocked.
	 */
	async mockAdsSettings( payload, status = 200 ) {
		await this.fulfillAdsSettings( payload, status );
	}

	/**
	 * Fulfills a mock request for the asset groups of a specific campaign.
	 *
	 * @param {string|number} campaignId - The ID of the campaign to get asset groups for.
	 * @param {Object} payload - The mock response payload to be returned.
	 * @param {number} [status=200] - The HTTP status code to be returned.
	 * @return {Promise<void>} A promise that resolves when the request is fulfilled.
	 */
	async fulfillAssetGroupsForCampaign( campaignId, payload, status = 200 ) {
		await this.fulfillRequest(
			new RegExp(
				`\\/wc\\/gla\\/ads\\/campaigns\\/asset-groups\\?.*campaign_id=${ campaignId }\\b`
			),
			payload,
			status,
			[ 'GET' ]
		);
	}

	/**
	 * Fulfill generate text assets request.
	 *
	 * @param {Object} payload - The response payload to return.
	 * @param {number} status - The HTTP status in the response.
	 * @return {Promise<void>}
	 */
	async fulfillGenerateTextAssetsRequest( payload, status = 200 ) {
		await this.fulfillRequest(
			/\/wc\/gla\/ads\/assets\/generate-text\b/,
			payload,
			status,
			[ 'POST' ]
		);
	}

	/**
	 * Fulfill generate image assets request.
	 *
	 * @param {Object} payload - The response payload to return.
	 * @param {number} status - The HTTP status in the response.
	 * @return {Promise<void>}
	 */
	async fulfillGenerateImageAssetsRequest( payload, status = 200 ) {
		await this.fulfillRequest(
			/\/wc\/gla\/ads\/assets\/generate-images\b/,
			payload,
			status,
			[ 'POST' ]
		);
	}

	/**
	 * Mocks a request for missing EU declaration campaigns.
	 *
	 * @param {Object} payload - The mock response payload to be returned.
	 * @param {number} [status=200] - The HTTP status code to be returned. Defaults to 200.
	 * @return {Promise<void>} A promise that resolves when the request is mocked.
	 */
	async fulfillMissingEUDeclarationCampaigns( payload, status = 200 ) {
		await this.fulfillRequest(
			/\/wc\/gla\/ads\/campaigns\/missing-eu-political-declaration\b/,
			payload,
			status,
			[ 'GET' ]
		);
	}

	/**
	 * Mocks the presence of campaigns missing EU political declarations.
	 */
	async mockHasMissingEUDeclarationCampaigns() {
		await this.fulfillMissingEUDeclarationCampaigns( [
			{
				id: 12345,
				name: 'Campaign 1',
			},
			{
				id: 23456,
				name: 'Campaign 2',
			},
		] );
	}

	/**
	 * Mocks the absence of campaigns missing EU political declarations.
	 */
	async mockHasNoMissingEUDeclarationCampaigns() {
		await this.fulfillMissingEUDeclarationCampaigns( [] );
	}
}
