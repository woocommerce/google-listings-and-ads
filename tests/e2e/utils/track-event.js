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
		const params = new URL( url ).searchParams;

		if (
			! url.includes( eventPath ) ||
			! params.get( 'data' )?.includes( 'event=' + eventName )
		) {
			return false;
		}

		if ( ! urlPath ) {
			return true;
		}

		const origin = new URL( page.url() ).origin;
		return params.get( 'url' )?.includes( `${ origin }/${ urlPath }` );
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

const PERSISTED_DATA_LAYER_KEY = 'gla_e2e_data_layer_events';

/**
 * Keep gtag events from the dataLayer across full page reloads.
 *
 * A non-AJAX form submit, like the single product add to cart, reloads the page
 * and resets `window.dataLayer`. Call this before navigating to save the events
 * to sessionStorage when the page unloads, so `getDataLayerEventParams` can still
 * find them after the reload.
 *
 * @param {Page} page
 */
export async function persistDataLayerEvents( page ) {
	await page.addInitScript( ( key ) => {
		window.addEventListener( 'pagehide', () => {
			const stored = JSON.parse(
				window.sessionStorage.getItem( key ) || '[]'
			);
			const events = ( window.dataLayer || [] )
				.map( ( item ) => Array.from( item ) )
				.filter( ( item ) => item[ 0 ] === 'event' );

			window.sessionStorage.setItem(
				key,
				JSON.stringify( [ ...stored, ...events ] )
			);
		} );
	}, PERSISTED_DATA_LAYER_KEY );
}

/**
 * Retrieve the params a gtag event call pushed to the dataLayer.
 *
 * Includes events saved before a page reload by `persistDataLayerEvents`.
 *
 * @param {Page} page
 * @param {string} eventName Event name to match.
 * @return {Promise<Object|undefined>} Params of the last matching event.
 */
export async function getDataLayerEventParams( page, eventName ) {
	const dataLayer = await page.evaluate( ( key ) => {
		const stored = JSON.parse(
			window.sessionStorage.getItem( key ) || '[]'
		);
		const current = ( window.dataLayer || [] ).map( ( item ) =>
			Array.from( item )
		);

		return [ ...stored, ...current ];
	}, PERSISTED_DATA_LAYER_KEY );

	return dataLayer
		.filter( ( i ) => i[ 0 ] === 'event' && i[ 1 ] === eventName )
		.pop()?.[ 2 ];
}
