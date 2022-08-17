/**
 * Tracking of Gtag events.
 */
/* global page */

/**
 * @typedef {import('puppeteer').HTTPRequest} HTTPRequest
 */

/**
 * Tracks when the Gtag Event request matching a specific name is sent.
 *
 * @param {string} eventName Event name to match.
 * @return {Promise} Matching request.
 */
export function trackGtagEvent( eventName ) {
	const eventPath = '/pagead';
	const option = { timeout: 15000 };
	return page.waitForRequest( ( request ) => {
		const url = request.url();
		const match = encodeURIComponent( 'event=' + eventName );
		return url.includes( eventPath ) && url.includes( match );
	}, option );
}

/**
 * Retrieve data from a Gtag event.
 *
 * @param {HTTPRequest} request
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
