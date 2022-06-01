/**
 * Tracking of Gtag events.
 */
/* global page */

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
