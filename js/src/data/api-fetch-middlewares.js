/**
 * Internal dependencies
 */
import { API_NAMESPACE } from './constants';

/**
 * @callback onErrorResponseCallback
 * @param {Object} response The error response instance.
 * @return {Promise<any>|any} Reject or throw the response error if want to continue original error handling process.
 *                            Otherwise, resolve recovery result if the error could be processed here.
 */

/**
 * Create a middleware with a callback to watch and handle the error response instance globally.
 *
 * @param  {onErrorResponseCallback} onErrorResponse A callback to be invoked when receiving error response.
 * @return {import('@wordpress/api-fetch').APIFetchMiddleware} A middleware to watch and handle the error response instance globally.
 */
export function createErrorResponseCatcher( onErrorResponse ) {
	const regexApiNamespace = new RegExp( `^${ API_NAMESPACE }\/` );

	return function errorResponseCatcher( options, next ) {
		// Requests issued from other places go to the original middlewares
		if ( ! regexApiNamespace.test( options.path ) ) {
			return next( options );
		}

		const { parse: shouldParseResponse = true } = options;

		return next( { ...options, parse: false } )
			.catch( onErrorResponse )
			.catch( ( response ) => {
				if ( shouldParseResponse && response.json ) {
					return response
						.json()
						.then( ( errMessage ) => Promise.reject( errMessage ) );
				}

				throw response;
			} )
			.then( ( response ) => {
				// Ref: https://github.com/WordPress/gutenberg/blob/%40wordpress/api-fetch%405.1.1/packages/api-fetch/src/utils/response.js#L14-L24
				if ( shouldParseResponse ) {
					if ( response.status === 204 ) {
						return null;
					}

					return response.json
						? response.json()
						: Promise.reject( response );
				}

				return response;
			} );
	};
}
