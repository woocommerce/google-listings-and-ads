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
	 * @param {RegExp|string} url The url to fulfill.
	 * @param {Object} payload The payload to send.
	 * @return {Promise<void>}
	 */
	async fulfillRequest( url, payload, status = 200 ) {
		await this.page.route( url, ( route ) =>
			route.fulfill( {
				status,
				content: 'application/json',
				headers: { 'Access-Control-Allow-Origin': '*' },
				body: JSON.stringify( payload ),
			} )
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
	 * @return {Promise<void>}
	 */
	async fulfillTargetAudience( payload ) {
		await this.fulfillRequest(
			/\/wc\/gla\/mc\/target_audience\b/,
			payload
		);
	}

	/**
	 * Fulfill the MC accounts request.
	 *
	 * @param {Object} payload
	 * @return {Promise<void>}
	 */
	async fulfillMCAccounts( payload, status = 200 ) {
		await this.fulfillRequest(
			/\/wc\/gla\/mc\/accounts\b/,
			payload,
			status
		);
	}

	/**
	 * Fulfill the MC accounts claim-overwrite request.
	 *
	 * @param {Object} payload
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
		await this.fulfillRequest(
			/\/wc\/gla\/mc\/connection\b/,
			payload
		);
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
	 * Fulfill the Sync Settings Connection request.
	 *
	 * @param {Object} payload
	 * @return {Promise<void>}
	 */
	async fulfillSettingsSync( payload ) {
		await this.fulfillRequest( /\/wc\/gla\/mc\/settings\/sync\b/, payload );
	}

	/**
	 * Mock the request to connect Jetpack
	 */
	async mockJetpackConnect( url ) {
		await this.fulfillConnectJetPack( { url } );
	}

	/**
	 * Mock Jetpack as connected.
	 */
	async mockJetpackConnected( displayName = 'John', email = 'mail@example.com' ) {
		await this.fulfillJetPackConnection( {
			active: 'yes',
			owner: 'yes',
			displayName,
			email,
		} );
	}

	/**
	 * Mock the request to connect Google.
	 */
	async mockGoogleConnect( url ) {
		await this.fulfillConnectGoogle( { url } );
	}

	/**
	 * Mock Google as connected.
	 */
	async mockGoogleConnected( email = 'mail@example.com' ) {
		await this.fulfillGoogleConnection( {
			active: 'yes',
			email,
			scope: [
				'https:\/\/www.googleapis.com\/auth\/content',
				'https:\/\/www.googleapis.com\/auth\/adwords',
				'https:\/\/www.googleapis.com\/auth\/userinfo.email',
				'https:\/\/www.googleapis.com\/auth\/siteverification.verify_only',
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
	 * Mock MC as connected.
	 */
	async mockMCConnected( id = 1234 ) {
		await this.fulfillMCConnection( {
			id,
			status: 'connected',
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
				domain: 'https:\/\/example.com',
			},
			{
				id: 23456,
				subaccount: true,
				name: 'MC Account 2',
				domain: 'https:\/\/example.com',
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
	 */
	async mockMCCreateAccountWebsiteNotClaimed( id = 12345 ) {
		await this.fulfillMCAccounts( {
			id,
			subaccount: null,
			name: null,
			domain: null,
		} );
	}

	/**
	 * Mock MC create account where the website is claimed.
	 */
	async mockMCCreateAccountWebsiteClaimed( id = 12345, websiteUrl = 'example.com' ) {
		await this.fulfillMCAccounts(
			{
				message: 'Website already claimed, use overwrite to complete the process.',
				id,
				website_url: websiteUrl,
			},
			403
		);
	}

	/**
	 * Mock MC accounts claim overwrite.
	 */
	async mockMCAccountsClaimOverwrite( id = 12345 ) {
		await this.fulfillMCAccountsClaimOverwrite(
			{
				id,
				subaccount: null,
				name: null,
				domain: null,
			},
		);
	}
}
