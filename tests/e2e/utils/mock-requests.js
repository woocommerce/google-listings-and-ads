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
	 * Add the parameter gla-e2e-onboarded to the request to simulate the onboarded state; otherwise, it will redirect to the onboarding page.
	 *
	 * @return {Promise<void>}
	 */
	async mockOnboarded() {
		await this.page.route( /\/admin.php\b/, ( route ) => {
			const url = `${ route.request().url() }&gla-e2e-onboarded=true`;
			route.continue( { url } );
		} );
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
	 * Fulfill the Google Connection request.
	 *
	 * @param {Object} payload
	 * @return {Promise<void>}
	 */
	async fulfillGoogleConnection( payload ) {
		await this.fulfillRequest( /\/wc\/gla\/google\/connected\b/, payload );
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
}
