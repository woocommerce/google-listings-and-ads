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
	const eventURL = 'https://www.google.com/pagead';
	return page.waitForRequest( ( request ) => {
		const url = request.url();
		const match = encodeURIComponent( 'event=' + eventName );
		return url.startsWith( eventURL ) && url.includes( match );
	} );
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
