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
	const eventURL = 'https://www.google.com/pagead/1p-user-list/';
	return page.waitForRequest( ( request ) => {
		const url = request.url();
		const match = encodeURIComponent( 'event=' + eventName );
		return url.startsWith( eventURL ) && url.includes( match );
	} );
}

/**
 * Retrieve data from a gtag event and convert it to key / value pairs.
 *
 * @param {HTTPRequest} request
 * @return {Object} Data sent with the event.
 */
export function getEventData( request ) {
	const url = new URL( request.url() );
	const data = new URLSearchParams( url.search ).get( 'data' );

	return Object.fromEntries(
		data.split( ';' ).map( ( pair ) => pair.split( '=' ) )
	);
}
