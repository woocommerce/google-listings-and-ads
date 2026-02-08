import { API_NAMESPACE } from '~/data/constants';

/**
 * Converts an external image URL to use the WordPress image proxy endpoint.
 * This bypasses adblockers that might block direct access to AI-generated images.
 *
 * Use this utility function when rendering images in the UI to ensure they load
 * even when users have adblockers enabled. The original URLs are preserved in
 * state and only proxied at render time.
 *
 * @param {string} imageUrl - The original image URL to proxy.
 * @return {string} The proxied URL through the WordPress REST API.
 */
export default function proxyImageUrl( imageUrl ) {
	if ( ! imageUrl ) {
		return imageUrl;
	}

	const apiUrl = new URL(
		`/wp-json${ API_NAMESPACE }/ads/assets/image-proxy`,
		window.location.origin
	);
	apiUrl.searchParams.set( 'url', imageUrl );

	return apiUrl.toString();
}
