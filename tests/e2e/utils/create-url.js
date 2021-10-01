/**
 * This is an enhanced/fixed version of `@wordpress/e2e-utils.createURL`,
 * the original one has not so usable API.
 * The PR to upstream nad tests are pending at
 * https://github.com/WordPress/gutenberg/pull/34051
 */
/**
 * External dependencies
 */
import { createURL as originalCreateUrl } from '@woocommerce/e2e-utils'; // eslint-disable-line import/named
const WP_BASE_URL = originalCreateUrl( '' );

/**
 * Creates new absolute (full) URL string by parsing given
 * `url` and `query` relative to the WP base.
 *
 * Given `query` params are merged with the ones already present in the `url`.
 * Any overlapping ones will be overwritten.
 *
 * @example
 *
 * ```js
 * createURL( '/foo?a=b&bar=f', { bar: 'baz', fiz: 'a/b/c' } );
 * // "http://localhost:8889/foo?a=b&bar=baz&fiz=a%2Fb%2Fc"
 * ```
 *
 * @param {string}              [url=''] A valid URL string, usually a relative path, but could contain as well contain query, fragment or be absolute.
 * @param {string|Object|Array} [query]     Query parameters in any format supported by {@link https://developer.mozilla.org/en-US/docs/Web/API/URLSearchParams/URLSearchParams | URLSearchParams}.
 * @return {string} String which represents the full, absolute URL.
 */
export function createURL( url = '', query = '' ) {
	const fullUrl = new URL( url, WP_BASE_URL );

	// Merge query params if any.
	if ( query ) {
		for ( const [ key, value ] of new URLSearchParams( query ) ) {
			// Overwrite any params if already present.
			fullUrl.searchParams.set( key, value );
		}
	}

	return fullUrl.href;
}
