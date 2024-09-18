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
	 * Fulfill a request with a payload.
	 *
	 * @param {RegExp|string} url      The url to fulfill.
	 * @param {Object}        payload  The payload to send.
	 * @param {number}        status   The HTTP status in the response.
	 * @param {Array}         methods  The HTTP methods in the request to be fulfill.
	 * @return {Promise<void>}
	 */
	async fulfillRequest( url, payload, status = 200, methods = [] ) {
		await this.page.route( url, ( route ) => {
			if (
				methods.length === 0 ||
				methods.includes( route.request().method() )
			) {
				route.fulfill( {
					status,
					content: 'application/json',
					headers: { 'Access-Control-Allow-Origin': '*' },
					body: JSON.stringify( payload ),
				} );
			} else {
				route.fallback();
			}
		} );
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
	async fulfillMCConnection( payload ) {
		await this.fulfillRequest( /\/wc\/gla\/mc\/connection\b/, payload );
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
	 * Fulfill the Settings request.
	 *
	 * @param {Object} payload
	 * @param {number} status
	 * @param {Array}  methods
	 * @return {Promise<void>}
	 */
	async fulfillSettings( payload, status = 200, methods = [] ) {
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
	 * @return {Promise<void>}
	 */
	async fulfillAdsAccounts( payload ) {
		await this.fulfillRequest( /\/wc\/gla\/ads\/accounts\b/, payload );
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
	 * Fulfill phone verification request request.
	 *
	 * @param {Object} payload
	 * @return {Promise<void>}
	 */
	async fulfillPhoneVerificationRequest( payload ) {
		await this.fulfillRequest(
			/\/wc\/gla\/mc\/phone-verification\/request\b/,
			payload
		);
	}

	/**
	 * Fulfill phone verification verify request.
	 *
	 * @param {Object} payload
	 * @param {number} status
	 * @return {Promise<void>}
	 */
	async fulfillPhoneVerificationVerifyRequest( payload, status = 204 ) {
		await this.fulfillRequest(
			/\/wc\/gla\/mc\/phone-verification\/verify\b/,
			payload,
			status
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
	async mockAdsAccountIncomplete() {
		await this.fulfillAdsConnection( {
			id: 12345,
			currency: 'TWD',
			symbol: 'NT$',
			status: 'incomplete',
			step: 'billing',
		} );
	}

	/**
	 * Mock Google Ads account as connected.
	 *
	 * @param {number} [id=12345]
	 * @return {Promise<void>}
	 */
	async mockAdsAccountConnected( id = 12345 ) {
		await this.fulfillAdsConnection( {
			id,
			currency: 'TWD',
			symbol: 'NT$',
			status: 'connected',
		} );
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
	 * @param {boolean} notificationServiceEnabled
	 * @param {null|'approved'|'error'|'dissaproved'} wpcomRestApiStatus
	 */
	async mockMCConnected(
		id = 1234,
		notificationServiceEnabled = false,
		wpcomRestApiStatus = null
	) {
		await this.fulfillMCConnection( {
			id,
			status: 'connected',
			notification_service_enabled: notificationServiceEnabled,
			wpcom_rest_api_status: wpcomRestApiStatus,
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
	 * Mock the successful settings sync requesst.
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
}
