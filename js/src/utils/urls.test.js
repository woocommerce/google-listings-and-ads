/**
 * Internal dependencies
 */
import { addReferrerParams, getAccountAwareUrl } from '~/utils/urls';

describe( 'getAccountAwareUrl', () => {
	it( 'wraps the destination URL in an accountchooser redirect for the given email', () => {
		expect(
			getAccountAwareUrl(
				'https://example.com/report',
				'merchant@example.com'
			)
		).toBe(
			'https://accounts.google.com/accountchooser?continue=https%3A%2F%2Fexample.com%2Freport&Email=merchant%40example.com'
		);
	} );
} );

describe( 'addReferrerParams', () => {
	it( 'appends referrer_type and referrer_id query params to the given href', () => {
		expect(
			addReferrerParams( '/onboarding', 'in_product_placements', 'foo' )
		).toBe(
			'/onboarding?referrer_type=in_product_placements&referrer_id=foo'
		);
	} );

	it( 'preserves existing query params on the href', () => {
		expect(
			addReferrerParams(
				'/onboarding?foo=bar',
				'in_product_placements',
				'baz'
			)
		).toBe(
			'/onboarding?foo=bar&referrer_type=in_product_placements&referrer_id=baz'
		);
	} );
} );
