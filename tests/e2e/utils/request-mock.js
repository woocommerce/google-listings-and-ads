/**
 * Observer to listen and mock puppeteer page requests.
 */
export class RequestMock {
	/**
	 * Map of mock functions to URL patters they should apply to.
	 *
	 * @type {Map<jest.Mock, RegExp>}
	 */
	#mocks;
	/**
	 * The instance of a page being observed.
	 *
	 * @type {import("puppeteer").Page}
	 */
	#observedPage;

	#callMock;

	constructor() {
		this.#mocks = new Map();
		/**
		 * Calls a matching mock, if any.
		 *
		 * @param {import("puppeteer").Request} interceptedRequest
		 */
		this.#callMock = ( interceptedRequest ) => {
			const url = interceptedRequest.url();
			// Find a matching mock.
			for ( const [ mock, pattern ] of this.#mocks ) {
				if ( url.match( pattern ) ) {
					const result = mock( interceptedRequest );

					if ( result === undefined ) {
						interceptedRequest.continue();
					} else if ( ! result ) {
						interceptedRequest.abort();
					}
					return;
				}
			}
			// Pass through non-matching requests.
			if ( ! interceptedRequest._interceptionHandled ) {
				interceptedRequest.continue();
			}
		};
	}

	/**
	 * Get the mock to stub/mock/observe requests for a URL matching the given `pattern`.
	 *
	 * @param {RegExp} pattern Pattern for an URL(s) to mock.
	 * @return {jest.Mock<import("puppeteer").Request>} New jest mock, will be called with an intercepted request. If the mock returns:
	 * 	- `undefined` - the original request will be passed through, unless already handled.
	 *  - falsy - the request will be aborted
	 *  - truthy - no action will be taken
	 */
	mock( pattern ) {
		const mock = jest.fn().mockName( 'mocked request' );
		this.#mocks.set( mock, pattern );
		return mock;
	}

	/**
	 * Stop applying all or the given mock.
	 *
	 * @param {jest.Mock} [mock] Mock to be restored/unobserved. If not given all will be restored.
	 */
	restore( mock ) {
		if ( mock ) {
			this.#mocks.delete( mock );
		} else {
			this.#mocks.clear();
		}
	}

	/**
	 * Start oberving/intercepting requests for the given `page`.
	 *
	 * @param {import("puppeteer").Page} page
	 */
	async observe( page ) {
		// Unobserve the previous page.
		this.disconnect();

		this.#observedPage = page;
		await page.setRequestInterception( true );
		page.on( 'request', this.#callMock );
	}
	/**
	 * Stop observing.
	 */
	disconnect() {
		if ( this.#observedPage ) {
			this.#observedPage.off( 'request', this.#callMock );
			this.#observedPage = undefined;
		}
	}
}
