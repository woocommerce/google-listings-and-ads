/**
 * Internal dependencies
 */
import getProxiedImageUrl from '~/utils/getProxiedImageUrl';

const IMAGE_URL = 'https://tpc.googlesyndication.com/image.jpg';

describe( 'getProxiedImageUrl', () => {
	beforeEach( () => {
		window.wpApiSettings = {
			nonce: 'test-nonce',
			root: 'https://example.com/wp-json/',
		};
	} );

	afterEach( () => {
		delete window.wpApiSettings;
	} );

	it( 'builds proxy URL with nonce and encoded image URL', () => {
		const result = getProxiedImageUrl( IMAGE_URL );

		expect( result ).toContain( 'wc/gla/ads/assets/image-proxy' );
		expect( result ).toContain( encodeURIComponent( IMAGE_URL ) );
		expect( result ).toContain( '_wpnonce=test-nonce' );
	} );

	it( 'uses the root from wpApiSettings as the base', () => {
		const result = getProxiedImageUrl( IMAGE_URL );

		expect( result ).toMatch(
			/^https:\/\/example\.com\/wp-json\/wc\/gla\/ads\/assets\/image-proxy/
		);
	} );

	it( 'omits _wpnonce when nonce is empty', () => {
		window.wpApiSettings.nonce = '';
		const result = getProxiedImageUrl( IMAGE_URL );

		expect( result ).not.toContain( '_wpnonce' );
	} );

	it( 'falls back to /wp-json/ root when wpApiSettings is absent', () => {
		delete window.wpApiSettings;
		const result = getProxiedImageUrl( IMAGE_URL );

		expect( result ).toContain( '/wp-json/wc/gla/ads/assets/image-proxy' );
	} );

	it( 'returns falsy values unchanged', () => {
		expect( getProxiedImageUrl( null ) ).toBeNull();
		expect( getProxiedImageUrl( '' ) ).toBe( '' );
		expect( getProxiedImageUrl( undefined ) ).toBeUndefined();
	} );
} );
