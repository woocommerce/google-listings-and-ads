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
	async fulfillRequest( url, payload ) {
		await this.page.route( url, ( route ) =>
			route.fulfill( {
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
	 * Mock Jetpack as connected
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
	 * Mock the request to connect Google
	 */
	async mockGoogleConnect( url ) {
		await this.fulfillConnectGoogle( { url } );
	}

	/**
	 * Mock Google as connected
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
	 * Mock Google as not connected
	 */
	async mockGoogleNotConnected() {
		await this.fulfillGoogleConnection( {
			active: 'no',
			email: '',
			scope: [],
		} );
	}
}
