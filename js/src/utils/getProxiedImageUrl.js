/**
 * External dependencies
 */
import { addQueryArgs } from '@wordpress/url';

/**
 * Converts an external image URL to use the WordPress image proxy endpoint.
 * This bypasses ad blockers that might block direct access to AI-generated images.
 *
 * Use this utility function when rendering images in the UI to ensure they load
 * even when users have ad blockers enabled. The original URLs are preserved in
 * state and only proxied at render time.
 *
 * The function includes a WordPress REST API nonce in the URL to authenticate
 * requests made by img tags, which cannot send custom HTTP headers.
 *
 * @param {string} imageUrl - The original image URL to proxy.
 * @return {string} The proxied URL through the WordPress REST API.
 */
export default function getProxiedImageUrl( imageUrl ) {
	if ( ! imageUrl ) {
		return imageUrl;
	}

	const nonce = window.wpApiSettings?.nonce || '';
	const root = window.wpApiSettings?.root || '/wp-json/';
	const baseUrl = `${ root }wc/gla/ads/assets/image-proxy`;

	const params = { url: imageUrl };
	if ( nonce ) {
		params._wpnonce = nonce;
	}

	return addQueryArgs( baseUrl, params );
}
