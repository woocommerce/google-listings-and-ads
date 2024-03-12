/**
 * Tracking of Gtag events.
 *
 * @typedef { import( '@playwright/test' ).Request } Request
 * @typedef { import( '@playwright/test' ).Page } Page
 */

/**
 * Tracks when the Gtag Event request matching a specific name is sent.
 *
 * @param {Page} page
 * @param {string} eventName Event name to match.
 * @param {string|null} urlPath The starting path to match where the event should be triggered.
 * @return {Promise<Request>} Matching request.
 */
export function trackGtagEvent( page, eventName, urlPath = null ) {
	const eventPath = '/pagead/';
	return page.waitForRequest( ( request ) => {
		const url = request.url();
		const match = 'event=' + eventName;
		const origin = new URL( page.url() ).origin;
		const params = new URL( url ).searchParams;

		return (
			url.includes( eventPath ) &&
			params.get( 'data' )?.includes( match ) &&
			( urlPath
				? params
						.get( 'url' )
						.includes( `${ `${ origin }/${ urlPath }` }` )
				: true )
		);
	} );
}

/**
 * Retrieve data from a Gtag event.
 *
 * @param {Request} request
 * @return {Object} Data sent with the event.
 */
export function getEventData( request ) {
	const url = new URL( request.url() );
	const params = new URLSearchParams( url.search );
	const data = Object.fromEntries(
		params
			.get( 'data' )
			.split( ';' )
			.map( ( pair ) => pair.split( '=' ) )
	);

	if ( params.get( 'value' ) ) {
		data.value = params.get( 'value' );
	}

	if ( params.get( 'currency_code' ) ) {
		data.currency_code = params.get( 'currency_code' );
	}

	return data;
}
