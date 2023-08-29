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
	 * @param {RegExp|String} url The url to fulfill.
	 * @param {Object} payload The payload to send.
	 * @returns {Promise<void>}
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
	 * @param {Object} payload
	 * @returns {Promise<void>}
	 */
	async fulfillMCReportProgram( payload ) {
		await this.fulfillRequest(
			/\/wc\/gla\/mc\/reports\/programs\b/,
			payload
		);
	}

	/**
	 * Fulfill the Target Audience request.
	 * @param {Object} payload
	 * @returns {Promise<void>}
	 */
	async fulfillTargetAudience( payload ) {
		await this.fulfillRequest(
			/\/wc\/gla\/mc\/target_audience\b/,
			payload
		);
	}

	/**
	 * Fulfill the JetPack Connection request.
	 * @param {Object} payload
	 * @returns {Promise<void>}
	 */
	async fulfillJetPackConnection( payload ) {
		await this.fulfillRequest( /\/wc\/gla\/jetpack\/connected\b/, payload );
	}

	/**
	 * Fulfill the Google Connection request.
	 * @param {Object} payload
	 * @returns {Promise<void>}
	 */
	async fulfillGoogleConnection( payload ) {
		await this.fulfillRequest( /\/wc\/gla\/google\/connected\b/, payload );
	}

	/**
	 * Fulfill the Ads Connection request.
	 * @param {Object} payload
	 * @returns {Promise<void>}
	 */
	async fulfillAdsConnection( payload ) {
		await this.fulfillRequest( /\/wc\/gla\/ads\/connection\b/, payload );
	}
}
